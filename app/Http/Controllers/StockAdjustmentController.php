<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StockAdjustment::with(['user', 'items.product', 'items.variant'])
            ->orderBy('created_at', 'desc');

        if ($request->from_date) {
            $query->whereDate('adjustment_date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('adjustment_date', '<=', $request->to_date);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }

        $adjustments = $query->paginate(20)->withQueryString();

        return view('admin_panel.stock_adjustment.index', compact('adjustments'));
    }

    public function create()
    {
        $products = Product::with(['unit', 'variants'])->orderBy('item_name')->get();
        return view('admin_panel.stock_adjustment.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'adjustment_date' => 'required|date',
            'type'            => 'required|in:increase,decrease',
            'reason'          => 'required|string|max:255',
            'product_id'      => 'required|array|min:1',
            'qty'             => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $refNo = 'ADJ-' . date('Ymd') . '-' . str_pad(
                (StockAdjustment::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT
            );

            $adjustment = StockAdjustment::create([
                'ref_no'          => $refNo,
                'adjustment_date' => $request->adjustment_date,
                'type'            => $request->type,
                'reason'          => $request->reason,
                'notes'           => $request->notes,
                'created_by'      => Auth::id(),
            ]);

            foreach ($request->product_id as $idx => $productId) {
                $qty = (float)($request->qty[$idx] ?? 0);
                if (!$productId || $qty <= 0) continue;

                 $product = Product::with('unit')->find($productId);
                 if (!$product) continue;
 
                 $unitName = strtolower($product->unit->name ?? '');
                 $isKg = $product->unit_type === 'kg'
                     || str_contains($unitName, 'gram')
                     || str_contains($unitName, 'gm');
 
                 $variantId = $request->variant_id[$idx] ?? null;
                 if ($variantId === '') $variantId = null;
 
                 $dbVariantId = $variantId;
                 $qtyStock = $qty;
 
                 if ($isKg) {
                     $dbVariantId = null; // Always adjust main product stock for KG items
                     if ($variantId) {
                         $vModel = \App\Models\ProductVariant::find($variantId);
                         if ($vModel) {
                             $kgSize = floatval($vModel->size_value);
                             if ($vModel->size_unit === 'kg') {
                                 $qtyStock = ($kgSize * $qty * 1000);
                             } else {
                                 $qtyStock = ($kgSize * $qty);
                             }
                         }
                     } else {
                         $qtyStock = $qty * 1000;
                     }
                 }
 
                 StockAdjustmentItem::create([
                     'adjustment_id' => $adjustment->id,
                     'product_id'    => $productId,
                     'variant_id'    => $variantId,
                     'unit'          => $product->unit->name ?? 'Pc',
                     'qty'           => $qty,
                     'qty_stock'     => $qtyStock,
                     'notes'         => $request->item_note[$idx] ?? null,
                 ]);
 
                 // Apply stock adjustment
                 $stockQuery = Stock::where('product_id', $productId)
                     ->where('branch_id', 1)
                     ->where('warehouse_id', 1);
 
                 if ($dbVariantId) {
                     $stockQuery->where('variant_id', $dbVariantId);
                 } else {
                     $stockQuery->whereNull('variant_id');
                 }
                 $stock = $stockQuery->first();
 
                 if ($stock) {
                     if ($request->type === 'increase') {
                         $stock->qty += $qtyStock;
                     } else {
                         $stock->qty = max(0, $stock->qty - $qtyStock);
                     }
                     $stock->save();
                 } elseif ($request->type === 'increase') {
                     Stock::create([
                         'branch_id'    => 1,
                         'warehouse_id' => 1,
                         'product_id'   => $productId,
                         'variant_id'   => $dbVariantId,
                         'qty'          => $qtyStock,
                     ]);
                 }
            }

            DB::commit();
            return redirect()->route('stock-adjustment.index')
                ->with('success', 'Stock adjustment saved! Ref: ' . $refNo);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Error: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $adjustment = StockAdjustment::with(['user', 'items.product', 'items.variant'])->findOrFail($id);
        return view('admin_panel.stock_adjustment.show', compact('adjustment'));
    }

    public function report(Request $request)
    {
        $from = $request->from_date ?? today()->startOfMonth()->format('Y-m-d');
        $to   = $request->to_date   ?? today()->format('Y-m-d');
        $type = $request->type ?? '';

        $query = DB::table('stock_adjustment_items as sai')
            ->join('stock_adjustments as sa', 'sa.id', '=', 'sai.adjustment_id')
            ->join('products as p', 'p.id', '=', 'sai.product_id')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'sai.variant_id')
            ->leftJoin('users as u', 'u.id', '=', 'sa.created_by')
            ->whereBetween('sa.adjustment_date', [$from, $to])
            ->select(
                'sa.ref_no', 'sa.adjustment_date', 'sa.type', 'sa.reason',
                'p.item_name', 'p.item_code', 'p.unit_type',
                'sai.qty', 'sai.qty_stock', 'sai.unit', 'sai.notes as item_note',
                'sa.notes as adj_note', 'u.name as user_name',
                'pv.size_label', 'pv.variant_name'
            )
            ->orderBy('sa.adjustment_date', 'desc')
            ->orderBy('sa.id', 'desc');

        if ($type) {
            $query->where('sa.type', $type);
        }

        $rows = $query->get();

        return view('admin_panel.stock_adjustment.report', compact('rows', 'from', 'to', 'type'));
    }
}
