<?php

namespace App\Http\Controllers;

use App\Models\ProductBooking;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBookingController extends Controller
{
    public function index()
    {
        $bookings = ProductBooking::with('customer_relation', 'productt')->latest()->get();
        // dd($bookings);
        return view('admin_panel.booking.index', compact('bookings'));
    }
    public function receipt($id)
    {
        $booking = ProductBooking::with('customer_relation')->findOrFail($id);

        // --- Parsing logic (adapted from SaleController@saleinvoice) ---
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
        $productMap = Product::with('category_relation')->whereIn('id', $productIds)->get()->keyBy('id');
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
                'item_name' => $displayName,
                'category'  => $catName,
                'item_code' => $codes[$index] ?? '',
                'brand'     => $brands[$index] ?? '',
                'unit'      => $unit,
                'price'     => $price,
                'discount'  => (float)($discounts[$index] ?? 0),
                'qty'       => $qty,
                'total'     => (float)($totals[$index] ?? 0),
            ];
        }

        return view('admin_panel.booking.receipt', [
            'booking'      => $booking,
            'bookingItems' => $items,
        ]);
    }

    public function create()
    {
        $products = Product::get();
        $Customer = Customer::get();
        return view('admin_panel.booking.create', compact('products', 'Customer'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $product_ids     = $request->product_id;
            $product_names   = $request->product_id;
            $product_codes   = $request->item_code;
            $brands          = $request->uom;
            $units           = $request->unit;
            $prices          = $request->price;
            $discounts       = $request->item_disc;
            $quantities      = $request->qty;
            $totals          = $request->total;
            $colors          = $request->color;

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

            foreach ($product_ids as $index => $product_id) {
                $qty   = $quantities[$index] ?? 0;
                $price = $prices[$index] ?? 0;

                if (!$product_id || !$qty || !$price) {
                    continue;
                }

                $combined_products[]   = $product_names[$index] ?? '';
                $combined_codes[]      = $product_codes[$index] ?? '';
                $combined_brands[]     = $brands[$index] ?? '';
                $combined_units[]      = $units[$index] ?? '';
                $combined_prices[]     = $prices[$index] ?? 0;
                $combined_discounts[]  = $discounts[$index] ?? 0;
                $combined_qtys[]       = $quantities[$index] ?? 0;
                $combined_totals[]     = $totals[$index] ?? 0;

                $combined_colors[] = $colors[$index] ?? '';

                $total_items += $qty;
            }

            $booking = new ProductBooking();
            $booking->customer            = $request->customer;
            $booking->reference           = $request->reference;
            $booking->product             = implode(',', $combined_products);
            $booking->product_code        = implode(',', $combined_codes);
            $booking->brand               = implode(',', $combined_brands);
            $booking->unit                = implode(',', $combined_units);
            $booking->per_price           = implode(',', $combined_prices);
            $booking->per_discount        = implode(',', $combined_discounts);
            $booking->qty                 = implode(',', $combined_qtys);
            $booking->per_total           = implode(',', $combined_totals);
            $booking->color               = json_encode($combined_colors);

            $booking->total_amount_Words = $request->total_amount_Words;
            $booking->total_bill_amount  = $request->total_subtotal;
            $booking->total_extradiscount = $request->total_extra_cost;
            $booking->total_net          = $request->total_net;

            $booking->cash   = $request->cash;
            $booking->card   = $request->card;
            $booking->change = $request->change;
            $booking->total_items = $total_items;
            $booking->booking_date = now();
            $booking->save();

            DB::commit();
            return back()->with('success', 'Product booking saved (no stock reduced).');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $booking = ProductBooking::findOrFail($id);
        $booking->delete();

        return redirect()->back()->with('success', 'Booking deleted successfully.');
    }
}
