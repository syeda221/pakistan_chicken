<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductionController extends Controller
{
    public function index()
    {
        $entries = DB::table('production_entries as pe')
            ->leftJoin('users as u', 'u.id', '=', 'pe.created_by')
            ->select('pe.*', 'u.name as user_name', 
                DB::raw('(SELECT COUNT(*) FROM production_entry_items WHERE production_entry_id = pe.id) as items_count'),
                DB::raw("(SELECT GROUP_CONCAT(CONCAT(p.item_name, IF(pv.size_label IS NULL AND pv.variant_name IS NULL, '', CONCAT(' ', COALESCE(pv.size_label, pv.variant_name))), ' (', pei.qty_entered, ' ', pei.unit, ')') SEPARATOR ', ') FROM production_entry_items pei JOIN products p ON p.id = pei.product_id LEFT JOIN product_variants pv ON pv.id = pei.variant_id WHERE pei.production_entry_id = pe.id) as product_details")
            )
            ->orderBy('pe.created_at', 'desc')
            ->get();

        return view('admin_panel.production.index', compact('entries'));
    }

    public function create()
    {
        $products = Product::with('unit')->orderBy('item_name')->get();
        return view('admin_panel.production.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'production_date' => 'required|date',
            'product_id' => 'required|array',
            'qty' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            $entryId = DB::table('production_entries')->insertGetId([
                'entry_no' => 'PROD-' . date('Ymd-His'),
                'production_date' => $request->production_date,
                'source' => $request->source ?? 'kitchen',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($request->product_id as $index => $productId) {
                $qtyTyped = (float)$request->qty[$index];
                if ($qtyTyped <= 0) continue;

                $variantId = $request->variant_id[$index] ?? null;
                // Treat empty string variant as null
                if ($variantId === '') $variantId = null;

                $product = Product::with('unit')->find($productId);
                if (!$product) continue;

                $unitName = strtolower($product->unit->name ?? '');
                $prodName = strtolower($product->item_name);
                
                // Determine if it's a gram item (1 KG input -> 1000 Stock units)
                $isGram = str_contains($unitName, 'gram') || str_contains($unitName, 'gm') || 
                          str_contains($prodName, 'gram') || str_contains($prodName, ' gm') || 
                          $product->unit_type === 'kg';
                
                $qtyStock = $qtyTyped;
                $dbVariantId = $variantId;

                if ($isGram) {
                    $dbVariantId = null; // Always manage stock on the main product for KG
                    if ($variantId) {
                        $vModel = \App\Models\ProductVariant::find($variantId);
                        if ($vModel) {
                            $kgSize = floatval($vModel->size_value);
                            // Convert to grams if the variant size is in KG
                            if ($vModel->size_unit === 'kg') {
                                $qtyStock = ($kgSize * $qtyTyped * 1000);
                            } else {
                                $qtyStock = ($kgSize * $qtyTyped);
                            }
                        }
                    } else {
                        $qtyStock = $qtyTyped * 1000; // Default conversion if no variant
                    }
                }

                DB::table('production_entry_items')->insert([
                    'production_entry_id' => $entryId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'unit' => $product->unit->name ?? 'Pc',
                    'qty_entered' => $qtyTyped,
                    'qty_stock' => $qtyStock,
                    'notes' => $request->item_note[$index] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update Stock
                $stockQuery = Stock::where('product_id', $productId)
                    ->where('branch_id', 1)
                    ->where('warehouse_id', 1); // 🔥 Standardized Warehouse ID
                
                if ($dbVariantId) {
                    $stockQuery->where('variant_id', $dbVariantId);
                } else {
                    $stockQuery->whereNull('variant_id');
                }
                
                $stock = $stockQuery->first();

                if ($stock) {
                    $stock->qty += $qtyStock;
                    $stock->save();
                } else {
                    Stock::create([
                        'product_id' => $productId,
                        'variant_id' => $dbVariantId,
                        'branch_id' => 1,
                        'warehouse_id' => 1, // 🔥 Added Warehouse ID
                        'qty' => $qtyStock,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('production.index')->with('success', 'Production entry saved and stock updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Error: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $entry = DB::table('production_entries')->where('id', $id)->first();
        if (!$entry) abort(404);

        $items = DB::table('production_entry_items as pei')
            ->leftJoin('products as p', 'p.id', '=', 'pei.product_id')
            ->where('pei.production_entry_id', $id)
            ->select('pei.*', 'p.item_name', 'p.item_code', 'p.unit_type')
            ->get();

        $products = Product::with('unit')->orderBy('item_name')->get();

        return view('admin_panel.production.edit', compact('entry', 'items', 'products'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'production_date' => 'required|date',
            'product_id' => 'required|array',
            'qty' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            // 1. Reverse old stock
            $oldItems = DB::table('production_entry_items')->where('production_entry_id', $id)->get();
            foreach ($oldItems as $oi) {
                // Determine if it was a main-product stock item (KG logic)
                $oldProduct = Product::find($oi->product_id);
                $oldIsGram = $oldProduct && ($oldProduct->unit_type === 'kg' || str_contains(strtolower($oldProduct->item_name), 'gram'));
                
                $stockQuery = Stock::where('product_id', $oi->product_id)
                    ->where('branch_id', 1)
                    ->where('warehouse_id', 1);

                if ($oldIsGram || !$oi->variant_id) {
                    $stockQuery->whereNull('variant_id');
                } else {
                    $stockQuery->where('variant_id', $oi->variant_id);
                }

                $stock = $stockQuery->first();
                if ($stock) {
                    $stock->qty -= $oi->qty_stock;
                    $stock->save();
                }
            }

            // 2. Delete old items
            DB::table('production_entry_items')->where('production_entry_id', $id)->delete();

            // 3. Update entry header
            DB::table('production_entries')->where('id', $id)->update([
                'production_date' => $request->production_date,
                'source' => $request->source ?? 'kitchen',
                'notes' => $request->notes,
                'updated_at' => now(),
            ]);

            // 4. Insert new items + update stock (same as store)
            foreach ($request->product_id as $index => $productId) {
                $qtyTyped = (float)$request->qty[$index];
                if ($qtyTyped <= 0) continue;

                $variantId = $request->variant_id[$index] ?? null;
                if ($variantId === '') $variantId = null;

                $product = Product::with('unit')->find($productId);
                if (!$product) continue;

                $unitName = strtolower($product->unit->name ?? '');
                $prodName = strtolower($product->item_name);

                $isGram = str_contains($unitName, 'gram') || str_contains($unitName, 'gm') ||
                          str_contains($prodName, 'gram') || str_contains($prodName, ' gm') ||
                          $product->unit_type === 'kg';

                $qtyStock = $qtyTyped;
                $dbVariantId = $variantId;

                if ($isGram) {
                    $dbVariantId = null;
                    if ($variantId) {
                        $vModel = \App\Models\ProductVariant::find($variantId);
                        if ($vModel) {
                            $kgSize = floatval($vModel->size_value);
                            if ($vModel->size_unit === 'kg') {
                                $qtyStock = ($kgSize * $qtyTyped * 1000);
                            } else {
                                $qtyStock = ($kgSize * $qtyTyped);
                            }
                        }
                    } else {
                        $qtyStock = $qtyTyped * 1000;
                    }
                }

                DB::table('production_entry_items')->insert([
                    'production_entry_id' => $id,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'unit' => $product->unit->name ?? 'Pc',
                    'qty_entered' => $qtyTyped,
                    'qty_stock' => $qtyStock,
                    'notes' => $request->item_note[$index] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update Stock
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
                    $stock->qty += $qtyStock;
                    $stock->save();
                } else {
                    Stock::create([
                        'product_id' => $productId,
                        'variant_id' => $dbVariantId,
                        'branch_id' => 1,
                        'warehouse_id' => 1,
                        'qty' => $qtyStock,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('production.index')->with('success', 'Production entry updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Error: ' . $e->getMessage());
        }
    }

    public function gatepass($id)
    {
        $entry = DB::table('production_entries as pe')
            ->leftJoin('users as u', 'u.id', '=', 'pe.created_by')
            ->where('pe.id', $id)
            ->select('pe.*', 'u.name as user_name')
            ->first();

        if (!$entry) abort(404);

        $items = DB::table('production_entry_items as pei')
            ->leftJoin('products as p', 'p.id', '=', 'pei.product_id')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'pei.variant_id')
            ->where('pei.production_entry_id', $id)
            ->select('pei.*', 'p.item_name', 'p.item_code', 'p.unit_type', 'pv.size_label', 'pv.variant_name')
            ->get();

        return view('admin_panel.production.gatepass', compact('entry', 'items'));
    }
}
