<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Customer;
use App\Models\SalesReturn;
use Illuminate\Http\Request;

use App\Models\CustomerLedger;
use App\Models\ProductBooking;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            
            // 🔹 Base Query
            $query = Sale::with(['customer_relation', 'user']);

            // 🔹 Restrict non-admin users to their own sales
            // Exempt User ID 1 (Super Admin) to ensure they can see/filter everything
            if (auth()->id() !== 1 && !auth()->user()->hasRole('Admin')) {
                 $query->where('user_id', auth()->id());
            }

            // 🔹 Apply Filters
            if ($request->filled('from_date')) {
                $query->where('created_at', '>=', \Carbon\Carbon::parse($request->from_date)->startOfDay());
            }
            if ($request->filled('to_date')) {
                $query->where('created_at', '<=', \Carbon\Carbon::parse($request->to_date)->endOfDay());
            }
            if ($request->filled('filter_user')) {
                 $query->where('user_id', $request->filter_user);
            }

            // 🔹 Search Filter
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%")
                      ->orWhere('total_bill_amount', 'like', "%{$search}%") // Search Amount
                      ->orWhere('product_code', 'like', "%{$search}%")      // Search Barcode/Item Code
                      ->orWhereHas('customer_relation', function($c) use ($search) {
                          $c->where('customer_name', 'like', "%{$search}%");
                      });

                    // Search by Product Name (indirectly via IDs)
                    // 1. Find IDs of products matching the search name
                    $matchingProductIds = \App\Models\Product::where('item_name', 'like', "%{$search}%")
                        ->orWhere('barcode_path', 'like', "%{$search}%") // Extra safety
                        ->pluck('id')
                        ->toArray();
                    
                    if (!empty($matchingProductIds)) {
                        foreach ($matchingProductIds as $pid) {
                            // Search for this ID in the comma-separated product column
                            // We use precise matching to avoid finding '1' in '10', '21', etc.
                            // Patterns: "1", "1,2", "2,1", "3,1,4"
                            $q->orWhereRaw("FIND_IN_SET(?, product)", [$pid]);
                        }
                    }
                });
            }

            // 🔹 Total Records (before filtering)
            $totalRecords = auth()->user()->hasRole('Admin')
                ? Sale::count()
                : Sale::where('user_id', auth()->id())->count();
            
            // 🔹 Filtered Records
            $filteredRecords = $query->count();

            // 🔹 Sorting
            if ($request->has('order')) {
                $orderColumnIndex = $request->order[0]['column'];
                $orderDir = $request->order[0]['dir'];
                
                // Map column index to database column (adjust as needed)
                $columns = [
                    1 => 'invoice_no',
                    2 => 'customer',
                    3 => 'reference',
                    10 => 'total_bill_amount',
                    11 => 'created_at',
                    12 => 'sale_status'
                ]; 
                
                $colName = $columns[$orderColumnIndex] ?? 'id';
                
                // Fix: Invoice No (col 1) string sorting issue (999 > 1000). 
                // Use ID instead for correct numeric order.
                if ($colName === 'invoice_no') {
                     $query->orderBy('id', $orderDir);
                } elseif($colName == 'customer') {
                     // Sorting by relation is complex, fallback to id
                     $query->orderBy('id', $orderDir);
                } else {
                     $query->orderBy($colName, $orderDir);
                }
            } else {
                $query->orderBy('id', 'desc');
            }

            // 🔹 Pagination
            $skip = $request->start ?? 0;
            $take = $request->length ?? 10;
            
            $sales = $query->skip($skip)->take($take)->get();

            // 🔹 Optimized Product Fetching (Avoid N+1)
            $allProductIds = [];
            foreach ($sales as $sale) {
                if(!empty($sale->product)) {
                    $ids = explode(',', $sale->product);
                    foreach($ids as $id) $allProductIds[] = trim($id);
                }
            }
            $allProductIds = array_unique($allProductIds);

            // Fetch all needed products and variants in one query
            $productsMap = \App\Models\Product::whereIn('id', $allProductIds)->get()->keyBy('id');
            
            $allVariantIds = [];
            foreach ($sales as $sale) {
                if(!empty($sale->variant_id)) {
                    $vids = explode(',', $sale->variant_id);
                    foreach($vids as $vid) if($vid) $allVariantIds[] = trim($vid);
                }
            }
            $allVariantIds = array_unique($allVariantIds);
            $variantsMap = \App\Models\ProductVariant::whereIn('id', $allVariantIds)->get()->keyBy('id');

            // 🔹 Transform Data
            $data = [];
            foreach ($sales as $index => $sale) {
                
                // Parse CSV columns
                $prodIds = explode(',', $sale->product);
                $qtys = explode(',', $sale->qty);
                $prices = explode(',', $sale->per_price);
                $discounts = explode(',', $sale->per_discount);
                $totals = explode(',', $sale->per_total);
                $vIds = explode(',', $sale->variant_id);
                
                $barcodeHtml = '';
                $productHtml = '';
                
                foreach($prodIds as $i => $pid) {
                    $p = $productsMap[$pid] ?? null;
                    $vId = $vIds[$i] ?? null;
                    $v = $vId ? ($variantsMap[$vId] ?? null) : null;
                    
                    $barcodeHtml .= ($p ? $p->barcode_path : 'N/A') . '<br>';
                    
                    $pName = $p ? $p->item_name : 'N/A';
                    if ($v) {
                        $pName .= ' (' . ($v->size_label ?: $v->variant_name) . ')';
                    }
                    $productHtml .= $pName . '<br>';
                }

                // Helper closure for formatting
                $fmt = function($val) {
                    $val = (float)$val;
                    return ($val == (int)$val) ? number_format($val, 0) : number_format($val, 2);
                };

                // Build HTML for other list columns
                $cleanedQtys = array_map(function($q) use ($fmt) { return $fmt($q); }, $qtys);
                $qtyHtml = implode('<br>', $cleanedQtys);

                $priceHtml = '';
                foreach($prices as $pr) $priceHtml .= $fmt($pr) . '<br>';
                
                $discHtml = '';
                foreach($discounts as $d) $discHtml .= $fmt($d) . '<br>';

                $totalHtml = '';
                foreach($totals as $t) $totalHtml .= $fmt($t) . '<br>';

                // Status Badge
                $statusBadge = '<span class="badge bg-secondary">Unknown</span>';
                if($sale->sale_status === null) 
                    $statusBadge = '<span class="badge bg-success">Sale</span>';
                elseif($sale->sale_status == 1) 
                    $statusBadge = '<span class="badge bg-danger">Return</span>';

                // Action Buttons
                $actions = '<div class="btn-group btn-group-sm" role="group">
                    <a href="'.route('sales.recepit', $sale->id).'" class="btn btn-dark" target="_blank"><i class="fas fa-print"></i> Print Bill</a>
                    <a href="'.route('sales.invoice', $sale->id).'" class="btn btn-info text-white" target="_blank"><i class="fas fa-file-invoice"></i> Invoice</a>
                    <a href="'.route('sales.dc', $sale->id).'" class="btn btn-success text-white" target="_blank">DC</a>
                    <a href="'.route('sales.edit', $sale->id).'" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
                    <a href="'.route('sales.return.create', $sale->id).'" class="btn btn-warning"><i class="fas fa-undo"></i> Return</a>
                </div>';
                
                // Date formatting
                $date = \Carbon\Carbon::parse($sale->created_at)->format('d-m-Y h:i A');

                $data[] = [
                    $skip + $index + 1, // S.No
                    $sale->user ? $sale->user->name : 'N/A', // User Name
                    $sale->invoice_no,
                    $sale->customer_relation->customer_name ?? 'Walk-in Customer',
                    $productHtml,
                    $qtyHtml,
                    $priceHtml,
                    $discHtml,
                    $totalHtml,
                    '<span class="fw-bold fs-5">' . $fmt($sale->total_bill_amount) . '</span>', // Bold Total Amount
                    $date,
                    $statusBadge,
                    $actions
                ];
            }

            return response()->json([
                "draw" => intval($request->draw),
                "recordsTotal" => $totalRecords,
                "recordsFiltered" => $filteredRecords,
                "data" => $data
            ]);
        }
        $openingBalance = \App\Models\UserOpeningBalance::where('user_id', auth()->id())
            ->where('date', date('Y-m-d'))
            ->value('amount') ?? 0;

        // Calculate today's sales for the logged-in user
        $todaySales = Sale::where('user_id', auth()->id())
            ->whereDate('created_at', date('Y-m-d'))
            ->sum('total_bill_amount');

        // Calculate today's expenses for the logged-in user
        $todayExpense = \App\Models\ExpenseVoucher::where('user_id', auth()->id())
            ->whereDate('date', date('Y-m-d'))
            ->sum('total_amount');

        $netCash = $openingBalance + $todaySales - $todayExpense;

        return view('admin_panel.sale.index', compact('openingBalance', 'todaySales', 'todayExpense', 'netCash'));
    }

    public function addsale()
    {
        $Customer = Customer::get();
        $categories = \App\Models\Category::orderBy('name')->get();
        $tables = \App\Models\Table::orderBy('table_name')->get();
        return view('admin_panel.sale.add_sale', compact('Customer', 'categories', 'tables'));
    }

    public function getPosProducts(Request $request)
    {
        $q       = trim($request->get('q', ''));
        $catId   = $request->get('category_id', '');
        $perPage = (int) $request->get('per_page', 60);

        $query = Product::with(['brand', 'stock', 'variants', 'activeDiscount', 'category_relation'])
            ->withSum(['stocks as total_stock' => function($q) {
                $q->where('branch_id', 1)->where('warehouse_id', 1);
            }], 'qty');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('item_name', 'like', "%{$q}%")
                    ->orWhere('item_code', 'like', "%{$q}%");
            });
        }

        if ($catId !== '') {
            $query->where('category_id', $catId);
        }

        $products = $query->orderBy('item_name')->paginate($perPage);

        $items = $products->getCollection()->map(function ($product) {
            $price = (float) $product->price;
            if ($product->activeDiscount) {
                $price = (float) ($product->activeDiscount->final_price ?? $price);
            }
            $stock    = (float) ($product->total_stock ?? 0);
            $imageUrl = $product->image
                ? asset('uploads/products/' . $product->image)
                : null;

            return [
                'id'               => $product->id,
                'item_name'        => $product->item_name,
                'item_code'        => $product->item_code,
                'category_id'      => $product->category_id,
                'category'         => $product->category_relation?->name ?? 'Other',
                'brand'            => $product->brand?->name ?? '',
                'unit_id'          => $product->unit_id,
                'note'             => $product->note ?? '',
                'price'            => $price,
                'original_price'   => (float) $product->price,
                'has_discount'     => $product->activeDiscount ? true : false,
                'discount_percent' => $product->activeDiscount?->discount_percentage ?? 0,
                'stock'            => $stock,
                'image'            => $imageUrl,
                'has_variants'     => $product->variants->count() > 0,
                'unit_type'        => $product->unit_type ?? 'piece',
            ];
        });

        return response()->json([
            'data'         => $items,
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
            'total'        => $products->total(),
        ]);
    }

    public function getProductVariants($id)
    {
        $product = Product::with(['variants', 'stock'])->findOrFail($id);
        $variants = $product->variants->map(function ($v) use ($product) {
            // Get real stock from stocks table for this variant
            $vStock = Stock::where('product_id', $product->id)
                ->where('branch_id', 1)
                ->where('warehouse_id', 1)
                ->where('variant_id', $v->id)
                ->first();
            
            return [
                'id'              => $v->id,
                'name'            => $v->variant_name,
                'size_label'      => $v->size_label ?? $v->variant_name,
                'size_value'      => $v->size_value,
                'size_unit'       => $v->size_unit,
                'price'           => (float) $v->price,
                'wholesale_price' => (float) ($v->wholesale_price ?: $v->price),
                'stock'           => $vStock ? (float) $vStock->qty : 0,
                'is_default'      => $v->is_default,
            ];
        });
        $totalStock = Stock::where('product_id', $product->id)
            ->where('branch_id', 1)
            ->where('warehouse_id', 1)
            ->sum('qty');

        return response()->json([
            'product_id'   => $product->id,
            'item_name'    => $product->item_name,
            'item_code'    => $product->item_code,
            'unit_type'    => $product->unit_type ?? 'piece',
            'total_stock'  => (float) $totalStock,
            'variants'     => $variants,
        ]);
    }

    public function searchpname(Request $request)
    {
        $q = trim($request->q ?? '');

        // 1. Search products
        $pQuery = Product::with(['brand', 'activeDiscount', 'variants'])
            ->orderBy('item_name');

        if ($q !== '') {
            $pQuery->where(function($query) use ($q) {
                $query->where('item_name', 'like', "%{$q}%")
                      ->orWhere('item_code', 'like', "{$q}%")
                      ->orWhere('barcode_path', 'like', "{$q}%");
            });
        }

        $products = $pQuery->limit(30)->get();

        // 2. Search variants directly for codes
        $variantSearchResults = $q !== ''
            ? \App\Models\ProductVariant::with('product.brand')
                ->where('variant_name', 'like', "%{$q}%")
                ->limit(20)
                ->get()
            : collect();

        $results = collect();

        // Handle products
        foreach ($products as $p) {
            if ($p->variants->count() > 0) {
                // Return each variant if they exist
                foreach ($p->variants as $v) {
                    $results->push([
                        'id'               => $p->id,
                        'variant_id'       => $v->id, // Mark as variant
                        'item_name'        => $p->item_name . ' (' . ($v->size_label ?: $v->variant_name) . ')',
                        'item_code'        => $p->item_code,
                        'brand'            => $p->brand?->name,
                        'unit_id'          => $p->unit_id,
                        'note'             => $p->note,
                        'wholesale_price'  => $v->wholesale_price ?: $p->wholesale_price,
                        'price'            => $v->price ?: $p->price,
                        'original_price'   => $v->price ?: $p->price,
                        'discount_percent' => 0,
                        'discount_amount'  => 0,
                        'has_discount'     => false,
                    ]);
                }
            } else {
                // No variants, return base product
                $price = (float) $p->price;
                if ($p->activeDiscount) {
                    $price = (float) $p->activeDiscount->final_price;
                }
                $results->push([
                    'id'               => $p->id,
                    'variant_id'       => null,
                    'item_name'        => $p->item_name,
                    'item_code'        => $p->item_code,
                    'brand'            => $p->brand?->name,
                    'unit_id'          => $p->unit_id,
                    'note'             => $p->note,
                    'wholesale_price'  => $p->wholesale_price,
                    'price'            => $price,
                    'original_price'   => $p->price,
                    'discount_percent' => $p->activeDiscount?->discount_percentage ?? 0,
                    'discount_amount'  => $p->activeDiscount?->total_discount ?? 0,
                    'has_discount'     => $p->activeDiscount ? true : false,
                ]);
            }
        }

        // Handle variants found directly
        foreach ($variantSearchResults as $v) {
            // Avoid duplicate if already added via product loop
            if ($results->where('variant_id', $v->id)->count() > 0) continue;

            $p = $v->product;
            $results->push([
                'id'               => $p->id,
                'variant_id'       => $v->id,
                'item_name'        => $p->item_name . ' (' . ($v->size_label ?: $v->variant_name) . ')',
                'item_code'        => $p->item_code,
                'brand'            => $p->brand?->name,
                'unit_id'          => $p->unit_id,
                'note'             => $p->note,
                'wholesale_price'  => $v->wholesale_price ?: $p->wholesale_price,
                'price'            => $v->price ?: $p->price,
                'original_price'   => $v->price ?: $p->price,
                'discount_percent' => 0,
                'discount_amount'  => 0,
                'has_discount'     => false,
            ]);
        }

        return response()->json($results->values());
    }

    public function getActiveSaleForTable($table_id)
    {
        $table = \App\Models\Table::find($table_id);
        if (!$table || $table->status !== 'occupied') {
            return response()->json(['success' => false, 'message' => 'Table not occupied']);
        }

        $sale = \App\Models\Sale::where('table_id', $table_id)
            ->where('order_type', 'Dine-in')
            ->orderBy('id', 'desc')
            ->first();

        if (!$sale) {
            return response()->json(['success' => false, 'message' => 'No active sale found']);
        }

        $products = explode(',', $sale->product);
        $codes = explode(',', $sale->product_code);
        $brands = explode(',', $sale->brand);
        $units = explode(',', $sale->unit);
        $prices = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys = explode(',', $sale->qty);
        $variant_ids = explode(',', $sale->variant_id ?? '');
        $colors = json_decode($sale->color, true) ?: [];

        // Fetch Product models to get item_name
        $productMap = Product::whereIn('id', array_filter($products))->get()->keyBy('id');
        $variantMap = \App\Models\ProductVariant::whereIn('id', array_filter($variant_ids))->pluck('variant_name', 'id');

        $items = [];
        foreach ($products as $index => $pid) {
            if (empty($pid)) continue;
            
            $prod = $productMap->get($pid);
            $vId = $variant_ids[$index] ?? '';
            $display_name = $prod ? $prod->item_name : $pid;

            // Optional: Append variant name
            if ($vId && isset($variantMap[$vId])) {
                $display_name .= ' - ' . $variantMap[$vId];
            }

            // Extract label (color field)
            $label = '';
            if (isset($colors[$index])) {
                $rawLabel = $colors[$index];
                $decoded = json_decode($rawLabel, true);
                if (json_last_error() === JSON_ERROR_NONE && !is_null($decoded)) {
                    $label = is_array($decoded) ? ($decoded[0] ?? '') : $decoded;
                } else {
                    $label = $rawLabel;
                }
            }

            $items[] = [
                'prod_id' => $pid,
                'var_id'  => $vId,
                'name'    => $display_name,
                'code'    => $codes[$index] ?? '',
                'brand'   => $brands[$index] ?? '',
                'unit'    => $units[$index] ?? '',
                'price'   => floatval($prices[$index] ?? 0),
                'disc'    => $discounts[$index] ?? 0,
                'qty'     => floatval($qtys[$index] ?? 0),
                'old_qty' => floatval($qtys[$index] ?? 0), 
                'label'   => $label,
            ];
        }

        return response()->json([
            'success' => true,
            'sale_id' => $sale->id,
            'items'   => $items,
            'customer' => $sale->customer,
            'reference' => $sale->reference,
        ]);
    }

    public function store(Request $request)
    {
        $running_sale_id = $request->input('running_sale_id');
        if (!empty($running_sale_id)) {
            return $this->updateRunningSale($request, $running_sale_id);
        }

        $action = $request->input('action'); // 'booking' or 'sale'
        $booking_id = $request->booking_id; // <-- existing booking ID if editing

        // --- Basic validation: require customer, reference, and at least one valid product row ---
        $validator = \Validator::make($request->all(), [
            'customer' => 'required',
            // We'll validate products manually below (because arrays mixed)
        ]);

        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        // Validate there's at least one filled product row
        $product_ids = is_array($request->product_id) ? $request->product_id : [];
        $qtys = is_array($request->qty) ? $request->qty : [];
        $prices = is_array($request->price) ? $request->price : [];

        $hasRow = false;
        foreach ($product_ids as $i => $pid) {
            $q = isset($qtys[$i]) ? floatval($qtys[$i]) : 0;
            $p = isset($prices[$i]) ? floatval($prices[$i]) : 0;
            if (!empty($pid) && $q > 0 && $p > 0) {
                $hasRow = true;
                break;
            }
        }

        if (! $hasRow) {
            return back()->withInput()->with('error', 'Please add at least one product with quantity and price.');
        }

        DB::beginTransaction();

        try {
            // --- Input arrays (safely handle missing keys) ---
            $product_ids     = is_array($request->product_id) ? $request->product_id : [];
            $product_names   = is_array($request->product_name) ? $request->product_name : [];
            $product_codes   = is_array($request->item_code) ? $request->item_code : [];
            $brands          = is_array($request->uom) ? $request->uom : [];
            $units           = is_array($request->unit) ? $request->unit : [];
            $prices          = is_array($request->price) ? $request->price : [];
            $discounts       = is_array($request->item_disc) ? $request->item_disc : [];
            $quantities      = is_array($request->qty) ? $request->qty : [];
            $totals          = is_array($request->total) ? $request->total : [];
            $colors          = is_array($request->color) ? $request->color : [];

            // Arrays to be saved
            $combined_product_ids   = [];
            $combined_product_names = [];
            $combined_codes         = [];
            $combined_brands        = [];
            $combined_units         = [];
            $combined_prices        = [];
            $combined_discounts     = [];
            $combined_qtys          = [];
            $combined_totals        = [];
            $combined_colors        = [];
            $combined_variant_ids   = [];

            $variant_ids = is_array($request->variant_id) ? $request->variant_id : [];

            $total_items = 0;

            // Pre-fetch all necessary models to avoid N+1 queries inside the loop
            $unique_product_ids = array_unique(array_filter($product_ids));
            
            // Fetch Products keyed by ID
            $productsMap = \App\Models\Product::whereIn('id', $unique_product_ids)->get()->keyBy('id');
            
            // Fetch Stocks keyed by product_id
            // Using logic from loop: we need stock for each product
            $stocksMap = \App\Models\Stock::whereIn('product_id', $unique_product_ids)->get()->keyBy('product_id');

            foreach ($product_ids as $index => $product_id) {
                $qty   = isset($quantities[$index]) ? $quantities[$index] : 0;
                $price = isset($prices[$index]) ? $prices[$index] : 0;

                // skip incomplete rows
                if (empty($product_id) || $qty <= 0 || $price <= 0) {
                    continue;
                }

                $combined_product_ids[] = $product_id;

                $pname = $product_names[$index] ?? null;
                if (empty($pname)) {
                    // Use pre-fetched product
                    $prodModel = $productsMap[$product_id] ?? null;
                    $pname = $prodModel ? $prodModel->item_name : '';
                }
                $combined_product_names[] = $pname;

                $combined_codes[]      = $product_codes[$index] ?? '';
                $combined_brands[]     = $brands[$index] ?? '';
                $combined_units[]      = $units[$index] ?? '';
                $combined_prices[]     = $prices[$index] ?? 0;
                $combined_discounts[]  = $discounts[$index] ?? 0;
                $combined_qtys[]       = $quantities[$index] ?? 0;
                $combined_totals[]     = $totals[$index] ?? 0;
                $combined_colors[]     = $colors[$index] ?? '';

                $vId   = $variant_ids[$index] ?? null;
                if ($vId === '') $vId = null; // Ensure null strictly for DB
                $combined_variant_ids[] = $vId;

                // Use pre-fetched stock 
                // In Bin Sultan POS, we deduct stock for BOTH final sale and save_token
                // Default branch/warehouse to 1 for POS for now
                $stockQuery = Stock::where('product_id', $product_id)
                                   ->where('branch_id', 1)
                                   ->where('warehouse_id', 1);
                
                $prodModel = $productsMap[$product_id] ?? null;
                $isGram = $prodModel && $prodModel->unit_type === 'kg';
                
                $dbVariantId = $vId;
                $deductQty = $qty;

                if ($isGram) {
                    $dbVariantId = null; // Always deduct from main product for KG
                    if ($vId) {
                        $vModel = \App\Models\ProductVariant::find($vId);
                        if ($vModel) {
                            $kgSize = floatval($vModel->size_value);
                            if ($vModel->size_unit === 'kg') {
                                $deductQty = ($kgSize * $qty * 1000);
                            } else {
                                $deductQty = ($kgSize * $qty);
                            }
                        }
                    } else {
                        $deductQty = $qty * 1000; // If no variant, assuming qty was inputted in KG
                    }
                }

                if ($dbVariantId) {
                    $stockQuery->where('variant_id', $dbVariantId);
                } else {
                    $stockQuery->whereNull('variant_id');
                }
                $stock = $stockQuery->first();

                // Only Sale updates stock (We decided to deduct for save_token as well so we can always do diff updates safely)
                if ($action === 'sale' || $action === 'save_token') {
                    if ($stock) {
                        $stock->qty = $stock->qty - $deductQty;
                        $stock->save();
                    } else {
                        $stock = \App\Models\Stock::create([
                            'branch_id'  => 1,
                            'warehouse_id' => 1,
                            'product_id' => $product_id,
                            'variant_id' => $dbVariantId,
                            'qty'        => 0 - $deductQty,
                        ]);
                    }

                    // 🔹 Sync with legacy/display stock column on variants table
                    if ($vId) {
                        $vModel = \App\Models\ProductVariant::find($vId);
                        if ($vModel) {
                            $vModel->stock_qty -= $qty; // Qty here is the primary unit quantity
                            $vModel->save();
                        }
                    }
                }
                $total_items += $qty;
            }

            // --- Choose model ---
            if ($action === 'booking' || ($action === 'sale' && !empty($booking_id))) {
                $model = !empty($booking_id) ? \App\Models\ProductBooking::findOrFail($booking_id) : new \App\Models\ProductBooking();
            } else { // 'sale' or 'save_token' (new direct sale)
                $model = new \App\Models\Sale();
                $model->invoice_no = \App\Models\Sale::generateInvoiceNo();
            }

            // --- Fill common fields ---
            $model->customer             = $request->customer;
            $model->reference            = $request->reference;
            $model->product              = implode(',', $combined_product_ids);
            $model->product_code         = implode(',', $combined_codes);
            $model->brand                = implode(',', $combined_brands);
            $model->unit                 = implode(',', $combined_units);
            $model->per_price            = implode(',', $combined_prices);
            $model->per_discount         = implode(',', $combined_discounts);
            $model->qty                  = implode(',', $combined_qtys);
            $model->per_total            = implode(',', $combined_totals);
            $model->color                = json_encode($combined_colors);
            $model->total_amount_Words   = $request->total_amount_Words ?? '';
            $model->total_bill_amount    = $request->total_subtotal ?? array_sum($combined_totals);
            $model->total_extradiscount  = $request->total_extra_cost ?? 0;
            $model->total_net            = $request->total_net ?? array_sum($combined_totals);
            
            // For bookings, we track advance and final payment
            if ($model instanceof \App\Models\ProductBooking) {
                if ($action === 'sale') {
                    $model->sale_date = now();
                    // Final payment reconciliation: 
                    // advance_payment stays as it was, 'cash' and 'card' are updated with what was received today.
                    // (Or we can store total in cash/card and keep advance separate)
                    $model->cash = $request->cash ?? 0;
                    $model->card = $request->card ?? 0;
                    $model->change = $request->change ?? 0;
                } else {
                    // This is a new booking or edit of unconfirmed booking
                    $model->advance_payment = $request->advance_payment ?? $request->cash ?? 0;
                    $model->booking_date = $model->booking_date ?? now();
                }
            } else {
                // New direct sale
                $model->cash   = $request->cash ?? 0;
                $model->card   = $request->card ?? 0;
                $model->change = $request->change ?? 0;
            }

            $model->total_items          = $total_items;
            $model->variant_id           = implode(',', $combined_variant_ids);
            $model->total_pieces          = $request->total_pieces;
            $model->total_meter          = $request->total_meter;

            if ($model instanceof \App\Models\Sale) {
                $model->user_id = auth()->id();
                $model->order_type = $request->order_type ?? 'Walk-in';
                $model->table_id = $request->table_id ?? null;
            }
            
            // Table status logic for Dine-in
            if ($model instanceof \App\Models\Sale && $model->order_type === 'Dine-in' && $model->table_id) {
                $table = \App\Models\Table::find($model->table_id);
                if ($table) {
                    if ($action === 'save_token') {
                        $table->status = 'occupied';
                    } elseif ($action === 'sale') {
                        $totalNet   = floatval($request->total_net ?? 0);
                        $totalPaid  = floatval($request->cash ?? 0) + floatval($request->card ?? 0);
                        if ($totalPaid >= $totalNet && $totalNet > 0) {
                            $table->status = 'available';
                        } else {
                            $table->status = 'occupied';
                        }
                    }
                    $table->save();
                }
            }

            $model->save();

            // Ledger update (only for confirmed sales or direct sales)
            if ($action === 'sale') {
                $customer_id = $request->customer;
                if ($customer_id !== 'Walk-in Customer') {
                    $ledger = \App\Models\CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
                    $net = floatval($request->total_net ?? 0);
                    if ($ledger) {
                        $ledger->previous_balance = $ledger->closing_balance;
                        $ledger->closing_balance += $net;
                        $ledger->save();
                    } else {
                        \App\Models\CustomerLedger::create([
                            'customer_id'      => $customer_id,
                            'admin_or_user_id' => auth()->id(),
                            'previous_balance' => 0,
                            'closing_balance'  => $net,
                            'opening_balance'  => $net,
                        ]);
                    }
                }
            }

            DB::commit();

            // Redirect logic
            if ($model instanceof \App\Models\Sale) {
                $returnTo = route('sale.add');
                $printMode = ($action === 'save_token') ? 'token_only' : 'invoice';
                $invoiceUrl = route('sales.invoice', $model->id) . '?return_to=' . urlencode($returnTo) . '&autoprint=1&mode=' . $printMode;
                return redirect()->to($invoiceUrl)->with('success', $action === 'save_token' ? 'Order Saved.' : 'Sale completed.');
            } else {
                // ProductBooking
                if ($action === 'sale') {
                    // Confirmation: Go back to list with alert
                    return redirect()->route('bookings.index')->with('success', 'Booking Confirmed Successfully.');
                }
                // New Booking or Edit: Show Receipt
                $returnTo = route('bookings.index');
                $receiptUrl = route('booking.receipt', $model->id) . '?return_to=' . urlencode($returnTo) . '&autoprint=1';
                return redirect()->to($receiptUrl)->with('success', 'Booking Saved Successfully.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function updateRunningSale(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $sale = \App\Models\Sale::findOrFail($id);
            $action = $request->input('action'); // 'sale' or 'save_token'

            // Fetch old items to find the newly added ones
            $old_product_ids = explode(',', $sale->product);
            $old_variant_ids = explode(',', $sale->variant_id ?? '');
            $old_qtys = explode(',', $sale->qty);
            $oldMap = [];
            foreach ($old_product_ids as $idx => $pid) {
                if(empty($pid)) continue;
                $vid = $old_variant_ids[$idx] ?? '';
                $key = $pid . '_' . ($vid ?: '0');
                $oldMap[$key] = ($oldMap[$key] ?? 0) + floatval($old_qtys[$idx] ?? 0);
            }

            // Arrays from request
            $product_ids    = $request->product_id ?? [];
            $variant_ids    = $request->variant_id ?? [];
            $product_codes  = $request->item_code ?? [];
            $brands         = $request->uom ?? $request->brand ?? [];
            $units          = $request->unit ?? [];
            $prices         = $request->price ?? [];
            $discounts      = $request->item_disc ?? [];
            $quantities     = $request->qty ?? [];
            $totals         = $request->total ?? [];
            $colors         = $request->color ?? [];

            $combined_products    = [];
            $combined_codes       = [];
            $combined_brands      = [];
            $combined_units       = [];
            $combined_prices      = [];
            $combined_discounts   = [];
            $combined_qtys        = [];
            $combined_totals      = [];
            $combined_colors      = [];
            $combined_variant_ids = [];

            $total_items = 0;
            $newItemsAddedForToken = []; // to pass to KOT

            foreach ($product_ids as $index => $product_id) {
                $vId   = $variant_ids[$index] ?? '';
                $key   = $product_id . '_' . ($vId ?: '0');
                $qty   = isset($quantities[$index]) ? floatval($quantities[$index]) : 0;
                $price = isset($prices[$index]) ? floatval($prices[$index]) : 0;

                if (empty($product_id) || $qty <= 0 || $price <= 0) continue;

                $combined_products[]    = $product_id;
                $combined_variant_ids[] = $vId;
                $combined_codes[]       = $product_codes[$index] ?? '';
                $combined_brands[]      = $brands[$index] ?? '';
                $combined_units[]       = $units[$index] ?? '';
                $combined_prices[]      = $prices[$index];
                $combined_discounts[]   = $discounts[$index] ?? 0;
                $combined_qtys[]        = $qty;
                $combined_totals[]      = $totals[$index] ?? 0;
                $combined_colors[]      = $colors[$index] ?? '';
                
                $total_items += $qty;

                $old_qty = $oldMap[$key] ?? 0;
                $qty_diff = $qty - $old_qty;

                if ($qty_diff > 0 && $action === 'save_token') {
                    // Collect diff for token ONLY if it's new/increased
                    $newItemsAddedForToken[] = [
                        'prod_id' => $product_id,
                        'var_id'  => $vId,
                        'qty'     => $qty_diff,
                        'price'   => $price
                    ];
                }

                unset($oldMap[$key]);

                // Stock update IF final sale or save_token
                if ($action === 'sale' || $action === 'save_token') {
                    $prodModel = \App\Models\Product::find($product_id);
                    $isGram = $prodModel && $prodModel->unit_type === 'kg';
                    
                    $dbVariantId = $vId === '' ? null : $vId;
                    $deductQtyDiff = $qty_diff;

                    if ($isGram) {
                        $dbVariantId = null;
                        if ($vId) {
                            $vModel = \App\Models\ProductVariant::find($vId);
                            if ($vModel) {
                                $kgSize = floatval($vModel->size_value);
                                if ($vModel->size_unit === 'kg') {
                                    $deductQtyDiff = ($kgSize * $qty_diff * 1000);
                                } else {
                                    $deductQtyDiff = ($kgSize * $qty_diff);
                                }
                            }
                        } else {
                            $deductQtyDiff = $qty_diff * 1000;
                        }
                    }

                    $stockQuery = \App\Models\Stock::where('product_id', $product_id)
                                                    ->where('branch_id', 1)
                                                    ->where('warehouse_id', 1);
                    if ($dbVariantId) $stockQuery->where('variant_id', $dbVariantId);
                    else $stockQuery->whereNull('variant_id');
                    $stock = $stockQuery->first();
                    
                    if ($stock) {
                        $stock->qty -= $deductQtyDiff;
                        $stock->save();
                    } else {
                        \App\Models\Stock::create([
                            'branch_id'  => 1,
                            'warehouse_id' => 1,
                            'product_id' => $product_id, 
                            'variant_id' => $dbVariantId, 
                            'qty' => -$deductQtyDiff
                        ]);
                    }
                }
            }

            if ($action === 'sale' || $action === 'save_token') {
                foreach ($oldMap as $key => $old_qty) {
                    if ($old_qty <= 0) continue;
                    $parts = explode('_', $key);
                    $pid = $parts[0];
                    $vid = $parts[1] === '0' ? null : $parts[1];

                    $prodModel = \App\Models\Product::find($pid);
                    $isGram = $prodModel && $prodModel->unit_type === 'kg';
                    
                    $dbVariantId = $vid;
                    $addBackQty = $old_qty;

                    if ($isGram) {
                        $dbVariantId = null;
                        if ($vid) {
                            $vModel = \App\Models\ProductVariant::find($vid);
                            if ($vModel) {
                                $kgSize = floatval($vModel->size_value);
                                if ($vModel->size_unit === 'kg') {
                                    $addBackQty = ($kgSize * $old_qty * 1000);
                                } else {
                                    $addBackQty = ($kgSize * $old_qty);
                                }
                            }
                        } else {
                            $addBackQty = $old_qty * 1000;
                        }
                    }

                    $stockQuery = \App\Models\Stock::where('product_id', $pid)
                                                    ->where('branch_id', 1)
                                                    ->where('warehouse_id', 1);
                    if ($dbVariantId) $stockQuery->where('variant_id', $dbVariantId);
                    else $stockQuery->whereNull('variant_id');
                    
                    $stock = $stockQuery->first();
                    if ($stock) {
                        $stock->qty += $addBackQty;
                        $stock->save();
                    }
                }
            }

            // Update Sale record
            $sale->customer        = $request->customer;
            $sale->reference       = $request->reference;
            $sale->product         = implode(',', $combined_products);
            $sale->variant_id      = implode(',', $combined_variant_ids);
            $sale->product_code    = implode(',', $combined_codes);
            $sale->brand           = implode(',', $combined_brands);
            $sale->unit            = implode(',', $combined_units);
            $sale->per_price       = implode(',', $combined_prices);
            $sale->per_discount    = implode(',', $combined_discounts);
            $sale->qty             = implode(',', $combined_qtys);
            $sale->per_total       = implode(',', $combined_totals);
            $sale->color           = json_encode($combined_colors);
            $sale->total_items     = $total_items;
            // The POS submits hidden inputs 
            $sale->total_bill_amount = $request->input('total_subtotal', $sale->total_bill_amount ?? array_sum($combined_totals));
            $sale->total_extradiscount = $request->input('total_extra_cost', $sale->total_extradiscount ?? 0);
            $sale->total_net       = $request->input('total_net', $sale->total_net ?? array_sum($combined_totals));
            $sale->cash            = $request->input('cash', 0);
            $sale->card            = $request->input('card', 0);
            $sale->change          = $request->input('change', 0);
            
            if ($action === 'sale') {
                if ($request->filled('table_id')) {
                    $sale->table_id = $request->table_id;
                    $table = \App\Models\Table::find($request->table_id);
                    if ($table) {
                        // Only free table if full payment is made
                        $totalNet  = floatval($request->input('total_net', $sale->total_net ?? 0));
                        $cashPaid  = floatval($request->input('cash', 0));
                        $cardPaid  = floatval($request->input('card', 0));
                        $totalPaid = $cashPaid + $cardPaid;
                        if ($totalPaid >= $totalNet && $totalNet > 0) {
                            $table->status = 'available';
                        } else {
                            $table->status = 'occupied';
                        }
                        $table->save();
                    }
                }
            }

            $sale->save();

            DB::commit();

            // Redirects
            $returnTo = route('sale.add');
            if ($action === 'save_token') {
                session()->flash('kot_items_json', json_encode($newItemsAddedForToken));
                $invoiceUrl = route('sales.invoice', $sale->id) . '?mode=token_only&autoprint=1&return_to=' . urlencode($returnTo);
                return redirect()->to($invoiceUrl);
            }

            // Dine-in final sale: print only invoice wrapper (not token again)
            $printMode = 'invoice';
            $invoiceUrl = route('sales.invoice', $sale->id) . '?mode=' . $printMode . '&autoprint=1&return_to=' . urlencode($returnTo);
            return redirect()->to($invoiceUrl);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Update Error: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sale $sale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */


    public function convertFromBooking($id)
    {
        $booking = ProductBooking::with('customer_relation')->findOrFail($id);
        $customers = Customer::all();

        // --- Parsing logic (identical to ProductBookingController@receipt) ---
        $products    = explode(',', $booking->product);
        $codes       = explode(',', $booking->product_code);
        $brands      = explode(',', $booking->brand);
        $units       = explode(',', $booking->unit);
        $prices      = explode(',', $booking->per_price);
        $discounts   = explode(',', $booking->per_discount);
        $qtys        = explode(',', $booking->qty);
        $totals      = explode(',', $booking->per_total);
        $variant_ids = explode(',', $booking->variant_id ?? '');

        $items = [];
        $productIds = array_unique($products);
        $productMap = Product::with('category_relation', 'brand')->whereIn('id', $productIds)->get()->keyBy('id');
        $variantMap = \App\Models\ProductVariant::whereIn('id', array_filter($variant_ids))->get()->keyBy('id');

        foreach ($products as $index => $p) {
            $qty = (float)($qtys[$index] ?? 0);
            if ($qty <= 0) continue;

            $vId = $variant_ids[$index] ?? '';
            $vModel = $vId ? ($variantMap[$vId] ?? null) : null;
            $variantName = $vModel ? ($vModel->size_label ?: $vModel->variant_name) : '';
            $productModel = $productMap[$p] ?? null;
            $displayName = $productModel ? $productModel->item_name : $p;
            $catName = ($productModel && $productModel->category_relation) ? $productModel->category_relation->name : 'Uncategorized';

            if ($variantName && $productModel) {
                $cleanVariant = preg_replace('/\s*\([\d.]+\s*KG\)/i', '', $variantName);
                $cleanVariant = trim(str_ireplace($productModel->item_name, '', $cleanVariant));
                $cleanVariant = ltrim($cleanVariant, ' -');
                if ($cleanVariant !== '') $displayName .= ' (' . $cleanVariant . ')';
            } else if ($variantName) {
                $displayName .= ' - ' . $variantName;
            }

            $unit = $units[$index] ?? '';
            $price = (float)($prices[$index] ?? 0);

            // Weight conversion logic
            if ($vModel && $productModel && strtolower($productModel->unit_type ?? '') === 'kg' && $vModel->size_value > 0) {
                $multiplier = 1;
                $sUnit = strtolower($vModel->size_unit ?? 'kg');
                if ($sUnit === 'kg') $multiplier = (float)$vModel->size_value;
                elseif (in_array($sUnit, ['gm','gram','grams'])) $multiplier = (float)$vModel->size_value / 1000;
                
                $qty = $qty * $multiplier;
                if ($multiplier > 0) $price = $price / $multiplier;
                $unit = 'KG';
            } else if (empty($unit) || is_numeric($unit)) {
                if ($vModel && !empty($vModel->size_unit)) $unit = strtoupper($vModel->size_unit);
                else if ($productModel && !empty($productModel->unit_type)) $unit = strtoupper($productModel->unit_type);
                else $unit = 'PIECE';
            }

            $items[] = [
                'product_id' => $productModel->id ?? '',
                'item_name' => $displayName,
                'category'  => $catName,
                'item_code' => $codes[$index] ?? '',
                'uom'       => $productModel && $productModel->brand ? $productModel->brand->name : ($brands[$index] ?? ''),
                'unit'      => $unit,
                'price'     => $price,
                'discount'  => (float)($discounts[$index] ?? 0),
                'qty'       => $qty,
                'total'     => (float)($totals[$index] ?? 0),
                'available_colors' => [], // Add if needed, keeping for compatibility
            ];
        }

        return view('admin_panel.sale.booking_edit', [
            'Customer' => $customers,
            'booking' => $booking,
            'bookingItems' => $items,
        ]);
    }


    // sale return start
    public function saleretun($id)
    {
        $sale = \App\Models\Sale::findOrFail($id);
        $customers = \App\Models\Customer::all();

        // Split comma-based fields from the sale row
        $products  = array_map('trim', explode(',', $sale->product ?? ''));
        $codes     = array_map('trim', explode(',', $sale->product_code ?? ''));
        $brands    = array_map('trim', explode(',', $sale->brand ?? ''));
        $units     = array_map('trim', explode(',', $sale->unit ?? ''));
        $prices    = array_map('trim', explode(',', $sale->per_price ?? ''));
        $discounts = array_map('trim', explode(',', $sale->per_discount ?? ''));
        $qtys      = array_map('trim', explode(',', $sale->qty ?? ''));
        $totals    = array_map('trim', explode(',', $sale->per_total ?? ''));
        $vIds      = array_map('trim', explode(',', $sale->variant_id ?? ''));

        // decode color JSON array (if stored like ["[]","[]"])
        $colors_json = json_decode($sale->color ?? '[]', true);
        if (!is_array($colors_json)) {
            $colors_json = [];
        }

        // Fetch all previous returns for this sale from sales_returns table
        $previousReturns = \DB::table('sales_returns')->where('sale_id', $sale->id)->get();

        // Build an aggregated map: returnedQtyByProductIdOrCode[productId_or_code] = totalReturnedQty
        $returnedQtyMap = [];

        foreach ($previousReturns as $ret) {
            // ret->product and ret->qty are comma separated strings, same shape as sale
            $retProducts = array_map('trim', explode(',', $ret->product ?? ''));
            $retQtys     = array_map('trim', explode(',', $ret->qty ?? ''));

            // loop indices and accumulate
            foreach ($retProducts as $ri => $rprod) {
                $rqty = isset($retQtys[$ri]) ? floatval($retQtys[$ri]) : 0;
                if ($rqty <= 0) continue;

                // try to treat rprod as product id (numeric) else treat as code
                $keyId = null;
                if (is_numeric($rprod)) {
                    $keyId = 'id_' . intval($rprod);
                    if (!isset($returnedQtyMap[$keyId])) $returnedQtyMap[$keyId] = 0;
                    $returnedQtyMap[$keyId] += $rqty;
                } else {
                    // store by code string
                    $keyCode = 'code_' . $rprod;
                    if (!isset($returnedQtyMap[$keyCode])) $returnedQtyMap[$keyCode] = 0;
                    $returnedQtyMap[$keyCode] += $rqty;
                }
            }
        }

        $items = [];

        foreach ($products as $index => $p) {
            // try to find product model by id (if value numeric) or by code fallback
            $product = null;
            $productIdCandidate = null;
            $itemCodeCandidate = $codes[$index] ?? '';

            if (is_numeric($p) && intval($p) > 0) {
                $productIdCandidate = intval($p);
                $product = \App\Models\Product::find($productIdCandidate);
            }

            if (!$product && !empty($itemCodeCandidate)) {
                $product = \App\Models\Product::where('item_code', trim($itemCodeCandidate))->first();
                if ($product) {
                    $productIdCandidate = $product->id;
                }
            }

            // ---------- NOTE parsing (previously color) ----------
            $note_value = '';
            if (isset($colors_json[$index])) {
                $maybe = $colors_json[$index];
                if (is_string($maybe)) {
                    $try = json_decode($maybe, true);
                    if ($try !== null) {
                        if (is_array($try)) {
                            $note_value = implode("\n", $try);
                        } else {
                            $note_value = (string)$try;
                        }
                    } else {
                        $note_value = $maybe;
                    }
                } elseif (is_array($maybe)) {
                    $note_value = implode("\n", $maybe);
                } else {
                    $note_value = (string)$maybe;
                }
            }
            // ---------- end note parsing ----------

            $soldQty = isset($qtys[$index]) && is_numeric($qtys[$index]) ? floatval($qtys[$index]) : 0;

            // compute returned qty using our map:
            $returnedQty = 0;
            if ($productIdCandidate) {
                $k = 'id_' . $productIdCandidate;
                if (isset($returnedQtyMap[$k])) {
                    // take as much as needed from the map to reduce this row's available qty
                    // but we must not consume more than soldQty for this specific row if we want to be safe, 
                    // OR we consume across multiple rows. The map is global for the sale.
                    // Strategy: We deduct from the map as we iterate.
                    $deduct = min($returnedQtyMap[$k], $soldQty);
                    $returnedQty += $deduct;
                    $returnedQtyMap[$k] -= $deduct; 
                }
            }
            if ($returnedQty == 0 && !empty($itemCodeCandidate)) { // fallback to code if id didn't match
                $kc = 'code_' . $itemCodeCandidate;
                if (isset($returnedQtyMap[$kc])) {
                    $deduct = min($returnedQtyMap[$kc], $soldQty);
                    $returnedQty += $deduct;
                    $returnedQtyMap[$kc] -= $deduct;
                }
            }

            $available = max(0, $soldQty - $returnedQty);

            $items[] = [
                'product_id'    => $product->id ?? ($productIdCandidate ?? ''),
                'variant_id'    => $vIds[$index] ?? null,
                'item_name'     => $product->item_name ?? (string)($p),
                'item_code'     => $product->item_code ?? ($itemCodeCandidate ?? ''),
                'brand'         => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'          => $product->unit ?? ($units[$index] ?? ''),
                'price'         => floatval($prices[$index] ?? 0),
                'discount'      => floatval($discounts[$index] ?? 0),
                'qty'           => $soldQty,
                'total'         => floatval($totals[$index] ?? 0),
                // send note (plain text) so blade can show it
                'note'          => $note_value,
                'available_qty' => (float)$available == (int)$available ? (int)$available : number_format($available, 3, '.', ''),
            ];
        }

        return view('admin_panel.sale.return.create', [
            'sale' => $sale,
            'Customer' => $customers,
            'saleItems' => $items,
        ]);
    }



    // public function storeSaleReturn(Request $request)
    // {
    //     // dd($request->all());
    //     DB::beginTransaction();

    //     try {
    //         $product_ids     = $request->product_id;
    //         $product_names   = $request->product;
    //         $product_codes   = $request->item_code;
    //         $brands          = $request->uom;
    //         $units           = $request->unit;
    //         $prices          = $request->price;
    //         $discounts       = $request->item_disc;
    //         $quantities      = $request->qty;
    //         $totals          = $request->total;
    //         $colors          = $request->color;

    //         $combined_products   = [];
    //         $combined_codes      = [];
    //         $combined_brands     = [];
    //         $combined_units      = [];
    //         $combined_prices     = [];
    //         $combined_discounts  = [];
    //         $combined_qtys       = [];
    //         $combined_totals     = [];
    //         $combined_colors     = [];

    //         $total_items = 0;

    //         foreach ($product_ids as $index => $product_id) {
    //             $qty   = $quantities[$index] ?? 0;
    //             $price = $prices[$index] ?? 0;

    //             if (!$product_id || !$qty || !$price) continue;

    //             $combined_products[]   = $product_names[$index] ?? '';
    //             $combined_codes[]      = $product_codes[$index] ?? '';
    //             $combined_brands[]     = $brands[$index] ?? '';
    //             $combined_units[]      = $units[$index] ?? '';
    //             $combined_prices[]     = $price;
    //             $combined_discounts[]  = $discounts[$index] ?? 0;
    //             $combined_qtys[]       = $qty;
    //             $combined_totals[]     = $totals[$index] ?? 0;

    //             // Convert color to valid JSON array
    //             $decodedColor = $colors[$index] ?? [];
    //             if (is_array($decodedColor)) {
    //                 $combined_colors[] = json_encode($decodedColor);
    //             } else {
    //                 $decoded = json_decode($decodedColor, true);
    //                 $combined_colors[] = json_encode(is_array($decoded) ? $decoded : []);
    //             }

    //             // ➕ Restore stock
    //             $stock = \App\Models\Stock::where('product_id', $product_id)->first();
    //             if ($stock) {
    //                 $stock->qty += $qty;
    //                 $stock->save();
    //             }

    //             $total_items += $qty;
    //         }

    //         // ➕ Create Sale Return
    //         $saleReturn = new \App\Models\SalesReturn();
    //         $saleReturn->sale_id              = $request->sale_id;
    //         $saleReturn->customer             = $request->customer;
    //         $saleReturn->reference            = $request->reference;

    //         $saleReturn->product              = implode(',', $combined_products);
    //         $saleReturn->product_code         = implode(',', $combined_codes);
    //         $saleReturn->brand                = implode(',', $combined_brands);
    //         $saleReturn->unit                 = implode(',', $combined_units);
    //         $saleReturn->per_price            = implode(',', $combined_prices);
    //         $saleReturn->per_discount         = implode(',', $combined_discounts);
    //         $saleReturn->qty                  = implode(',', $combined_qtys);
    //         $saleReturn->per_total            = implode(',', $combined_totals);
    //         $saleReturn->color                = json_encode($combined_colors);

    //         $saleReturn->total_amount_Words   = $request->total_amount_Words;
    //         $saleReturn->total_bill_amount    = $request->total_subtotal;
    //         $saleReturn->total_extradiscount  = $request->total_extra_cost;
    //         $saleReturn->total_net            = $request->total_net;

    //         $saleReturn->cash                 = $request->cash;
    //         $saleReturn->card                 = $request->card;
    //         $saleReturn->change               = $request->change;

    //         $saleReturn->total_items          = $total_items;
    //         $saleReturn->return_note          = $request->return_note;

    //         $saleReturn->save();

    //         DB::commit();

    //         return redirect()->route('sale.index')->with('success', 'Sale return saved successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Sale return failed: ' . $e->getMessage());
    //     }
    // }
    public function storeSaleReturn(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            // the arrays may be posted only for selected items, but validate generically
            'product'    => 'required|array',
            'product.*'  => 'nullable|string',
            'item_code'  => 'required|array',
            'item_code.*' => 'nullable|string',
            'unit'       => 'required|array',
            'unit.*'     => 'nullable|string',
            'price'      => 'required|array',
            'price.*'    => 'nullable|numeric',
            'item_disc'  => 'required|array',
            'item_disc.*' => 'nullable|numeric',
            'qty'        => 'required|array',
            'qty.*'      => 'nullable|numeric|min:0',
            'total'      => 'required|array',
            'total.*'    => 'nullable|numeric',
            'color'      => 'nullable|array',
            'variant_id' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $saleId = $request->sale_id;
            $sale = \App\Models\Sale::findOrFail($saleId);

            // Incoming arrays (may contain only selected return rows)
            $product_names = $request->input('product', []);     // product name or id text
            $product_ids   = $request->input('product_id', []);  // may be empty strings for some rows
            $variant_ids   = $request->input('variant_id', []);
            $product_codes = $request->input('item_code', []);
            $brands        = $request->input('brand', $request->input('uom', [])); // some forms call it uom
            $units         = $request->input('unit', []);
            $prices        = $request->input('price', []);
            $discounts     = $request->input('item_disc', []);
            $quantities    = $request->input('qty', []);
            $totals        = $request->input('total', []);
            $colors        = $request->input('color', []); // may be array of single selected color value or json

            // We'll build combined arrays to save in sales_returns
            $combined_products   = [];
            $combined_codes      = [];
            $combined_brands     = [];
            $combined_units      = [];
            $combined_prices     = [];
            $combined_discounts  = [];
            $combined_qtys       = [];
            $combined_totals     = [];
            $combined_colors     = [];

            $total_items = 0;

            // Use length of product_names (should match other arrays); be defensive
            $rows = max(
                count($product_names),
                count($product_codes),
                count($quantities),
                count($prices)
            );

            // Build a map of returns by code for updating original sale quantities
            $returnByCode = []; // code => qty to reduce

            for ($i = 0; $i < $rows; $i++) {
                $name = isset($product_names[$i]) ? trim($product_names[$i]) : '';
                $pid  = isset($product_ids[$i]) ? trim($product_ids[$i]) : '';
                $code = isset($product_codes[$i]) ? trim($product_codes[$i]) : '';
                $brand = isset($brands[$i]) ? trim($brands[$i]) : '';
                $unit  = isset($units[$i]) ? trim($units[$i]) : '';
                $price = isset($prices[$i]) ? floatval($prices[$i]) : 0;
                $disc  = isset($discounts[$i]) ? floatval($discounts[$i]) : 0;
                $qty   = isset($quantities[$i]) ? floatval($quantities[$i]) : 0;
                $total = isset($totals[$i]) ? floatval($totals[$i]) : ($price * $qty);
                $vId   = isset($variant_ids[$i]) ? trim($variant_ids[$i]) : '';
                $colorRaw = $colors[$i] ?? null;

                // Skip rows with zero qty (not selected)
                if ($qty <= 0) continue;

                // Push to combined arrays (preserve name/code even if id missing)
                $combined_products[]  = $name;
                $combined_codes[]     = $code;
                $combined_brands[]    = $brand;
                $combined_units[]     = $unit;
                $combined_prices[]    = (string)$price;
                $combined_discounts[] = (string)$disc;
                $combined_qtys[]      = (string)$qty;
                $combined_totals[]    = (string)$total;

                // Normalize color: if array => json_encode; if string that's JSON => try decode
                if (is_array($colorRaw)) {
                    $combined_colors[] = json_encode($colorRaw);
                } else {
                    // try parse string -> if valid json array keep, else wrap single value
                    $decoded = null;
                    if (is_string($colorRaw)) {
                        $decoded = json_decode($colorRaw, true);
                    }
                    if (is_array($decoded)) {
                        $combined_colors[] = json_encode($decoded);
                    } elseif (!empty($colorRaw)) {
                        $combined_colors[] = json_encode([$colorRaw]);
                    } else {
                        $combined_colors[] = json_encode([]);
                    }
                }

                // Stock update: increase stock for returned items if we can find product by ID or code
                $foundProduct = null;
                if ($pid !== '') {
                    // If numeric id provided, try find by id
                    if (is_numeric($pid)) {
                        $foundProduct = \App\Models\Product::find(intval($pid));
                    } else {
                        // sometimes product_id may come as name; try to find by id or code fallback
                        $maybe = \App\Models\Product::find($pid);
                        if (!$maybe && $code) {
                            $maybe = \App\Models\Product::where('item_code', $code)->first();
                        }
                        $foundProduct = $maybe;
                    }
                } else if (!empty($code)) {
                    $foundProduct = \App\Models\Product::where('item_code', $code)->first();
                } else if (!empty($name)) {
                    $foundProduct = \App\Models\Product::where('item_name', $name)->first();
                }

                if ($foundProduct) {
                    // Update stock: find stock row for product in same branch/warehouse
                    $stockQuery = \App\Models\Stock::where('product_id', $foundProduct->id)
                                                    ->where('branch_id', 1)
                                                    ->where('warehouse_id', 1);

                    if (!empty($sale->warehouse_id)) {
                        $stockQuery->where('warehouse_id', $sale->warehouse_id);
                    }

                    // For KG items, we store stock in GRAMS and use variant_id = NULL
                    $isKg = strtolower($foundProduct->unit_type ?? '') === 'kg';
                    $returnQtyInDb = $qty;

                    if ($isKg) {
                        $stockQuery->whereNull('variant_id');
                        $returnQtyInDb = $qty * 1000;
                    } else {
                        if (!empty($vId)) {
                            $stockQuery->where('variant_id', $vId);
                        } else {
                            $stockQuery->whereNull('variant_id');
                        }
                    }

                    $stock = $stockQuery->first();
                    if ($stock) {
                        $stock->qty = $stock->qty + $returnQtyInDb;
                        $stock->save();
                    } else {
                        // Create if not exists (defensive)
                        \App\Models\Stock::create([
                            'product_id'   => $foundProduct->id,
                            'variant_id'   => ($isKg ? null : (!empty($vId) ? $vId : null)),
                            'branch_id'    => 1,
                            'warehouse_id' => $sale->warehouse_id ?? 1,
                            'qty'          => $returnQtyInDb
                        ]);
                    }

                    // Also sync back to variants table display column if applicable
                    if (!empty($vId) && !$isKg) {
                        $vModel = \App\Models\ProductVariant::find($vId);
                        if ($vModel) {
                            $vModel->stock_qty += $qty;
                            $vModel->save();
                        }
                    }
                }

                // accumulate for sale update
                $key = $code ?: ($foundProduct ? ('ID_' . $foundProduct->id) : $name);
                if (!isset($returnByCode[$key])) $returnByCode[$key] = 0;
                $returnByCode[$key] += $qty;

                $total_items += $qty;
            }

            if (empty($combined_products)) {
                return redirect()->back()->with('error', 'No items selected for return.');
            }

            // Save sales_returns row (CSV arrays + json color array)
            $saleReturn = new \App\Models\SalesReturn();
            $saleReturn->sale_id = $saleId;
            $saleReturn->customer = $request->customer;
            $saleReturn->reference = $request->reference;
            $saleReturn->product = implode(',', $combined_products);
            $saleReturn->product_code = implode(',', $combined_codes);
            $saleReturn->brand = implode(',', $combined_brands);
            $saleReturn->unit = implode(',', $combined_units);
            $saleReturn->per_price = implode(',', $combined_prices);
            $saleReturn->per_discount = implode(',', $combined_discounts);
            $saleReturn->qty = implode(',', $combined_qtys);
            $saleReturn->per_total = implode(',', $combined_totals);
            // colors as JSON array of json-encoded color-arrays (to keep compatible with your existing format)
            $saleReturn->color = json_encode($combined_colors);
            $saleReturn->total_amount_Words = $request->total_amount_Words ?? '';
            $saleReturn->total_bill_amount = $request->total_subtotal ?? array_sum($combined_totals);
            $saleReturn->total_extradiscount = $request->total_extra_cost ?? 0;
            $saleReturn->total_net = $request->total_net ?? array_sum($combined_totals);
            $saleReturn->cash = $request->cash ?? 0;
            $saleReturn->card = $request->card ?? 0;
            $saleReturn->change = $request->change ?? 0;
            $saleReturn->total_items = $total_items;
            $saleReturn->return_note = $request->return_note ?? null;
            $saleReturn->save();

            // -----------------------
            // Update original Sale quantities by matching product_code positions.
            // We will try to reduce quantities based on product_code matching. This handles multi-item sales correctly.
            // -----------------------
            // Convert sale comma fields to arrays
            $sale_products  = array_map('trim', explode(',', $sale->product ?? ''));
            $sale_codes     = array_map('trim', explode(',', $sale->product_code ?? ''));
            $sale_qtys      = array_map('trim', explode(',', $sale->qty ?? ''));
            $sale_prices    = array_map('trim', explode(',', $sale->per_price ?? ''));
            $sale_totals    = array_map('trim', explode(',', $sale->per_total ?? ''));

            // initialize numeric arrays safely
            $sale_qtys = array_map(function ($v) {
                return is_numeric($v) ? floatval($v) : 0;
            }, $sale_qtys);
            $sale_totals = array_map(function ($v) {
                return is_numeric($v) ? floatval($v) : 0;
            }, $sale_totals);
            $sale_prices = array_map(function ($v) {
                return is_numeric($v) ? floatval($v) : 0;
            }, $sale_prices);

            // For each returnByCode entry, reduce sale_qtys in first matching positions until consumed
            foreach ($returnByCode as $codeKey => $qtyToReduce) {
                // We stored keys as actual code string or 'ID_xxx' or name; prefer matching code
                $matchCode = $codeKey;
                if (strpos($codeKey, 'ID_') === 0) {
                    // try to resolve to item_code via product id
                    $pid = intval(substr($codeKey, 3));
                    $prod = \App\Models\Product::find($pid);
                    if ($prod) $matchCode = $prod->item_code;
                }

                // search through sale_codes left-to-right and reduce where equal
                for ($j = 0; $j < count($sale_codes) && $qtyToReduce > 0; $j++) {
                    $sc = trim($sale_codes[$j] ?? '');
                    if ($sc === $matchCode) {
                        $availableHere = $sale_qtys[$j] ?? 0;
                        if ($availableHere <= 0) continue;
                        $deduct = min($availableHere, $qtyToReduce);
                        $sale_qtys[$j] = max(0, $sale_qtys[$j] - $deduct);

                        // recalc per_total for that line using sale_prices[$j]
                        $priceHere = $sale_prices[$j] ?? 0;
                        $sale_totals[$j] = $priceHere * $sale_qtys[$j];

                        $qtyToReduce -= $deduct;
                    }
                }
                // if qtyToReduce still >0, we attempted best-effort; ignore remainder
            }

            // update sale fields back
            $sale->qty = implode(',', $sale_qtys);
            $sale->per_total = implode(',', $sale_totals);
            $sale->total_net = array_sum($sale_totals);
            $sale->total_bill_amount = $sale->total_net;
            $sale->total_items = array_sum($sale_qtys);
            // optionally update sale_status: mark as partially returned (1) or fully returned
            $sale->sale_status = 1;
            $sale->save();

            // -----------------------
            // Customer ledger update (simple)
            // -----------------------
            $customer_id = $request->customer;
            $netAmount = $saleReturn->total_net;

            // Only update ledger if customer is numeric (i.e., normal customer)
            if (is_numeric($customer_id) && $customer_id > 0) {
                $ledger = \App\Models\CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance = $ledger->closing_balance - $netAmount;
                    $ledger->save();
                } else {
                    \App\Models\CustomerLedger::create([
                        'customer_id' => $customer_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'opening_balance' => 0 - $netAmount,
                        'closing_balance' => 0 - $netAmount,
                    ]);
                }
            } else {
                // Walk-in Customer: do nothing in ledger
            }
            DB::commit();
            $returnTo = route('sale.index');
            $invoiceUrl = route('saleReturn.invoice', $saleReturn->id) . '?autoprint=1&return_to=' . urlencode($returnTo);
            return redirect()->to($invoiceUrl)->with('success', 'Sale return saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Sale return failed: ' . $e->getMessage());
        }
    }


    public function salereturnview()
    {
        // Fetch all sale returns with the original sale and customer info
        $salesReturns = SalesReturn::with('sale.customer_relation')->orderBy('created_at', 'desc')->get();
        return view('admin_panel.sale.return.index', [
            'salesReturns' => $salesReturns,
        ]);
    }

    public function saleinvoice($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);

        $saleReturn = \App\Models\SalesReturn::where('sale_id', $sale->id)->first();

        // 🔥 IMPORTANT: decide source
        $bill = $sale;
        // items
        $products  = explode(',', $sale->product);
        $codes     = explode(',', $sale->product_code);
        $brands    = explode(',', $sale->brand);
        $units     = explode(',', $sale->unit);
        $prices    = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys      = explode(',', $sale->qty);
        $totals    = explode(',', $sale->per_total);
        $variant_ids = explode(',', $sale->variant_id ?? '');

        $items = [];
        $productIds = array_unique($products);
        $productMap = Product::with('category_relation')->whereIn('id', $productIds)
            ->get()->keyBy('id'); // [id => ProductModel]
            
        $variantMap = \App\Models\ProductVariant::whereIn('id', array_filter($variant_ids))
            ->get()->keyBy('id'); 

        foreach ($products as $index => $p) {

            $qty = (float) ($qtys[$index] ?? 0);

            // ❌ returned item → skip
            if ($qty <= 0) {
                continue;
            }
            
            $vId = $variant_ids[$index] ?? '';
            $vModel = $vId ? ($variantMap[$vId] ?? null) : null;
            $variantName = $vModel ? ($vModel->size_label ?: $vModel->variant_name) : '';
            $productModel = $productMap[$p] ?? null;
            $displayName = $productModel ? $productModel->item_name : $p;
            $catName = ($productModel && $productModel->category_relation) ? $productModel->category_relation->name : 'Uncategorized';
            
            if ($variantName && $productModel) {
                // Remove redundant info like "(1.000 KG)" from variant name
                $cleanVariant = preg_replace('/\s*\([\d.]+\s*KG\)/i', '', $variantName);
                
                // Remove product name from variant name if it repeats
                $cleanVariant = trim(str_ireplace($productModel->item_name, '', $cleanVariant));
                $cleanVariant = ltrim($cleanVariant, ' -');
                
                if ($cleanVariant !== '') {
                    $displayName .= ' (' . $cleanVariant . ')';
                }
            } else if ($variantName) {
                $displayName .= ' - ' . $variantName;
            }

            $unit = $units[$index] ?? '';
            $price = (float)($prices[$index] ?? 0);

            // WEIGHT CONVERSION LOGIC
            if ($vModel && $productModel && strtolower($productModel->unit_type ?? '') === 'kg' && $vModel->size_value > 0) {
                $multiplier = 1;
                $sUnit = strtolower($vModel->size_unit ?? 'kg');
                if ($sUnit === 'kg') $multiplier = (float)$vModel->size_value;
                elseif (in_array($sUnit, ['gm','gram','grams'])) $multiplier = (float)$vModel->size_value / 1000;
                
                $qty = $qty * $multiplier;
                if ($multiplier > 0) $price = $price / $multiplier;
                $unit = 'KG';
            } else if (empty($unit) || is_numeric($unit)) {
                if ($vModel && !empty($vModel->size_unit)) {
                    $unit = strtoupper($vModel->size_unit);
                } else if ($productModel && !empty($productModel->unit_type)) {
                    $unit = strtoupper($productModel->unit_type);
                } else {
                    $unit = 'PIECE';
                }
            }

            $items[] = [
                'item_name' => $displayName,
                'category'  => $catName,
                'item_code' => $codes[$index] ?? '',
                'brand'     => $brands[$index] ?? '',
                'unit'      => $unit,
                'price'     => $price,
                'discount'  => (float) ($discounts[$index] ?? 0),
                'qty'       => $qty,
                'total'     => (float) ($totals[$index] ?? 0),
            ];
        }

        $mode = request('mode', 'invoice');

        // Override items if we only want to print the newly added ones (Diff)
        if ($mode === 'token_only' && session()->has('kot_items_json')) {
            $diffItems = json_decode(session('kot_items_json'), true);
            if (is_array($diffItems) && count($diffItems) > 0) {
                $filteredItems = [];
                foreach ($diffItems as $diff) {
                    $pid = $diff['prod_id'];
                    $vid = $diff['var_id'] ?? '';
                    $productModel = $productMap[$pid] ?? null;
                    $vModel = $vid ? ($variantMap[$vid] ?? null) : null;
                    $variantName = $vModel ? ($vModel->size_label ?: $vModel->variant_name) : '';

                    $displayName = $productModel ? $productModel->item_name : $pid;
                    if ($variantName) $displayName .= ' - ' . $variantName;

                    $catName = ($productModel && $productModel->category_relation) ? $productModel->category_relation->name : 'Uncategorized';

                    $filteredItems[] = [
                        'item_name' => $displayName,
                        'category'  => $catName,
                        'item_code' => '',
                        'brand'     => '',
                        'unit'      => '',
                        'price'     => (float) $diff['price'],
                        'discount'  => 0,
                        'qty'       => (float) $diff['qty'],
                        'total'     => (float) $diff['qty'] * (float) $diff['price'],
                    ];
                }
                $items = $filteredItems;
            }
        }

        return view('admin_panel.sale.saleinvoice', [
            'sale'       => $sale,
            'bill'       => $bill,        // 👈 unified object
            'saleItems'  => $items,
            'saleReturn' => $saleReturn,
            'mode'       => $mode,
        ]);
    }
    public function saleedit($id)
    {
        $sale = Sale::findOrFail($id);

        $customers = Customer::all();

        $products   = explode(',', $sale->product);
        $codes      = explode(',', $sale->product_code);
        $brands     = explode(',', $sale->brand);
        $units      = explode(',', $sale->unit);
        $prices     = explode(',', $sale->per_price);
        $discounts  = explode(',', $sale->per_discount);
        $qtys       = explode(',', $sale->qty);
        $totals     = explode(',', $sale->per_total);

        // Expecting sale->color to be JSON array (each element a JSON-encoded note or plain string)
        $colors_json = json_decode($sale->color, true);
        if (!is_array($colors_json)) {
            $colors_json = [];
        }

        $items = [];

        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            // Get note safely:
            $note_value = '';
            if (isset($colors_json[$index])) {
                // If stored as JSON-encoded string, decode; else use directly
                $maybe = $colors_json[$index];

                if (is_string($maybe)) {
                    // try json decode in case it's JSON string
                    $try = json_decode($maybe, true);
                    if ($try !== null) {
                        // decoded OK (could be array or string)
                        if (is_array($try)) {
                            // join array into newline-separated text
                            $note_value = implode("\n", $try);
                        } else {
                            $note_value = (string)$try;
                        }
                    } else {
                        // plain string note
                        $note_value = $maybe;
                    }
                } elseif (is_array($maybe)) {
                    // join into text
                    $note_value = implode("\n", $maybe);
                } else {
                    $note_value = (string)$maybe;
                }
            }

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'       => $product->unit ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => floatval($qtys[$index] ?? 1), // <-- use floatval
                'total'      => floatval($totals[$index] ?? 0),
                'note'       => $note_value,
            ];
        }

        $categories = \App\Models\Category::orderBy('name')->get();
        $tables = \App\Models\Table::orderBy('table_name')->get();

        return view('admin_panel.sale.saleedit', [
            'sale' => $sale,
            'Customer' => $customers,
            'saleItems' => $items,
            'categories' => $categories,
            'tables' => $tables,
        ]);
    }

    public function updatesale(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // --- Arrays from request ---
            $product_ids    = $request->product_id ?? [];
            $variant_ids    = $request->variant_id ?? [];
            $product_names  = $request->product_name ?? [];
            $product_codes  = $request->item_code ?? [];
            $brands         = $request->brand ?? [];
            $units          = $request->unit ?? [];
            $prices         = $request->price ?? [];
            $discounts      = $request->item_disc ?? [];
            $quantities     = $request->qty ?? [];
            $totals         = $request->total ?? [];
            $colors         = $request->color ?? [];

            $combined_products    = [];
            $combined_codes       = [];
            $combined_brands      = [];
            $combined_units       = [];
            $combined_prices      = [];
            $combined_discounts   = [];
            $combined_qtys        = [];
            $combined_totals      = [];
            $combined_colors      = [];
            $combined_variant_ids = [];

            $total_items = 0;

            // --- Get old sale to update stock differences ---
            $sale = Sale::findOrFail($id);
            $old_quantities = explode(',', $sale->qty);
            $old_variant_ids = explode(',', $sale->variant_id);
            $old_product_ids = explode(',', $sale->product);

            // Create a map of existing items for easy diff: key = pid_vid
            $oldMap = [];
            foreach ($old_product_ids as $idx => $pid) {
                $vid = $old_variant_ids[$idx] ?? '';
                $key = $pid . '_' . ($vid ?: '0');
                $oldMap[$key] = ($oldMap[$key] ?? 0) + floatval($old_quantities[$idx] ?? 0);
            }

            foreach ($product_ids as $index => $product_id) {
                $vId   = $variant_ids[$index] ?? '';
                $key   = $product_id . '_' . ($vId ?: '0');
                $qty   = isset($quantities[$index]) ? floatval($quantities[$index]) : 0;
                $price = isset($prices[$index]) ? floatval($prices[$index]) : 0;

                if (!$product_id || $qty <= 0 || $price <= 0) continue;

                $combined_products[]    = $product_id;
                $combined_variant_ids[] = $vId;
                $combined_codes[]       = $product_codes[$index] ?? '';
                $combined_brands[]      = $brands[$index] ?? '';
                $combined_units[]       = $units[$index] ?? '';
                $combined_prices[]      = $prices[$index];
                $combined_discounts[]   = $discounts[$index] ?? 0;
                $combined_qtys[]        = $qty;
                $combined_totals[]      = $totals[$index] ?? 0;
                $combined_colors[]      = $colors[$index] ?? '';

                $total_items += $qty;

                // --- Update stock ---
                $old_qty = $oldMap[$key] ?? 0;
                $qty_diff = $qty - $old_qty;
                
                // Clear from map so we know it's handled
                unset($oldMap[$key]);

                $prodModel = \App\Models\Product::find($product_id);
                $isGram = $prodModel && $prodModel->unit_type === 'kg';
                
                $dbVariantId = $vId;
                $deductQtyDiff = $qty_diff;

                if ($isGram) {
                    $dbVariantId = null; // Always manage stock on the main product for KG
                    if ($vId) {
                        $vModel = \App\Models\ProductVariant::find($vId);
                        if ($vModel) {
                            $kgSize = floatval($vModel->size_value);
                            if ($vModel->size_unit === 'kg') {
                                $deductQtyDiff = ($kgSize * $qty_diff * 1000);
                            } else {
                                $deductQtyDiff = ($kgSize * $qty_diff);
                            }
                        }
                    } else {
                        $deductQtyDiff = $qty_diff * 1000;
                    }
                }

                $stockQuery = \App\Models\Stock::where('product_id', $product_id)
                                                ->where('branch_id', 1)
                                                ->where('warehouse_id', 1);
                if ($dbVariantId) {
                    $stockQuery->where('variant_id', $dbVariantId);
                } else {
                    $stockQuery->whereNull('variant_id');
                }
                $stock = $stockQuery->first();

                if ($stock) {
                    $stock->qty -= $deductQtyDiff;
                    $stock->save();
                } else {
                    \App\Models\Stock::create([
                        'branch_id'  => 1,
                        'warehouse_id' => 1,
                        'product_id' => $product_id,
                        'variant_id' => $dbVariantId ?: null,
                        'qty'        => -$deductQtyDiff,
                    ]);
                }
            }

            // Handle items that were removed (old qty becomes 0, so diff = -old_qty)
            foreach ($oldMap as $key => $old_qty) {
                if ($old_qty <= 0) continue;
                $parts = explode('_', $key);
                $pid = $parts[0];
                $vid = $parts[1] === '0' ? null : $parts[1];

                $prodModel = \App\Models\Product::find($pid);
                $isGram = $prodModel && $prodModel->unit_type === 'kg';
                
                $dbVariantId = $vid;
                $addBackQty = $old_qty;

                if ($isGram) {
                    $dbVariantId = null;
                    if ($vid) {
                        $vModel = \App\Models\ProductVariant::find($vid);
                        if ($vModel) {
                            $kgSize = floatval($vModel->size_value);
                            if ($vModel->size_unit === 'kg') {
                                $addBackQty = ($kgSize * $old_qty * 1000);
                            } else {
                                $addBackQty = ($kgSize * $old_qty);
                            }
                        }
                    } else {
                        $addBackQty = $old_qty * 1000;
                    }
                }

                $stockQuery = \App\Models\Stock::where('product_id', $pid)
                                                ->where('branch_id', 1)
                                                ->where('warehouse_id', 1);
                
                if ($dbVariantId) {
                    $stockQuery->where('variant_id', $dbVariantId);
                } else {
                    $stockQuery->whereNull('variant_id');
                }
                
                $stock = $stockQuery->first();
                if ($stock) {
                    $stock->qty += $addBackQty; // Add back the removed quantity
                    $stock->save();
                }
            }

            // --- Save updated Sale ---
            $old_total = $sale->total_net;

            $sale->customer            = $request->customer;
            $sale->reference           = $request->reference;
            $sale->product             = implode(',', $combined_products);
            $sale->variant_id          = implode(',', $combined_variant_ids);
            $sale->product_code        = implode(',', $combined_codes);
            $sale->brand               = implode(',', $combined_brands);
            $sale->unit                = implode(',', $combined_units);
            $sale->per_price           = implode(',', $combined_prices);
            $sale->per_discount        = implode(',', $combined_discounts);
            $sale->qty                 = implode(',', $combined_qtys);
            $sale->per_total           = implode(',', $combined_totals);
            $sale->color               = json_encode($combined_colors);
            $sale->total_amount_Words  = $request->total_amount_Words;
            $sale->total_bill_amount   = $request->total_subtotal;
            $sale->total_extradiscount = $request->total_extra_cost;
            $sale->total_net           = $request->total_net;
            $sale->cash                = $request->cash;
            $sale->card                = $request->card;
            $sale->change              = $request->change;
            $sale->total_items         = $total_items;
            $sale->save();

            // --- Ledger Update ---
            $customer_id = $request->customer;

            if ($customer_id !== 'Walk-in Customer') { // ✅ Only update ledger for registered customers
                $ledger = CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
                $difference = $request->total_net - $old_total;

                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  += $difference;
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id'      => $customer_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'closing_balance'  => $request->total_net,
                        'opening_balance'  => $request->total_net,
                    ]);
                }
            }

            DB::commit();
            $returnTo   = route('sale.add'); // ya route('sale.edit', $sale->id) agar wapas edit chaho
            $invoiceUrl = route('sales.invoice', $sale->id)
                . '?return_to=' . urlencode($returnTo)
                . '&autoprint=1';

            return redirect()->to($invoiceUrl)
                ->with('success', 'Sale updated successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }


    public function saledc($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);

        // Decode sale pivot or comma fields
        $products = explode(',', $sale->product);
        $codes    = explode(',', $sale->product_code);
        $brands   = explode(',', $sale->brand);
        $units    = explode(',', $sale->unit);
        $prices   = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys     = explode(',', $sale->qty);
        $totals   = explode(',', $sale->per_total);
        $colors_json = json_decode($sale->color, true);

        $items = [];

        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'       => $product->unit ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => intval($qtys[$index] ?? 1),
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.saledc', [
            'sale' => $sale,
            'saleItems' => $items,
        ]);
    }

    public function salerecepit($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);

        // Decode sale pivot or comma fields
        $products = explode(',', $sale->product);
        $codes    = explode(',', $sale->product_code);
        $brands   = explode(',', $sale->brand);
        $units    = explode(',', $sale->unit);
        $prices   = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys     = explode(',', $sale->qty);
        $totals   = explode(',', $sale->per_total);
        $vids     = explode(',', $sale->variant_id ?? '');
        $colors_json = json_decode($sale->color, true);

        $vObjects = \App\Models\ProductVariant::whereIn('id', array_filter($vids))->get()->keyBy('id');

        $pObjects = Product::with('brand')->whereIn('id', array_filter($products))->get()->keyBy('id');

        foreach ($products as $index => $pId) {
            $product = $pObjects[$pId] ?? null;
            if (!$product) {
                // Fallback search by name if ID fails (for old data)
                $product = Product::where('item_name', trim($pId))
                    ->orWhere('item_code', trim($codes[$index] ?? ''))
                    ->first();
            }

            $vId = trim($vids[$index] ?? '');
            $vModel = $vId ? ($vObjects[$vId] ?? null) : null;
            $variantName = $vModel ? ($vModel->size_label ?: $vModel->variant_name) : '';
            
            $displayName = $product ? $product->item_name : $pId;
            if ($variantName && $product) {
                // Remove redundant info like "(1.000 KG)" from variant name
                $cleanVariant = preg_replace('/\s*\([\d.]+\s*KG\)/i', '', $variantName);
                
                // Remove product name from variant name if it repeats
                $cleanVariant = trim(str_ireplace($product->item_name, '', $cleanVariant));
                $cleanVariant = ltrim($cleanVariant, ' -');
                
                if ($cleanVariant !== '') {
                    $displayName .= ' (' . $cleanVariant . ')';
                }
            } else if ($variantName) {
                $displayName .= ' - ' . $variantName;
            }

            $price = floatval($prices[$index] ?? 0);
            $qty = floatval($qtys[$index] ?? 1);
            $unit = $units[$index] ?? '';

            if ($vModel && $product && strtolower($product->unit_type ?? '') === 'kg' && $vModel->size_value > 0) {
                $multiplier = 1;
                $sUnit = strtolower($vModel->size_unit ?? 'kg');
                if ($sUnit === 'kg') $multiplier = (float)$vModel->size_value;
                elseif (in_array($sUnit, ['gm','gram','grams'])) $multiplier = (float)$vModel->size_value / 1000;
                
                $qty = $qty * $multiplier;
                if ($multiplier > 0) $price = $price / $multiplier;
                $unit = 'KG';
            } else if (empty($unit) || is_numeric($unit)) {
                if ($vModel && !empty($vModel->size_unit)) {
                    $unit = strtoupper($vModel->size_unit);
                } else if ($product && !empty($product->unit_type)) {
                    $unit = strtoupper($product->unit_type);
                } else {
                    $unit = 'PIECE';
                }
            }

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $displayName,
                'item_code'  => $product ? $product->item_code : ($codes[$index] ?? ''),
                'brand'      => $product && $product->brand ? $product->brand->name : ($brands[$index] ?? ''),
                'unit'       => $unit ?: 'PIECE',
                'price'      => $price,
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => $qty,
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.salerecepit', [
            'sale' => $sale,
            'saleItems' => $items,
        ]);
    }

    public function retrninvoice($id)
    {
        $return = \App\Models\SalesReturn::with('sale.customer_relation')->findOrFail($id);

        $products   = explode(',', $return->product);
        $codes      = explode(',', $return->product_code);
        $brands     = explode(',', $return->brand);
        $units      = explode(',', $return->unit);
        $prices     = explode(',', $return->per_price);
        $discounts  = explode(',', $return->per_discount);
        $qtys       = explode(',', $return->qty);
        $totals     = explode(',', $return->per_total);
        $colors_json = json_decode($return->color, true);

        $items = [];

        foreach ($products as $index => $p) {

            $qty = intval($qtys[$index] ?? 0);

            // ❌ qty 0 ya empty ho to skip
            if ($qty <= 0) {
                continue;
            }

            $product = \App\Models\Product::with('category_relation', 'brand')->where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'category'   => ($product && $product->category_relation) ? $product->category_relation->name : 'Uncategorized',
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => ($product && $product->brand) ? $product->brand->name : ($brands[$index] ?? ''),
                'unit'       => ($product && $product->unit_type) ? $product->unit_type : ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => (float)$qty,
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index])
                    ? json_decode($colors_json[$index], true)
                    : [],
            ];
        }

        $unitTotals = [
            'Pc'  => 0,
            'Mtr' => 0,
            'Yd'  => 0,
        ];

        foreach ($items as $item) {
            $unit = strtolower($item['unit']);

            if (in_array($unit, ['pc', 'pcs', 'piece'])) {
                $unitTotals['Pc'] += $item['qty'];
            }

            if (in_array($unit, ['mtr', 'meter'])) {
                $unitTotals['Mtr'] += $item['qty'];
            }

            if (in_array($unit, ['yd', 'yard'])) {
                $unitTotals['Yd'] += $item['qty'];
            }
        }

        return view('admin_panel.sale.return.salereturnrecepit', [
            'return' => $return,
            'returnItems' => $items,
            'unitTotals'   => $unitTotals,
        ]);
    }
}
