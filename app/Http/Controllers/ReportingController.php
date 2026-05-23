<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CustomerPayment;
use App\Models\ExpenseVoucher;
use App\Models\Product;
use App\Models\Sale;
use App\Models\VendorPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingController extends Controller
{
    public function item_stock_report()
    {
        // Pass ALL products for the dropdown — no pagination needed
        $products = \App\Models\Product::orderBy('item_name', 'asc')
            ->select('id', 'item_code', 'item_name')
            ->get();
        return view('admin_panel.reporting.item_stock_report', compact('products'));
    }

    public function fetchItemStock(Request $request)
    {
        $productId  = $request->product_id;
        $searchQ    = trim($request->get('q', ''));
        $startDate  = $request->start_date ?? date('Y-m-01');
        $endDate    = $request->end_date   ?? date('Y-m-t');

        $startDT = $startDate . ' 00:00:00';
        $endDT   = $endDate   . ' 23:59:59';

        // 1. Get products and their variants
        $pQuery = Product::with(['variants', 'unit'])->orderBy('item_name');

        if ($productId && $productId !== 'all') {
            $pQuery->where('id', $productId);
        } elseif ($searchQ !== '') {
            $pQuery->where(function($q) use ($searchQ) {
                $q->where('item_name','like',"%{$searchQ}%")
                  ->orWhere('item_code','like',"%{$searchQ}%");
            });
        }

        $products = $pQuery->get();
        $productIds = $products->pluck('id')->toArray();

        // 2. Bulk queries grouped by product_id AND variant_id with DATE filters
        $purchases = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereIn('purchase_items.product_id', $productIds)
            ->whereBetween('purchases.purchase_date', [$startDate, $endDate])
            ->select('purchase_items.product_id', 'purchase_items.variant_id', DB::raw('SUM(purchase_items.qty) as total_qty'))
            ->groupBy('purchase_items.product_id', 'purchase_items.variant_id')
            ->get();

        $productions = DB::table('production_entry_items')
            ->join('production_entries', 'production_entries.id', '=', 'production_entry_items.production_entry_id')
            ->whereIn('production_entry_items.product_id', $productIds)
            ->whereDate('production_entries.production_date', '>=', $startDate)
            ->whereDate('production_entries.production_date', '<=', $endDate)
            ->select('production_entry_items.product_id', 'production_entry_items.variant_id', DB::raw('SUM(production_entry_items.qty_stock) as total_qty'))
            ->groupBy('production_entry_items.product_id', 'production_entry_items.variant_id')
            ->get();

        $purchaseReturns = DB::table('purchase_return_items')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->whereIn('purchase_return_items.product_id', $productIds)
            ->whereBetween('purchase_returns.return_date', [$startDate, $endDate])
            ->select('purchase_return_items.product_id', DB::raw('SUM(purchase_return_items.qty) as total_qty'))
            ->groupBy('purchase_return_items.product_id')
            ->get();

        $currStocks = DB::table('stocks')
            ->whereIn('product_id', $productIds)
            ->select('product_id', 'variant_id', 'qty')
            ->get();

        // Mapping helper: key = pid_vid (use 0 for null variant)
        $mapP = []; foreach($purchases as $p) { $mapP[$p->product_id . '_' . ($p->variant_id ?? 0)] = $p->total_qty; }
        $mapProd = []; foreach($productions as $pd) { $mapProd[$pd->product_id . '_' . ($pd->variant_id ?? 0)] = ($mapProd[$pd->product_id . '_' . ($pd->variant_id ?? 0)] ?? 0) + $pd->total_qty; }
        // purchase_return_items has no variant_id — map by product only (key pid_0)
        $mapPR = []; foreach($purchaseReturns as $pr) { $mapPR[$pr->product_id . '_0'] = ($mapPR[$pr->product_id . '_0'] ?? 0) + $pr->total_qty; }
        $mapS = []; foreach($currStocks as $cs) { $mapS[$cs->product_id . '_' . ($cs->variant_id ?? 0)] = $cs->qty; }

        // Sales processing (by product and variant) with DATE filter
        $hasVariantIdInSales = \Illuminate\Support\Facades\Schema::hasColumn('sales', 'variant_id');
        $allSalesQuery = DB::table('sales')->whereBetween('created_at', [$startDT, $endDT])->whereNotNull('product')->select('product', 'qty');
        if ($hasVariantIdInSales) {
            $allSalesQuery->addSelect('variant_id');
        }
        $allSales = $allSalesQuery->get();

        $soldMap = [];
        foreach ($allSales as $s) {
            $pids = explode(',', $s->product);
            $qtys = explode(',', $s->qty);
            $vids = $hasVariantIdInSales ? explode(',', $s->variant_id ?? '') : [];
            
            foreach ($pids as $idx => $pid) {
                $pid = trim($pid);
                if ($pid === '') continue;
                $vid = trim($vids[$idx] ?? '0');
                if ($vid === '') $vid = '0';
                $key = $pid . '_' . $vid;
                $soldMap[$key] = ($soldMap[$key] ?? 0) + floatval($qtys[$idx] ?? 0);
            }
        }

        $hasVariantIdInReturns = \Illuminate\Support\Facades\Schema::hasColumn('sales_returns', 'variant_id');
        $allReturnsQuery = DB::table('sales_returns')->whereBetween('created_at', [$startDT, $endDT])->whereNotNull('product')->select('product', 'qty');
        if ($hasVariantIdInReturns) {
            $allReturnsQuery->addSelect('variant_id');
        }
        $allReturns = $allReturnsQuery->get();

        $retMap = [];
        foreach ($allReturns as $r) {
            $pids = explode(',', $r->product);
            $qtys = explode(',', $r->qty);
            $vids = $hasVariantIdInReturns ? explode(',', $r->variant_id ?? '') : [];
            
            foreach ($pids as $idx => $pid) {
                $pid = trim($pid);
                if ($pid === '') continue;
                $vid = trim($vids[$idx] ?? '0');
                if ($vid === '') $vid = '0';
                $key = $pid . '_' . $vid;
                $retMap[$key] = ($retMap[$key] ?? 0) + floatval($qtys[$idx] ?? 0);
            }
        }

        // 3. Transactions AFTER end_date to perform backward calculation for Balance
        $purchAfter = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereIn('purchase_items.product_id', $productIds)
            ->where('purchases.purchase_date', '>', $endDate)
            ->select('purchase_items.product_id', 'purchase_items.variant_id', DB::raw('SUM(purchase_items.qty) as total_qty'))
            ->groupBy('purchase_items.product_id', 'purchase_items.variant_id')
            ->get();
        $mapPAft = []; foreach($purchAfter as $p) { $mapPAft[$p->product_id . '_' . ($p->variant_id ?? '0')] = $p->total_qty; }

        $prodAfter = DB::table('production_entry_items')
            ->join('production_entries', 'production_entries.id', '=', 'production_entry_items.production_entry_id')
            ->whereIn('production_entry_items.product_id', $productIds)
            ->whereDate('production_entries.production_date', '>', $endDate)
            ->select('production_entry_items.product_id', 'production_entry_items.variant_id', DB::raw('SUM(production_entry_items.qty_stock) as total_qty'))
            ->groupBy('production_entry_items.product_id', 'production_entry_items.variant_id')
            ->get();
        $mapProdAft = []; foreach($prodAfter as $pd) { $mapProdAft[$pd->product_id . '_' . ($pd->variant_id ?? 0)] = ($mapProdAft[$pd->product_id . '_' . ($pd->variant_id ?? 0)] ?? 0) + $pd->total_qty; }

        $prAfter = DB::table('purchase_return_items')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->whereIn('purchase_return_items.product_id', $productIds)
            ->where('purchase_returns.return_date', '>', $endDate)
            ->select('purchase_return_items.product_id', DB::raw('SUM(purchase_return_items.qty) as total_qty'))
            ->groupBy('purchase_return_items.product_id')
            ->get();
        $mapPRAft = []; foreach($prAfter as $pr) { $mapPRAft[$pr->product_id . '_0'] = ($mapPRAft[$pr->product_id . '_0'] ?? 0) + $pr->total_qty; }

        $allSalesAfterQ = DB::table('sales')->where('created_at', '>', $endDT)->whereNotNull('product')->select('product', 'qty');
        if ($hasVariantIdInSales) { $allSalesAfterQ->addSelect('variant_id'); }
        $allSalesAfter = $allSalesAfterQ->get();
        
        $soldAftMap = [];
        foreach ($allSalesAfter as $s) {
            $pids = explode(',', $s->product); $qtys = explode(',', $s->qty); $vids = $hasVariantIdInSales ? explode(',', $s->variant_id ?? '') : [];
            foreach ($pids as $idx => $pid) {
                $pid = trim($pid); if ($pid === '') continue;
                $vid = trim($vids[$idx] ?? '0'); if ($vid === '') $vid = '0';
                $key = $pid . '_' . $vid;
                $soldAftMap[$key] = ($soldAftMap[$key] ?? 0) + floatval($qtys[$idx] ?? 0);
            }
        }

        $allRetAfterQ = DB::table('sales_returns')->where('created_at', '>', $endDT)->whereNotNull('product')->select('product', 'qty');
        if ($hasVariantIdInReturns) { $allRetAfterQ->addSelect('variant_id'); }
        $allRetAfter = $allRetAfterQ->get();
        
        $retAftMap = [];
        foreach ($allRetAfter as $r) {
            $pids = explode(',', $r->product); $qtys = explode(',', $r->qty); $vids = $hasVariantIdInReturns ? explode(',', $r->variant_id ?? '') : [];
            foreach ($pids as $idx => $pid) {
                $pid = trim($pid); if ($pid === '') continue;
                $vid = trim($vids[$idx] ?? '0'); if ($vid === '') $vid = '0';
                $key = $pid . '_' . $vid;
                $retAftMap[$key] = ($retAftMap[$key] ?? 0) + floatval($qtys[$idx] ?? 0);
            }
        }

        // ---- STOCK ADJUSTMENTS within date range ----
        $adjInRange = DB::table('stock_adjustment_items as sai')
            ->join('stock_adjustments as sa', 'sa.id', '=', 'sai.adjustment_id')
            ->whereIn('sai.product_id', $productIds)
            ->whereBetween('sa.adjustment_date', [$startDate, $endDate])
            ->select('sai.product_id', 'sa.type', DB::raw('SUM(sai.qty_stock) as total_qty'))
            ->groupBy('sai.product_id', 'sa.type')
            ->get();

        $mapAdjInc = []; // increase within range
        $mapAdjDec = []; // decrease within range
        foreach ($adjInRange as $adj) {
            $k = $adj->product_id . '_0';
            if ($adj->type === 'increase') {
                $mapAdjInc[$k] = ($mapAdjInc[$k] ?? 0) + $adj->total_qty;
            } else {
                $mapAdjDec[$k] = ($mapAdjDec[$k] ?? 0) + $adj->total_qty;
            }
        }

        // ---- STOCK ADJUSTMENTS AFTER end_date ----
        $adjAfter = DB::table('stock_adjustment_items as sai')
            ->join('stock_adjustments as sa', 'sa.id', '=', 'sai.adjustment_id')
            ->whereIn('sai.product_id', $productIds)
            ->where('sa.adjustment_date', '>', $endDate)
            ->select('sai.product_id', 'sa.type', DB::raw('SUM(sai.qty_stock) as total_qty'))
            ->groupBy('sai.product_id', 'sa.type')
            ->get();

        $mapAdjIncAft = [];
        $mapAdjDecAft = [];
        foreach ($adjAfter as $adj) {
            $k = $adj->product_id . '_0';
            if ($adj->type === 'increase') {
                $mapAdjIncAft[$k] = ($mapAdjIncAft[$k] ?? 0) + $adj->total_qty;
            } else {
                $mapAdjDecAft[$k] = ($mapAdjDecAft[$k] ?? 0) + $adj->total_qty;
            }
        }

        $rows = [];
        $grandTotalValue = 0;

        foreach ($products as $p) {
            $is_kg = $p->unit_type === 'kg';

            if (!$is_kg && $p->variants->count() > 0) {
                foreach ($p->variants as $v) {
                    $key = $p->id . '_' . $v->id;
                    $code = $p->item_code; 
                    
                    $purchased = (float)($mapP[$key] ?? 0);
                    $produced  = (float)($mapProd[$key] ?? 0);
                    
                    // If this is the default (or first) variant, also include base product productions (without variant ID)
                    if ($v->is_default || $p->variants->first()->id == $v->id) {
                        $produced += (float)($mapProd[$p->id . '_0'] ?? 0);
                    }

                    $pReturn   = (float)($mapPR[$p->id . '_0'] ?? 0); 
                    if ($pReturn == 0) { }
                    $sold      = (float)($soldMap[$key] ?? 0);
                    $sReturn   = (float)($retMap[$key] ?? 0);
                    $balance   = (float)($mapS[$key] ?? 0);
                    
                    // If this is default variant, also include base product stock if any
                    if ($v->is_default || $p->variants->first()->id == $v->id) {
                        $balance += (float)($mapS[$p->id . '_0'] ?? 0);
                    }

                    $adjInc    = (float)($mapAdjInc[$p->id . '_0'] ?? 0);
                    $adjDec    = (float)($mapAdjDec[$p->id . '_0'] ?? 0);

                    $purchAft = (float)($mapPAft[$key] ?? 0);
                    $prodAft  = (float)($mapProdAft[$key] ?? 0); 
                    if ($v->is_default || $p->variants->first()->id == $v->id) {
                        $prodAft += (float)($mapProdAft[$p->id . '_0'] ?? 0);
                    }
                    $prAft    = (float)($mapPRAft[$p->id . '_0'] ?? 0);
                    $soldAft  = (float)($soldAftMap[$key] ?? 0);
                    $sRetAft  = (float)($retAftMap[$key] ?? 0);
                    $adjIncAft = (float)($mapAdjIncAft[$p->id . '_0'] ?? 0);
                    $adjDecAft = (float)($mapAdjDecAft[$p->id . '_0'] ?? 0);
                    
                    $closingStock = $balance - $purchAft - $prodAft - $sRetAft + $soldAft + $prAft - $adjIncAft + $adjDecAft;
                    $openingStock = $closingStock - $purchased - $produced - $sReturn + $sold + $pReturn - $adjInc + $adjDec;

                    $rows[] = [
                        'item_code'       => $code,
                        'item_name'       => $p->item_name . ' (' . ($v->size_label ?: $v->variant_name) . ')',
                        'is_kg'           => false,
                        'initial_stock'   => $openingStock,
                        'produced'        => $produced,
                        'purchased'       => $purchased,
                        'purchase_return' => $pReturn,
                        'adj_increase'    => $adjInc,
                        'adj_decrease'    => $adjDec,
                        'sold'            => $sold,
                        'sale_return'     => $sReturn,
                        'balance'         => $closingStock,
                        'unit'            => $p->unit->name ?? ($is_kg ? 'KG' : 'PC'),
                    ];
                    $grandTotalValue += $closingStock * (float)($v->wholesale_price ?: $p->wholesale_price);
                }
            } else {
                // Base product only or combined KG product
                $key = $p->id . '_0';
                $code = $p->item_code;

                $purchased_kg = (float)($mapP[$key] ?? 0);
                $produced  = (float)($mapProd[$key] ?? 0); // already in grams from qty_stock
                $pReturn   = (float)($mapPR[$p->id . '_0'] ?? 0);
                $balance   = (float)($mapS[$key] ?? 0); // already in grams

                // For KG products: purchased qty in purchase_items is in KG → convert to grams
                // For KG products: sold qty in sales is in KG → convert to grams
                $rawSold    = (float)($soldMap[$key] ?? 0);
                $rawSReturn = (float)($retMap[$key] ?? 0);
                
                $purchAft_kg = (float)($mapPAft[$key] ?? 0);
                $prodAft     = (float)($mapProdAft[$key] ?? 0); 
                $prAft       = (float)($mapPRAft[$p->id . '_0'] ?? 0);
                $rawSoldAft  = (float)($soldAftMap[$key] ?? 0);
                $rawSRetAft  = (float)($retAftMap[$key] ?? 0);

                if ($is_kg) {
                    $purchased = $purchased_kg * 1000;
                    $sold      = $rawSold * 1000;
                    $sReturn   = $rawSReturn * 1000;

                    $purchAft = $purchAft_kg * 1000;
                    $soldAft  = $rawSoldAft * 1000;
                    $sRetAft  = $rawSRetAft * 1000;
                } else {
                    $purchased = $purchased_kg;
                    $sold      = $rawSold;
                    $sReturn   = $rawSReturn;

                    $purchAft = $purchAft_kg;
                    $soldAft  = $rawSoldAft;
                    $sRetAft  = $rawSRetAft;
                }

                if ($is_kg && $p->variants->count() > 0) {
                    // Accumulate all variants into base for KG items
                    foreach ($p->variants as $v) {
                        $vKey = $p->id . '_' . $v->id;
                        $mul = floatval($v->size_value); 
                        
                        // If variant size is in KG, multiply by 1000 to get grams
                        if ($v->size_unit === 'kg') {
                            $mul *= 1000;
                        }

                        $vPurchRaw = (float)($mapP[$vKey] ?? 0);
                        $vPurchAftRaw = (float)($mapPAft[$vKey] ?? 0);

                        // Assuming purchase qty for KG items is in KG
                        $purchased += $vPurchRaw * 1000;
                        $purchAft  += $vPurchAftRaw * 1000;

                        $produced  += (float)($mapProd[$vKey] ?? 0);
                        $prodAft   += (float)($mapProdAft[$vKey] ?? 0);

                        $pReturn   += (float)($mapPR[$vKey] ?? 0);
                        $prAft     += (float)($mapPRAft[$vKey] ?? 0);

                        $sold      += (float)($soldMap[$vKey] ?? 0) * $mul;
                        $soldAft   += (float)($soldAftMap[$vKey] ?? 0) * $mul;

                        $sReturn   += (float)($retMap[$vKey] ?? 0) * $mul;
                        $sRetAft   += (float)($retAftMap[$vKey] ?? 0) * $mul;

                        $balance   += (float)($mapS[$vKey] ?? 0);
                    }
                }

                $adjInc    = (float)($mapAdjInc[$p->id . '_0'] ?? 0);
                $adjDec    = (float)($mapAdjDec[$p->id . '_0'] ?? 0);
                $adjIncAft = (float)($mapAdjIncAft[$p->id . '_0'] ?? 0);
                $adjDecAft = (float)($mapAdjDecAft[$p->id . '_0'] ?? 0);

                $closingStock = $balance - $purchAft - $prodAft - $sRetAft + $soldAft + $prAft - $adjIncAft + $adjDecAft;
                $openingStock = $closingStock - $purchased - $produced - $sReturn + $sold + $pReturn - $adjInc + $adjDec;

                $rows[] = [
                    'item_code'       => $code,
                    'item_name'       => $p->item_name,
                    'is_kg'           => $is_kg,
                    'initial_stock'   => $openingStock,
                    'produced'        => $produced,
                    'purchased'       => $purchased,
                    'purchase_return' => $pReturn,
                    'adj_increase'    => $adjInc,
                    'adj_decrease'    => $adjDec,
                    'sold'            => $sold,
                    'sale_return'     => $sReturn,
                    'balance'         => $closingStock,
                    'unit'            => $p->unit->name ?? ($is_kg ? 'KG' : 'PC'),
                ];
                
                $valuationQty = $is_kg ? ($closingStock / 1000) : $closingStock;
                $grandTotalValue += $valuationQty * (float)$p->wholesale_price;
            }
        }

        return response()->json([
            'data'        => $rows,
            'grand_total' => $grandTotalValue,
            'total'       => count($rows),
        ]);
    }

    /**
     * Variant / Size-wise Stock Report
     * Returns all variants grouped under their parent product.
     */
    public function fetchVariantStock(Request $request)
    {
        $searchQ   = trim($request->get('q', ''));
        $productId = $request->get('product_id', 'all');

        $query = DB::table('product_variants')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select(
                'products.id          as product_id',
                'products.item_code   as item_code',
                'products.item_name   as product_name',
                'categories.name      as category',
                'product_variants.id           as variant_id',
                'product_variants.size_label   as size_label',
                'product_variants.variant_name as variant_name',
                'product_variants.size_value   as size_value',
                'product_variants.size_unit    as size_unit',
                'product_variants.price        as price',
                'product_variants.stock_qty    as stock_qty',
                'product_variants.alert_quantity as alert_qty',
                'product_variants.is_default   as is_default'
            )
            ->where('product_variants.is_active', true)
            ->orderBy('products.item_name')
            ->orderBy('product_variants.size_value');

        if ($productId && $productId !== 'all') {
            $query->where('products.id', $productId);
        }

        if ($searchQ !== '') {
            $query->where(function($q) use ($searchQ) {
                $q->where('products.item_name', 'like', "%{$searchQ}%")
                  ->orWhere('products.item_code', 'like', "%{$searchQ}%")
                  ->orWhere('product_variants.size_label', 'like', "%{$searchQ}%");
            });
        }

        $variants = $query->get();
        $variantIds = $variants->pluck('variant_id')->toArray();
        $stocks = DB::table('stocks')
            ->whereIn('variant_id', $variantIds)
            ->pluck('qty', 'variant_id');

        // Group by product
        $grouped = [];
        foreach ($variants as $v) {
            $pid = $v->product_id;
            if (!isset($grouped[$pid])) {
                $grouped[$pid] = [
                    'product_id'   => $pid,
                    'item_code'    => $v->item_code,
                    'product_name' => $v->product_name,
                    'category'     => $v->category ?? '–',
                    'total_stock'  => 0,
                    'sizes'        => [],
                ];
            }
            $label = $v->size_label ?: $v->variant_name ?: ('Size ' . $v->size_value . ' ' . $v->size_unit);
            
            // Override with actual stocks table
            $stock = isset($stocks[$v->variant_id]) ? (float)$stocks[$v->variant_id] : (float)$v->stock_qty;
            
            $grouped[$pid]['sizes'][] = [
                'variant_id'  => $v->variant_id,
                'label'       => $label,
                'price'       => (float)$v->price,
                'stock_qty'   => $stock,
                'alert_qty'   => (int)$v->alert_qty,
                'is_default'  => (bool)$v->is_default,
                'status'      => $stock <= 0 ? 'out' : ($stock <= ($v->alert_qty ?: 5) ? 'low' : 'ok'),
            ];
            $grouped[$pid]['total_stock'] += $stock;
        }

        return response()->json([
            'data'  => array_values($grouped),
            'total' => count($grouped),
        ]);
    }


    public function purchase_report()
    {
        return view('admin_panel.reporting.purchase_report');
    }

    public function fetchPurchaseReport(Request $request)
    {
        $startDate = $request->start_date;
        $endDate   = $request->end_date;

        /* ================= NORMAL PURCHASE ================= */
        $purchaseQuery = DB::table('purchases')
            ->leftJoin('purchase_items', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->leftJoin('products', 'purchase_items.product_id', '=', 'products.id')
            ->leftJoin('vendors', 'purchases.vendor_id', '=', 'vendors.id')
            ->select(
                DB::raw("'purchase' as source_type"),
                'purchases.purchase_date as purchase_date',
                'purchases.invoice_no',
                'vendors.name as vendor_name',
                'products.item_code',
                'products.item_name',
                'purchase_items.qty',
                'purchase_items.unit',
                'purchase_items.price',
                'purchase_items.item_discount',
                'purchase_items.line_total',
                'purchases.subtotal',
                'purchases.discount',
                'purchases.extra_cost',
                'purchases.net_amount',
                'purchases.paid_amount',
                'purchases.due_amount'
            );

        if ($startDate && $endDate) {
            $purchaseQuery->whereBetween('purchases.purchase_date', [$startDate, $endDate]);
        }

        /* ================= INWARD AS PURCHASE ================= */
        $inwardQuery = DB::table('inward_gatepasses')
            ->leftJoin('inward_gatepass_items', 'inward_gatepasses.id', '=', 'inward_gatepass_items.inward_gatepass_id')
            ->leftJoin('products', 'inward_gatepass_items.product_id', '=', 'products.id')
            ->leftJoin('vendors', 'inward_gatepasses.vendor_id', '=', 'vendors.id')
            ->where('inward_gatepasses.status', 'linked')
            ->where('inward_gatepasses.bill_status', 'billed')
            ->select(
                DB::raw("'inward' as source_type"),
                'inward_gatepasses.gatepass_date as purchase_date',
                'inward_gatepasses.invoice_no',
                'vendors.name as vendor_name',
                'products.item_code',
                'products.item_name',
                'inward_gatepass_items.qty',
                DB::raw('products.unit_id as unit'),
                // Use the specific transaction price from the item table, not master product price
                DB::raw('COALESCE(inward_gatepass_items.price, products.wholesale_price) as price'),
                DB::raw('0 as item_discount'),
                // Calculate line total using the transaction price (no discount supported on inward items currently)
                DB::raw('(COALESCE(inward_gatepass_items.price, products.wholesale_price) * inward_gatepass_items.qty) as line_total'),
                'inward_gatepasses.subtotal',
                'inward_gatepasses.discount',
                'inward_gatepasses.extra_cost',
                'inward_gatepasses.net_amount',
                'inward_gatepasses.paid_amount',
                'inward_gatepasses.due_amount'
            );


        if ($startDate && $endDate) {
            $inwardQuery->whereBetween('gatepass_date', [$startDate, $endDate]);
        }

        /* ================= UNION ================= */
        $data = $purchaseQuery
            ->unionAll($inwardQuery)
            ->get()
            ->sort(function($a, $b) {
                if ($a->purchase_date === $b->purchase_date) {
                    // Secondary: invoice_no desc
                    return strcmp($b->invoice_no, $a->invoice_no);
                }
                // Primary: purchase_date desc
                return strcmp($b->purchase_date, $a->purchase_date);
            })
            ->values();

        // 🔹 Post-processing to remove duplicate invoice totals
        $seenInvoices = [];
        foreach ($data as $row) {
            $uniqueKey = $row->source_type . '_' . $row->invoice_no;

            if (in_array($uniqueKey, $seenInvoices)) {
                // Secondary item: Zero out invoice-level totals to prevent double counting
                $row->subtotal = 0;
                $row->discount = 0;
                $row->extra_cost = 0;
                $row->net_amount = 0;
                $row->paid_amount = 0;
                $row->due_amount = 0;
                $row->is_duplicate = true; 
            } else {
                // First item: Keep totals
                $seenInvoices[] = $uniqueKey;
                $row->is_duplicate = false;
            }
        }

        return response()->json([
            'data' => $data
        ]);
    }



    public function sale_report()
    {
        return view('admin_panel.reporting.sale_report');
    }

    public function fetchsaleReport(Request $request)
    {
        if ($request->ajax()) {
            $start = $request->start_date;
            $end = $request->end_date;

            $query = DB::table('sales')
                ->leftJoin('customers', 'sales.customer', '=', 'customers.id')
                ->select(
                    'sales.id',
                    'sales.invoice_no', // ✅ Select invoice_no specifically
                    'sales.reference',
                    'sales.product',
                    'sales.variant_id',
                    'sales.product_code',
                    'sales.brand',
                    'sales.unit',
                    'sales.per_price',
                    'sales.per_discount',
                    'sales.qty',
                    'sales.per_total',
                    'sales.total_net',
                    'sales.created_at',
                    'customers.customer_name',
                    'sales.unit' // Add unit
                );

            if ($start && $end) {
                // Precise Date Filtering (Start 00:00:00 to End 23:59:59)
                $query->whereBetween('sales.created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            }

            // Filter by Customer Type/Category
            if ($request->has('customer_type')) {
                $types = $request->customer_type;

                // Ensure types is an array and not empty
                if (is_array($types) && count($types) > 0) {
                     $query->where(function($q) use ($types) {
                        
                        // 1. Handle Literal "Walk-in Customer" (Not a registered user ID)
                        if (in_array('Walking Customer', $types)) {
                            $q->orWhere('sales.customer', 'Walk-in Customer');
                        }

                        // 2. Filter by Category (Strictly Category, Ignore Type)
                        // This excludes customers like ID 12 who are Wholesalers but have Type='Walking Customer'
                        $q->orWhereIn('customers.customer_category', $types);
                     });
                }
            } else {
                // Default: Show 'Walk-in Customer' literal and 'Walking Customer' category
                 $query->where(function($q) {
                     $q->where('sales.customer', 'Walk-in Customer')
                       ->orWhere('customers.customer_category', 'Walking Customer');
                 });
            }

            $sales = $query->orderBy('sales.created_at', 'asc')->get();

            // 1. Collect all product and variant IDs for bulk fetching
            $allProdIds = [];
            $allVarIds  = [];
            foreach ($sales as $sale) {
                if (!empty($sale->product)) {
                    $ids = explode(',', $sale->product);
                    foreach($ids as $id) if(trim($id)) $allProdIds[] = trim($id);
                }
                if (!empty($sale->variant_id)) {
                    $vids = explode(',', $sale->variant_id);
                    foreach($vids as $vid) if(trim($vid)) $allVarIds[] = trim($vid);
                }
            }
            
            $productsDict = \App\Models\Product::whereIn('id', array_unique($allProdIds))->get()->keyBy('id');
            $variantsDict = \App\Models\ProductVariant::whereIn('id', array_unique($allVarIds))->get()->keyBy('id');

            foreach ($sales as $sale) {
                // --- Process Products, Variants and Units ---
                if (!empty($sale->product)) {
                    $pIds = explode(',', $sale->product);
                    $vIds = !empty($sale->variant_id) ? explode(',', $sale->variant_id) : [];
                    $qArr = explode(',', $sale->qty);
                    $prArr = explode(',', $sale->per_price);
                    
                    $orderedNames = [];
                    $orderedUnits = [];
                    $modifiedQtys = [];
                    $modifiedPrices = [];
                    
                    foreach ($pIds as $idx => $pid) {
                        $pid = trim($pid);
                        $p = $productsDict->get($pid);
                        $name = $p ? $p->item_name : '-';
                        $unit = $p ? strtoupper($p->unit_type ?? 'Piece') : '-';
                        $qty = isset($qArr[$idx]) ? floatval($qArr[$idx]) : 0;
                        $price = isset($prArr[$idx]) ? floatval($prArr[$idx]) : 0;
                        
                        $vid = isset($vIds[$idx]) ? trim($vIds[$idx]) : null;
                        if ($vid && $variantsDict->has($vid)) {
                            $v = $variantsDict->get($vid);
                            $name .= ' (' . ($v->size_label ?: $v->variant_name) . ')';
                            
                            // Check if we should convert to weight-based display
                            if ($p && strtolower($p->unit_type) === 'kg' && $v->size_value > 0) {
                                $multiplier = 1;
                                $sUnit = strtolower($v->size_unit ?? 'kg');
                                if ($sUnit === 'kg') {
                                    $multiplier = floatval($v->size_value);
                                } elseif ($sUnit === 'gm' || $sUnit === 'grams' || $sUnit === 'gram') {
                                    $multiplier = floatval($v->size_value) / 1000;
                                }
                                
                                // Convert display values: 2 boxes of 0.250kg -> 0.500kg
                                $qty = $qty * $multiplier;
                                // Adjust price to price-per-kg to keep total consistent
                                if ($multiplier > 0) {
                                    $price = $price / $multiplier;
                                }
                                $unit = 'KG';
                            } else {
                                // If not weight-based, use PIECE for variant count
                                $unit = 'PIECE';
                            }
                        }
                        
                        $orderedNames[]  = $name;
                        $orderedUnits[]  = $unit;
                        $modifiedQtys[]  = $qty;
                        $modifiedPrices[] = $price;
                    }

                    $sale->product_names = implode('|', $orderedNames);
                    $sale->unit          = implode('|', $orderedUnits);
                    $sale->qty           = implode(',', $modifiedQtys);
                    $sale->per_price     = implode(',', $modifiedPrices);
                } else {
                    $sale->product_names = '-';
                    $sale->unit          = '-';
                }

                // --- Merge Sale Returns ---
                $returnsRaw = DB::table('sales_returns')
                    ->where('sale_id', $sale->id)
                    ->get();
                
                $parsedReturns = [];
                foreach ($returnsRaw as $ret) {
                    $rProducts = explode(',', $ret->product ?? '');
                    $rQtys     = explode(',', $ret->qty ?? '');
                    $rTotals   = explode(',', $ret->per_total ?? ''); // or use per_price * qty if needed

                    foreach ($rProducts as $idx => $rProd) {
                        $q = isset($rQtys[$idx]) ? floatval($rQtys[$idx]) : 0;
                        $t = isset($rTotals[$idx]) ? floatval($rTotals[$idx]) : 0;
                        if ($q > 0) {
                            $parsedReturns[] = [
                                'product' => trim($rProd),
                                'qty'     => $q,
                                'amount'  => $t
                            ];
                        }
                    }
                }
                $sale->returns = $parsedReturns;
            }


            return response()->json($sales);
        }

        return view('admin_panel.reporting.sale_report');
    }



    public function sale_report_category()
    {
        $categories = Category::select('id', 'name')->get();
        return view('admin_panel.reporting.sale_report_category', compact('categories'));
    }

    public function fetchsalecategoryReport(Request $request)
    {
        if ($request->ajax()) {

            $start      = $request->start_date;
            $end        = $request->end_date;
            $categoryId = $request->category_id;
            $subCategoryId = $request->subcategory_id; // Get subcategory ID

            // ================== BASE SALES QUERY ==================
            $query = DB::table('sales')
                ->leftJoin('customers', 'sales.customer', '=', 'customers.id')
                ->select(
                    'sales.id',
                    'sales.invoice_no',
                    'sales.reference',
                    'sales.product',
                    'sales.variant_id',
                    'sales.product_code',
                    'sales.brand',
                    'sales.unit',
                    'sales.per_price',
                    'sales.per_discount',
                    'sales.qty',
                    'sales.per_total',
                    'sales.total_net',
                    'sales.created_at',
                    'customers.customer_name'
                )
                ->when($start && $end, function ($q) use ($start, $end) {
                     $q->whereBetween('sales.created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
                });

            // ================== CUSTOMER FILTERING ==================
            if ($request->has('customer_type')) {
                $types = $request->customer_type;
                if (is_array($types) && count($types) > 0) {
                     $query->where(function($q) use ($types) {
                        
                        // 1. Literal "Walk-in Customer"
                        if (in_array('Walking Customer', $types)) {
                            $q->orWhere('sales.customer', 'Walk-in Customer');
                        }
                        // 2. Category Match
                        $q->orWhereIn('customers.customer_category', $types);
                     });
                }
            } else {
                // Default: Walking
                 $query->where(function($q) {
                     $q->where('sales.customer', 'Walk-in Customer')
                       ->orWhere('customers.customer_category', 'Walking Customer');
                 });
            }

            $sales = $query->orderBy('sales.created_at', 'asc')->get();

            $finalSales = [];

            // 1. Collect all variant IDs for bulk fetching (products are already fetched per-sale or could be bulked too)
            $allVarIds = [];
            foreach ($sales as $sale) {
                if (!empty($sale->variant_id)) {
                    foreach(explode(',', $sale->variant_id) as $vid) if(trim($vid)) $allVarIds[] = trim($vid);
                }
            }
            $variantsDict = \App\Models\ProductVariant::whereIn('id', array_unique($allVarIds))->get()->keyBy('id');

            foreach ($sales as $sale) {
                if (empty($sale->product)) continue;

                $pIds     = explode(',', $sale->product);
                $vIds     = !empty($sale->variant_id) ? explode(',', $sale->variant_id) : [];
                $qtyArr   = explode(',', $sale->qty);
                $priceArr = explode(',', $sale->per_price);
                $totalArr = explode(',', $sale->per_total);

                // ================== PRODUCTS QUERY ==================
                // Filter products by category/subcategory if requested
                $productsQuery = DB::table('products')
                    ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                    ->leftJoin('subcategories', 'products.sub_category_id', '=', 'subcategories.id')
                    ->whereIn('products.id', $pIds)
                    ->select('products.id','products.item_name','products.unit_type','categories.name as category_name','subcategories.name as subcategory_name');
                
                if ($categoryId)    $productsQuery->where('products.category_id', $categoryId);
                if ($subCategoryId) $productsQuery->where('products.sub_category_id', $subCategoryId);
                
                $matchedProducts = $productsQuery->get()->keyBy('id');

                if ($matchedProducts->isEmpty()) continue;

                $finalNames   = [];
                $finalCats    = [];
                $finalSubCats = [];
                $finalQtys    = [];
                $finalPrices  = [];
                $finalTotals  = [];
                $finalUnits   = [];

                // Iterate through the comma-separated arrays to maintain order and handle multiple instances/variants
                foreach ($pIds as $index => $pid) {
                    $pid = trim($pid);
                    if (!$matchedProducts->has($pid)) continue;

                    $product = $matchedProducts->get($pid);
                    $name    = $product->item_name;
                    $unit    = strtoupper($product->unit_type ?? 'Piece');
                    $qty     = (float)($qtyArr[$index] ?? 0);
                    $price   = (float)($priceArr[$index] ?? 0);
                    
                    $vid = isset($vIds[$index]) ? trim($vIds[$index]) : null;
                    if ($vid && $variantsDict->has($vid)) {
                        $v = $variantsDict->get($vid);
                        $name .= ' (' . ($v->size_label ?: $v->variant_name) . ')';
                        
                        // Check weight-based conversion
                        if ($product && strtolower($product->unit_type) === 'kg' && $v->size_value > 0) {
                            $multiplier = 1;
                            $sUnit = strtolower($v->size_unit ?? 'kg');
                            if ($sUnit === 'kg') {
                                $multiplier = floatval($v->size_value);
                            } elseif ($sUnit === 'gm' || $sUnit === 'grams' || $sUnit === 'gram') {
                                $multiplier = floatval($v->size_value) / 1000;
                            }
                            
                            $qty = $qty * $multiplier;
                            if ($multiplier > 0) {
                                $price = $price / $multiplier;
                            }
                            $unit = 'KG';
                        } else {
                            $unit = 'PIECE';
                        }
                    }

                    $finalNames[]   = $name;
                    $finalCats[]    = $product->category_name ?? '-';
                    $finalSubCats[] = $product->subcategory_name ?? '-';
                    $finalQtys[]    = $qty;
                    $finalPrices[]  = $price;
                    $finalTotals[]  = (float)($totalArr[$index] ?? 0);
                    $finalUnits[]   = $unit;
                }

                if (empty($finalNames)) continue;

                $sale->product_names  = implode('|', $finalNames);
                $sale->categories     = implode(', ', array_unique($finalCats));
                $sale->subcategories  = implode(', ', array_unique($finalSubCats));
                $sale->filtered_qty   = implode('|', $finalQtys);
                $sale->filtered_price = implode('|', $finalPrices);
                $sale->filtered_total = implode('|', $finalTotals);
                $sale->filtered_unit  = implode('|', $finalUnits); 
                $sale->filtered_net   = array_sum($finalTotals);

                $sale->returns = DB::table('sales_returns')->where('sale_id', $sale->id)->get();
                $finalSales[] = $sale;
            }

            return response()->json($finalSales);
        }

        return view('admin_panel.reporting.sale_report_category'); // Should not be reached if handled by first method, but keeping safe
    }



    public function customer_ledger_report()
    {
        $customers = DB::table('customers')->select('id', 'customer_name')->get();

        return view('admin_panel.reporting.customer_ledger_report', compact('customers'));
    }

    public function fetch_customer_ledger(Request $request)
    {
        $customerId = $request->customer_id;
        $start = $request->start_date;
        $end = $request->end_date . ' 23:59:59';

        // Customer info
        $customer = DB::table('customers')->where('id', $customerId)->first();

        // ---------------- CALCULATE OPENING BALANCE DYNAMICALLY ----------------
        // 1. Initial Opening from Customer
        $initial = $customer->opening_balance ?? 0;

        // 2. Prior Sales (Net of Returns as stored in DB)
        $prevSales = DB::table('sales')
            ->where('customer', $customerId)
            ->where('created_at', '<', $start)
            ->sum(DB::raw('COALESCE(total_net, total_bill_amount)'));

        // 3. Prior Payments
        $prevPayments = DB::table('customer_payments')
            ->where('customer_id', $customerId)
            ->where('payment_date', '<', $start)
            ->sum('amount');

        // 4. Returns that happened ON/AFTER StartDate, but belong to Prior Sales.
        // These need to be added back because the Prior Sales Sum is already reduced by them,
        // but the credit event hasn't happened yet in this timeline.
        $addBackReturns = DB::table('sales_returns')
            ->join('sales', 'sales_returns.sale_id', '=', 'sales.id')
            ->where('sales_returns.customer', $customerId)
            ->where('sales_returns.created_at', '>=', $start)
            ->where('sales.created_at', '<', $start)
            ->sum('sales_returns.total_net');

        $opening = $initial + $prevSales - $prevPayments + $addBackReturns;

        // ---------------- FETCH ALL SALE RETURNS FIRST ----------------
        $allSaleReturns = DB::table('sales_returns')
            ->where('customer', $customerId)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy('sale_id'); // group by sale_id for easy lookup

        // ---------------- SALES (Debit) ----------------
        $sales = DB::table('sales')
            ->where('customer', $customerId)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->map(function ($s) use ($allSaleReturns) {
                $fullSaleAmount = $s->total_net ?? $s->total_bill_amount;

                // Check if this sale has any returns
                $returnTotal = 0;
                if (isset($allSaleReturns[$s->id])) {
                    $returnTotal = $allSaleReturns[$s->id]->sum('total_net');
                }

                // Sale debit = original sale + total of related returns
                $debitAmount = $fullSaleAmount + $returnTotal;

                return [
                    'date' => $s->created_at,
                    'sort_type' => 1,
                    'invoice' => $s->invoice_no,
                    'reference' => $s->reference,
                    'description' => 'To Sale A/c',
                    'debit' => $debitAmount,
                    'credit' => 0,
                    'original_sale_id' => $s->id
                ];
            });

        // ---------------- SALE RETURNS (Credit) ----------------
        $saleReturns = collect();
        foreach ($allSaleReturns as $saleId => $returns) {
            foreach ($returns as $r) {
                $saleReturns->push([
                    'date' => $r->created_at,
                    'sort_type' => 3,
                    'invoice' => 'SR-' . $r->sale_id,
                    'reference' => $r->reference,
                    'description' => 'By Sale Return',
                    'debit' => 0,
                    'credit' => $r->total_net,
                    'original_sale_id' => $r->sale_id
                ]);
            }
        }

        // ---------------- PAYMENTS (Credit) ----------------
        $payments = DB::table('customer_payments')
            ->where('customer_id', $customerId)
            ->whereBetween('payment_date', [$start, $end])
            ->get()
            ->map(function ($p) {
                return [
                    'date' => $p->payment_date . ' 23:59:59',
                    'sort_type' => 2,
                    'invoice' => $p->received_no,
                    'reference' => $p->payment_method,
                    'description' => $p->note ?? 'Payment Received',
                    'debit' => 0,
                    'credit' => $p->amount,
                ];
            });

        // ---------------- MERGE + SORT ----------------
        $transactions = collect()
            ->merge($sales)
            ->merge($saleReturns)
            ->merge($payments)
            ->sort(function ($a, $b) {
                $dateA = strtotime($a['date']);
                $dateB = strtotime($b['date']);
                if ($dateA != $dateB) return $dateA <=> $dateB;

                // Sale → Sale Return → Payment
                $order = [1 => 1, 3 => 2, 2 => 3];
                return $order[$a['sort_type']] <=> $order[$b['sort_type']];
            })
            ->values()
            ->all();

        // ---------------- RUNNING BALANCE ----------------
        $balance = $opening;
        foreach ($transactions as $key => $t) {
            $balance += $t['debit'];
            $balance -= $t['credit'];
            $transactions[$key]['balance'] = $balance;
        }

        return response()->json([
            'customer' => $customer,
            'opening_balance' => $opening,
            'transactions' => $transactions,
        ]);
    }





    public function vendor_ledger_report()
    {
        $vendors = DB::table('vendors')->select('id', 'name')->get();

        return view('admin_panel.reporting.vendor_ledger_report', compact('vendors'));
    }

    public function fetch_vendor_ledger(Request $request)
    {
        $vendorId = $request->vendor_id;
        $start = $request->start_date;
        $end = $request->end_date . ' 23:59:59';

        $vendor = DB::table('vendors')->where('id', $vendorId)->first();
        // ---------------- CALCULATE OPENING BALANCE DYNAMICALLY ----------------
        // 1. Initial Opening from Vendor
        $initial = $vendor->opening_balance ?? 0;

        // 2. Prior Purchases (Debit: We owe more)
        $prevPurchases = DB::table('purchases')
            ->where('vendor_id', $vendorId)
            ->where('purchase_date', '<', $start)
            ->sum('net_amount');

        // 2b. Prior Inwards (Debit: We owe more)
        $prevInwards = DB::table('inward_gatepasses')
            ->where('vendor_id', $vendorId)
            ->where('bill_status', 'billed')
            ->where('gatepass_date', '<', $start)
            ->sum('net_amount');

        // 3. Prior Returns (Credit: We owe less)
        $prevReturns = DB::table('purchase_returns')
            ->where('vendor_id', $vendorId)
            ->where('return_date', '<', $start)
            ->sum('net_amount');

        // 4. Prior Payments (Credit: We owe less)
        $prevPayments = DB::table('vendor_payments')
            ->where('vendor_id', $vendorId)
            ->where('payment_date', '<', $start)
            ->sum('amount');

        // 5. Prior Bilties (Debit: We owe more)
        $prevBilties = DB::table('vendor_bilties')
            ->where('vendor_id', $vendorId)
            ->where('delivery_date', '<', $start)
            ->sum('amount');

        $opening = $initial + $prevPurchases + $prevInwards + $prevBilties - $prevReturns - $prevPayments;

        // 🔹 1. Purchases → Debit (we owe vendor)
        $purchases = DB::table('purchases')
            ->where('vendor_id', $vendorId)
            ->whereBetween('purchase_date', [$start, $end])
            ->select('purchase_date', 'invoice_no', 'net_amount', 'note') // Explicitly select note
            ->get()
            ->map(function ($p) {
                return [
                    'date' => $p->purchase_date,
                    'invoice' => $p->invoice_no,
                    'description' => $p->note ?: 'Purchase Invoice', // Use note if available
                    'debit' => $p->net_amount,
                    'credit' => 0,
                    'sort_date' => $p->purchase_date
                ];
            });

        // 🔹 1b. Inward Bills → Debit (we owe vendor)
        $inwards = DB::table('inward_gatepasses')
            ->where('vendor_id', $vendorId)
            ->where('bill_status', 'billed')
            ->whereBetween('gatepass_date', [$start, $end])
            ->get()
            ->map(function ($i) {
                return [
                    'date' => $i->gatepass_date,
                    'invoice' => $i->invoice_no . ' (' . $i->gatepass_no . ')',
                    'description' => 'Inward Bill - ' . ($i->remarks ?? ''),
                    'debit' => $i->net_amount,
                    'credit' => 0,
                    'sort_date' => $i->gatepass_date
                ];
            });

        // 🔹 2. Purchase Returns → Credit (reduces vendor balance)
        $returns = DB::table('purchase_returns')
            ->where('vendor_id', $vendorId)
            ->whereBetween('return_date', [$start, $end])
            ->get()
            ->map(function ($r) {
                return [
                    'date' => $r->return_date,
                    'invoice' => $r->return_invoice,
                    'description' => 'Purchase Return',
                    'debit' => 0,
                    'credit' => $r->net_amount,
                    'sort_date' => $r->return_date
                ];
            });

        // 🔹 3. Vendor Payments → Credit (we paid vendor)
        $payments = DB::table('vendor_payments')
            ->where('vendor_id', $vendorId)
            ->whereBetween('payment_date', [$start, $end])
            ->get()
            ->map(function ($v) {
                return [
                    'date' => $v->payment_date,
                    'invoice' => $v->payment_no,
                    'reference' => $v->payment_method,
                    'description' => $v->note ?? 'Cash Given',
                    'debit' => 0,
                    'credit' => $v->amount,
                    'sort_date' => $v->payment_date
                ];
            });

        // 🔹 4. Vendor Bilties → Debit (we owe vendor for freight/logistics)
        $bilties = DB::table('vendor_bilties')
            ->where('vendor_id', $vendorId)
            ->whereBetween('delivery_date', [$start, $end])
            ->get()
            ->map(function ($b) {
                return [
                    'date' => $b->delivery_date,
                    'invoice' => $b->bilty_no ?: 'Bilty',
                    'description' => $b->note ?: 'Bilty Amount',
                    'debit' => $b->amount,
                    'credit' => 0,
                    'sort_date' => $b->delivery_date
                ];
            });

        // 🔹 Merge all
        $transactions = $purchases
            ->merge($inwards)
            ->merge($returns)
            ->merge($payments)
            ->merge($bilties)
            ->sortBy('sort_date')
            ->values()
            ->all();

        // 🔹 Running Balance Calculation (Debit increases, Credit decreases)

        $balance = $opening;

        foreach ($transactions as $key => $t) {

            $debit  = (float) ($t['debit'] ?? 0);
            $credit = (float) ($t['credit'] ?? 0);

            $balance = $balance + $debit - $credit;

            $transactions[$key]['balance'] = round($balance, 2);
        }


        return response()->json([
            'vendor' => $vendor,
            'opening_balance' => $opening,
            'transactions' => $transactions,
        ]);
    }

    public function cashbook(Request $request)
    {
        // ✅ Allow date filtering, default to today
        $selectedDate = $request->get('date', Carbon::today()->toDateString());
        $today = $selectedDate;
        
        // ✅ Cashbook Start Date (transactions before this are ignored)
        // Default: 30 days ago to avoid huge opening balance from old data
        $startDate = $request->get('start_date', Carbon::today()->subDays(30)->toDateString());

        /* ================= CALCULATE OPENING BALANCE ================= */
        // Opening = sum of ALL transactions BETWEEN start_date and selected date (exclusive)
        
        // Sales after start_date but before selected date
        $previousSales = Sale::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<', $today)
            ->sum('total_net');
        
        // Customer recoveries after start_date but before selected date
        $previousCustomerRecoveries = CustomerPayment::whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<', $today)
            ->sum('amount');
        
        // Vendor payments after start_date but before selected date
        $previousVendorPayments = VendorPayment::whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<', $today)
            ->sum('amount');
        
        // Expenses after start_date but before selected date
        $previousExpenses = ExpenseVoucher::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<', $today)
            ->sum('total_amount');
        
        // Opening = Previous Receipts - Previous Payments
        $openingBalance = ($previousSales + $previousCustomerRecoveries) - ($previousVendorPayments + $previousExpenses);

        /* ================= RECEIPTS (Current Date) ================= */

        $receipts = [];

        // ✅ ALL Sales (not just walk-in)
        $allSales = Sale::whereDate('created_at', $today)->get();

        foreach ($allSales as $sale) {
            $receipts[] = [
                'title'  => 'Sale',
                'ref'    => 'Invoice #' . $sale->invoice_no,
                'amount' => $sale->total_net,
            ];
        }

        // ✅ ALL Customer Recoveries (not just cash)
        $customerRecoveries = CustomerPayment::with('customer')
            ->whereDate('payment_date', $today)
            ->get();

        foreach ($customerRecoveries as $pay) {
            $receipts[] = [
                'title'  => 'Customer Recovery',
                'ref'    => ($pay->customer->customer_name ?? '-') . ' (' . ($pay->payment_method ?? 'N/A') . ')',
                'amount' => $pay->amount,
            ];
        }

        $totalReceipts = collect($receipts)->sum('amount');

        /* ================= PAYMENTS (Current Date) ================= */

        $payments = [];

        // ✅ ALL Vendor Payments (not just cash)
        $vendorPayments = VendorPayment::with('vendor')
            ->whereDate('payment_date', $today)
            ->get();

        foreach ($vendorPayments as $pay) {
            $payments[] = [
                'title'  => 'Vendor Payment',
                'ref'    => ($pay->vendor->name ?? '-') . ' (' . ($pay->payment_method ?? 'N/A') . ')',
                'amount' => $pay->amount,
            ];
        }

        // ✅ ALL Expense Vouchers
        $expenseVouchers = ExpenseVoucher::whereDate('date', $today)->get();

        foreach ($expenseVouchers as $exp) {
            $remarks = is_array($exp->remarks) ? implode(', ', $exp->remarks) : ($exp->remarks ?? '');
            $payments[] = [
                'title'  => 'Expense',
                'ref'    => $remarks ?: 'Voucher #' . $exp->evid,
                'amount' => $exp->total_amount,
            ];
        }

        $totalPayments = collect($payments)->sum('amount');

        /* ================= CLOSING BALANCE ================= */

        $closingBalance = $openingBalance + $totalReceipts - $totalPayments;

        // IMPORTANT for blade loop
        $maxRows = max(count($receipts), count($payments));

        return view('admin_panel.reporting.CashBook', compact(
            'receipts',
            'payments',
            'maxRows',
            'totalReceipts',
            'totalPayments',
            'openingBalance',
            'closingBalance',
            'selectedDate',
            'startDate'
        ));
    }




    public function expense_vocher(Request $request)
    {
        $accountHeads = \App\Models\AccountHead::where('status', 1)->get();
        $accounts     = \App\Models\Account::where('status', 1)->get();

        $vouchers = collect();
        $grandTotal = 0;

        if ($request->hasAny(['account_heads', 'accounts', 'start_date', 'end_date'])) {

            $query = \App\Models\ExpenseVoucher::query();
            
            // 🛡️ Restrict non-admin users to their own expenses
            if (auth()->id() !== 1 && !auth()->user()->hasRole('Admin')) {
                $query->where('user_id', auth()->id());
            }

            // Account Head filter (type = account_head_id)
            if ($request->filled('account_heads') && !in_array('all', $request->account_heads)) {
                $query->whereIn('type', $request->account_heads);
            }

            // Account filter (party_id = account_id)
            if ($request->filled('accounts')) {
                $query->whereIn('party_id', $request->accounts);
            }

            // Date filter
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            $vouchers = $query->latest()->get();

            // Grand Total
            $grandTotal = $vouchers->sum('total_amount');
        }

        return view(
            'admin_panel.reporting.expense_vocher',
            compact('accountHeads', 'accounts', 'vouchers', 'grandTotal')
        );
    }

    public function expenseVoucherAjax(Request $request)
    {
        $query = \App\Models\ExpenseVoucher::query();

        // 🛡️ Restrict non-admin users to their own expenses
        if (auth()->id() !== 1 && !auth()->user()->hasRole('Admin')) {
            $query->where('user_id', auth()->id());
        }

        // Account Head (type)
        if ($request->filled('account_heads') && !in_array('all', $request->account_heads)) {
            $query->whereIn('type', $request->account_heads);
        }

        // Accounts (party_id)
        if ($request->filled('accounts')) {
            $query->whereIn('party_id', $request->accounts);
        }

        // Date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $vouchers = $query->latest()->get();

        $data = $vouchers->map(function ($v) {

            // remarks decode (JSON safe)
            $remarks = json_decode($v->remarks, true);

            return [
                'evid'    => $v->evid,
                'date'    => \Carbon\Carbon::parse($v->date)->format('d-m-Y'),
                'head'    => optional(\App\Models\AccountHead::find($v->type))->name,
                'account' => optional(\App\Models\Account::find($v->party_id))->title,
                'remarks' => is_array($remarks) ? implode(', ', $remarks) : ($v->remarks ?? '-'),
                'amount'  => number_format($v->total_amount, 2),
            ];
        });

        return response()->json([
            'rows' => $data,
            'total' => number_format($vouchers->sum('total_amount'), 2)
        ]);
    }

    public function sale_closing_report()
    {
        $users = \App\Models\User::all();
        return view('admin_panel.reporting.sale_closing_report', compact('users'));
    }

    public function fetchSaleClosingReport(Request $request)
    {
        $start = $request->start_date ?? date('Y-m-d');
        $end   = $request->end_date   ?? date('Y-m-d');
        $userId = $request->user_id;

        // 1. Fetch Sales
        $salesQuery = DB::table('sales')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);

        if (auth()->id() !== 1 && !auth()->user()->hasRole('Admin')) {
            $salesQuery->where('user_id', auth()->id());
        } elseif ($userId && $userId !== 'all') {
            $salesQuery->where('user_id', $userId);
        }

        $sales = $salesQuery->select('id', 'invoice_no', 'total_net', 'created_at')
            ->orderBy('created_at', 'asc')
            ->get();

        $totalSale = $sales->sum('total_net');

        // 2. Fetch Expenses
        $expenseQuery = DB::table('expense_vouchers')
            ->whereBetween('date', [$start, $end]);

        if (auth()->id() !== 1 && !auth()->user()->hasRole('Admin')) {
            $expenseQuery->where('user_id', auth()->id());
        } elseif ($userId && $userId !== 'all') {
            $expenseQuery->where('user_id', $userId);
        }

        $expenses = $expenseQuery->select('id', 'evid', 'amount', 'date', 'type', 'party_id')
            ->orderBy('date', 'asc')
            ->get();

        $processedExpenses = [];
        $totalExpense = 0;
        foreach ($expenses as $ex) {
            $amounts = json_decode($ex->amount, true) ?: [];
            $sum = array_sum($amounts);
            $totalExpense += $sum;
            
            $ex->total_amount = $sum;
            $processedExpenses[] = $ex;
        }

        return response()->json([
            'sales' => $sales,
            'expenses' => $processedExpenses,
            'total_sale' => $totalSale,
            'total_expense' => $totalExpense,
            'net_amount' => $totalSale - $totalExpense,
            'start_date' => $start,
            'end_date' => $end
        ]);
    }

    public function printSaleClosingReport(Request $request)
    {
        $start = $request->start_date ?? date('Y-m-d');
        $end   = $request->end_date   ?? date('Y-m-d');
        $userId = $request->user_id;

        $salesQuery = DB::table('sales')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);

        if (auth()->id() !== 1 && !auth()->user()->hasRole('Admin')) {
            $salesQuery->where('user_id', auth()->id());
        } elseif ($userId && $userId !== 'all') {
            $salesQuery->where('user_id', $userId);
        }

        $sales = $salesQuery->select('id', 'invoice_no', 'total_net', 'created_at')
            ->get();

        $totalSale = $sales->sum('total_net');

        $expenseQuery = DB::table('expense_vouchers')
            ->whereBetween('date', [$start, $end]);

        if (auth()->id() !== 1 && !auth()->user()->hasRole('Admin')) {
            $expenseQuery->where('user_id', auth()->id());
        } elseif ($userId && $userId !== 'all') {
            $expenseQuery->where('user_id', $userId);
        }

        $expenses = $expenseQuery->get();

        $totalExpense = 0;
        foreach ($expenses as $ex) {
            $amounts = json_decode($ex->amount, true) ?: [];
            $totalExpense += array_sum($amounts);
        }

        $userName = 'All Users';
        if ($userId && $userId !== 'all') {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $userName = $user->name;
            }
        }

        return view('admin_panel.reporting.sale_closing_print', [
            'totalSale' => $totalSale,
            'totalExpense' => $totalExpense,
            'netAmount' => $totalSale - $totalExpense,
            'startDate' => $start,
            'endDate' => $end,
            'salesCount' => $sales->count(),
            'expensesCount' => $expenses->count(),
            'userName' => $userName
        ]);
    }
}
