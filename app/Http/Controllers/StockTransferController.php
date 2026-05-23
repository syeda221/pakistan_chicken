<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockTransfer;
use App\Models\WarehouseStock;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse'])
            ->latest()
            ->when($request->start_date && $request->end_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            })
            ->get()
            ->map(function ($transfer) {
                $productIds = is_array($transfer->product_id) ? $transfer->product_id : (json_decode($transfer->product_id, true) ?: []);
                $variantIds = is_array($transfer->variant_id) ? $transfer->variant_id : (json_decode($transfer->variant_id, true) ?: []);
                $quantities = is_array($transfer->quantity) ? $transfer->quantity : (json_decode($transfer->quantity, true) ?: []);

                $items = [];
                foreach ($productIds as $i => $pid) {
                    $vid = $variantIds[$i] ?? null;
                    $qty = (float) ($quantities[$i] ?? 0);
                    
                    $product = Product::find($pid);
                    $variant = $vid ? ProductVariant::find($vid) : null;
                    
                    if ($product) {
                        $items[] = [
                            'name' => $product->item_name . ($variant ? ' (' . $variant->variant_name . ')' : ''),
                            'qty'  => $qty,
                            'unit' => $product->unit_id,
                        ];
                    }
                }

                $transfer->items = collect($items);
                return $transfer;
            });

        return view(
            'admin_panel.warehouses.stock_transfers.index',
            compact('transfers')
        );
    }

    public function create()
    {
        $warehouses = Warehouse::all();
        $products = Product::with('variants')->get();
        return view('admin_panel.warehouses.stock_transfers.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        try {
            $productIds = $request->product_id;
            $variantIds = $request->variant_id;
            $quantities = $request->quantity;

            $request->validate([
                'transfer_to'  => 'required|in:shop,warehouse',
                'product_id'   => 'required|array|min:1',
                'product_id.*' => 'required|integer|exists:products,id',
                'quantity'     => 'required|array',
                'quantity.*'   => 'required|numeric|min:0.01',
            ]);

            $fromWarehouse = $request->from_warehouse_id;
            $transferTo    = $request->transfer_to;
            $toWarehouse   = $request->to_warehouse_id;
            $remarks       = $request->remarks;

            DB::beginTransaction();

            foreach ($productIds as $index => $productId) {
                if (empty($productId) || empty($quantities[$index])) {
                    continue;
                }

                $qty = (float) $quantities[$index];
                $variantId = $variantIds[$index] ?? null;

                if ($qty <= 0) {
                    continue;
                }

                // ---------- SOURCE ----------
                if ($fromWarehouse !== 'Shop') {
                    $sourceStock = WarehouseStock::firstOrCreate(
                        [
                            'warehouse_id' => $fromWarehouse,
                            'product_id'   => $productId,
                            'variant_id'   => $variantId
                        ],
                        ['quantity' => 0, 'price' => 0]
                    );

                    $sourceStock->quantity -= $qty;
                    $sourceStock->save();
                } else {
                    $sourceStock = Stock::firstOrCreate(
                        [
                            'product_id' => $productId,
                            'variant_id' => $variantId
                        ],
                        ['qty' => 0]
                    );

                    $sourceStock->qty -= $qty;
                    $sourceStock->save();
                }

                // ---------- DESTINATION ----------
                if ($transferTo === 'warehouse' && $toWarehouse) {
                    $destStock = WarehouseStock::firstOrCreate(
                        [
                            'warehouse_id' => $toWarehouse,
                            'product_id'   => $productId,
                            'variant_id'   => $variantId
                        ],
                        ['quantity' => 0, 'price' => $sourceStock->price ?? 0]
                    );

                    $destStock->quantity += $qty;
                    $destStock->save();
                } elseif ($transferTo === 'shop') {
                    $shopStock = Stock::firstOrCreate(
                        [
                            'product_id' => $productId,
                            'variant_id' => $variantId
                        ],
                        ['qty' => 0]
                    );

                    $shopStock->qty += $qty;
                    $shopStock->save();
                }
            }

            $transfer = StockTransfer::create([
                'from_warehouse_id' => $fromWarehouse === 'Shop' ? null : $fromWarehouse,
                'transfer_to'       => $transferTo,
                'to_warehouse_id'   => $transferTo === 'warehouse' ? $toWarehouse : null,
                'product_id'        => json_encode(array_values(array_filter($productIds ?? []))),
                'variant_id'        => json_encode(array_values($variantIds ?? [])),
                'quantity'          => json_encode(array_values(array_filter($quantities ?? []))),
                'remarks'           => $remarks,
                'created_at'        => $request->transfer_date ? \Carbon\Carbon::parse($request->transfer_date) : now(),
                'updated_at'        => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('recipt.warehouse', $transfer->id)
                ->with('success', 'Stock transferred successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        return back()->with('error', 'Deleting transfers not allowed.');
    }

    public function getStockQuantity(Request $request)
    {
        $productId   = $request->product_id;
        $variantId   = $request->variant_id;
        if (empty($variantId) || $variantId === 'null') {
            $variantId = null;
        }
        $warehouseId = $request->warehouse_id;

        if (!empty($warehouseId) && $warehouseId !== 'Shop') {
            $query = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId);
                
            if ($variantId) {
                $query->where('variant_id', $variantId);
            } else {
                $query->whereNull('variant_id');
            }

            $stock = $query->first();
            
            // Fallback for legacy records that might have been saved without a variant_id
            if (!$stock && $variantId) {
                $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->whereNull('variant_id')
                    ->first();
            }

            return response()->json([
                'quantity' => $stock ? $stock->quantity : 0,
                'source'   => 'warehouse'
            ]);
        }

        $query = Stock::where('product_id', $productId);
        if ($variantId) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }
        
        $stock = $query->first();

        // Fallback for shop stock as well
        if (!$stock && $variantId) {
            $stock = Stock::where('product_id', $productId)
                ->whereNull('variant_id')
                ->first();
        }

        return response()->json([
            'quantity' => $stock ? $stock->qty : 0,
            'source'   => 'shop'
        ]);
    }

    public function receipt($id)
    {
        $transfer = StockTransfer::with(['fromWarehouse', 'toWarehouse'])
            ->findOrFail($id);

        $productIds = is_array($transfer->product_id) ? $transfer->product_id : (json_decode($transfer->product_id, true) ?: []);
        $variantIds = is_array($transfer->variant_id) ? $transfer->variant_id : (json_decode($transfer->variant_id, true) ?: []);
        $quantities = is_array($transfer->quantity) ? $transfer->quantity : (json_decode($transfer->quantity, true) ?: []);

        $items = [];
        $unitTotals = [];

        foreach ($productIds as $i => $pid) {
            $vid = $variantIds[$i] ?? null;
            $qty = (float) ($quantities[$i] ?? 0);
            
            $product = Product::find($pid);
            $variant = $vid ? ProductVariant::find($vid) : null;
            
            if ($product) {
                $items[] = (object)[
                    'item_name' => $product->item_name,
                    'variant_name' => $variant ? $variant->variant_name : null,
                    'transfer_qty' => $qty,
                    'unit_id' => $product->unit_id,
                    'price' => $product->price,
                ];

                $unit = $product->unit_id ?? 'Unit';
                $unitTotals[$unit] = ($unitTotals[$unit] ?? 0) + $qty;
            }
        }

        $products = collect($items);

        return view(
            'admin_panel.warehouses.stock_transfers.receipt',
            compact('transfer', 'products', 'unitTotals')
        );
    }

    public function checkNewTransfers()
    {
        if (Auth::check() && Auth::user()->email === 'admin@admin.com') {
            $newTransfers = StockTransfer::with(['fromWarehouse', 'toWarehouse'])
                ->where('admin_notified', 0)
                ->orderBy('created_at', 'desc')
                ->get();
    
            return response()->json($newTransfers);
        }
        return response()->json([]);
    }

    public function markTransfersNotified(Request $request)
    {
        if (Auth::check() && Auth::user()->email === 'admin@admin.com') {
            $ids = $request->input('ids', []);
            if (!empty($ids)) {
                StockTransfer::whereIn('id', $ids)->update(['admin_notified' => 1]);
            }
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error'], 403);
    }
}
