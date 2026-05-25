<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SALE RECEIPT - {{ $sale->invoice_no }}</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&display=swap');

    * {
      box-sizing: border-box;
    }

    html,
    body {
      margin: 0;
      padding: 0;
      background: #fff;
      color: #000;
      font-family: 'Arial', sans-serif; /* Switching to Arial for better density on some printers */
      font-size: 14px; /* Slightly larger */
      line-height: 1.3;
      font-weight: 700; /* Bold by default for better visibility */
    }

    /* Action Buttons - Hidden in Print */
    .actions {
      max-width: 80mm;
      margin: 10px auto;
      display: flex;
      gap: 10px;
      justify-content: center;
      padding: 10px;
      background: #f0f0f0;
      border-radius: 8px;
    }

    .btn {
      padding: 8px 16px;
      font-size: 14px;
      font-weight: 800;
      cursor: pointer;
      border: 2px solid #000;
      background: #fff;
      border-radius: 4px;
    }

    .btn:hover {
      background: #eee;
    }

    /* Receipt Container */
    .receipt-container {
      width: 100%;
      max-width: 80mm; /* Limit on screen, but adapt to smaller screens/printers */
      margin: 0 auto;
      padding: 2mm;
      background: #fff;
      overflow: hidden; /* Prevent horizontal overflow */
    }

    .center {
      text-align: center;
    }

    .bold {
      font-weight: 900;
    }

    .line {
      border-top: 2px dashed #000; /* Thicker dashed line */
      margin: 6px 0;
    }

    .double-line {
      border-top: 2px solid #000;
      border-bottom: 2px solid #000;
      height: 4px;
      margin: 6px 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 3px 0;
      vertical-align: top;
      color: #000 !important;
    }

    /* Header Section */
    .store-name {
      font-size: 22px;
      font-weight: 900;
      margin: 5px 0;
      text-transform: uppercase;
    }

    .store-info {
      font-size: 13px;
      margin: 2px 0;
      font-weight: 800;
    }

    .receipt-title {
      font-size: 18px;
      font-weight: 900;
      margin: 10px 0;
      border-top: 2px solid #000;
      border-bottom: 2px solid #000;
      padding: 5px 0;
      text-transform: uppercase;
    }

    /* Metadata Table */
    .meta-table th {
      text-align: left;
      font-weight: 900;
      width: 45%;
    }

    .meta-table td {
      text-align: left;
      font-weight: 800;
    }

    /* Items Table */
    .items-table thead th {
      border-top: 2px solid #000;
      border-bottom: 2px solid #000;
      font-size: 14px;
      text-align: left;
      font-weight: 900;
    }

    .items-table .col-amount { text-align: right; white-space: nowrap; padding-left: 5px; }
    .items-table .col-price { text-align: right; white-space: nowrap; padding-left: 5px; }
    .items-table .col-qty { text-align: right; white-space: nowrap; padding-left: 5px; }
    .items-table th, .items-table td { padding: 3px 2px; }

    .category-row {
      text-align: center;
      font-weight: 900;
      padding: 6px 0;
      background: #eee;
      border-bottom: 1px solid #000;
      font-size: 14px;
    }

    .item-row td {
      font-size: 14px;
      padding-top: 5px;
    }

    /* Totals Section */
    .totals-table th {
      text-align: left;
      font-weight: 800;
    }

    .totals-table td {
      text-align: right;
      font-weight: 900;
    }

    .totals-table .grand-total-row th,
    .totals-table .grand-total-row td {
      font-weight: 900;
      font-size: 18px;
      border-top: 2px solid #000;
      border-bottom: 2px solid #000;
      padding: 6px 0;
    }

    .payment-table {
      margin-top: 10px;
      border: 2px solid #000;
    }

    .payment-table th, .payment-table td {
      padding: 5px 10px;
      border: 1px solid #000;
      font-weight: 900;
    }

    .footer {
      margin-top: 15px;
      font-size: 13px;
      font-weight: 800;
    }

    @media print {
      .actions {
        display: none !important;
      }

      @page {
        margin: 0;
        size: 80mm auto;
      }

      html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        height: auto;
        -webkit-print-color-adjust: exact;
      }

      .receipt-container {
        width: 74mm !important; /* Printable area for 80mm paper is ~72-76mm */
        max-width: 100% !important;
        margin: 0 auto !important;
        padding: 2mm !important;
        page-break-inside: avoid;
        page-break-after: avoid;
      }
      
      table, tr, td, th, tbody, thead, tfoot {
        page-break-inside: avoid !important;
      }

      table {
        table-layout: fixed;
        width: 100%;
        word-wrap: break-word;
      }

      th, td {
        white-space: normal !important;
        padding: 1px !important;
      }
      
      body {
        font-size: 10px;
      }
      .store-name { font-size: 14px; }
      .store-info { font-size: 10px; }
      .receipt-title { font-size: 12px; }
      .items-table thead th { font-size: 10px; }
      .item-row td { font-size: 10px; }
      .totals-table .grand-total-row th,
      .totals-table .grand-total-row td { font-size: 12px; }
    }

    .page-break {
      page-break-after: always;
    }
  </style>
</head>

<body>

  <div class="actions">
    <button class="btn" id="btnBack" type="button">BACK</button>
    <button class="btn" id="btnPrint" type="button">PRINT</button>
  </div>

  @php
    $mode = $mode ?? 'invoice';
  @endphp

  {{-- Token Logic (KOT) --}}
  @if(in_array($mode, ['token_only', 'token_and_invoice']))
      @php
          $categoryGroups = collect($saleItems)->groupBy('category');
      @endphp

      @foreach($categoryGroups as $categoryName => $groupItems)
      <div class="receipt-container @if(!$loop->last || $mode == 'token_and_invoice') page-break @endif">
          <div class="center">
              <img src="{{ asset('assets/images/logo.jpeg') }}" alt="Logo" style="max-height: 60px; margin-bottom: 5px;">
              <div class="store-name">Bin Sultan</div>
              <div style="font-weight:bold; border:1px dashed #000; padding:4px; margin:5px 0;">TOKEN ({{ strtoupper($categoryName) }})</div>
              <p style="margin:5px 0; font-size:14px; font-weight:bold;">Order: {{ $sale->order_type ?? 'Walk-in' }} @if($sale->table_id) | Table: {{ \App\Models\Table::find($sale->table_id)->table_name ?? '' }} @endif</p>
          </div>
          <div class="line"></div>
          <table class="meta-table">
              <tr>
                  <th>Invoice No</th>
                  <td>: {{ $sale->invoice_no ?? 'N/A' }}</td>
              </tr>
              <tr>
                  <th>Date</th>
                  <td>: {{ optional($sale->created_at)->format('d-M-Y h:i A') }}</td>
              </tr>
          </table>
          <div class="line"></div>
          <table class="items-table">
              <thead>
                  <tr>
                      <th>Item</th>
                      <th class="col-qty">Qty</th>
                  </tr>
              </thead>
              <tbody>
                  @foreach($groupItems as $item)
                  <tr>
                      <td style="font-weight:bold;">{{ $item['item_name'] }}</td>
                      <td class="col-qty" style="font-weight:bold;">{{ $item['qty'] }}</td>
                  </tr>
                  @endforeach
              </tbody>
          </table>
          <div class="line"></div>
          <div class="center" style="margin-top:10px;">
              <p>*** Thank You ***</p>
          </div>
      </div>
      @endforeach
  @endif

  {{-- Main Invoice --}}
  @if(in_array($mode, ['invoice', 'token_and_invoice']))
  <div class="receipt-container">
    <!-- Header -->
    <div class="center">
      <img src="{{ asset('assets/images/logo.jpeg') }}" alt="Logo" style="max-height: 80px; margin-bottom: 5px;">
      <div class="store-name">Bin Sultan</div>
      <div class="store-info">Sweets & Bakers</div>
      <div class="store-info">A-16/B Block-D Unit No. 6 Latifabad, Hyderabad</div>
      <div class="store-info">Phone: 0334 2615888</div>
      
      <div class="receipt-title">Sale Receipt</div>
    </div>

    <!-- Metadata -->
    <table class="meta-table">
      <tr>
        <th>Invoice #</th>
        <td>: {{ $sale->invoice_no }}</td>
      </tr>
      <tr>
        <th>Operator Name</th>
        <td>: {{ auth()->user()->name ?? 'Admin' }}</td>
      </tr>
      <tr>
        <th>Order Type</th>
        <td>: {{ $sale->order_type ?? 'Walk-in' }}</td>
      </tr>
      @if(isset($sale->order_type) && $sale->order_type == 'Dine-in' && $sale->table_id)
      <tr>
        <th>Table No</th>
        <td>: {{ $sale->table->table_name ?? 'N/A' }}</td>
      </tr>
      @endif
      <tr>
        <th>Invoice Date</th>
        <td>: {{ optional($sale->created_at)->format('D, d-M-Y h:i A') }}</td>
      </tr>
    </table>

    <div style="margin-top:10px;"></div>

    <!-- Items Table -->
    <table class="items-table">
      <thead>
        <tr>
          <th style="width: 40%;">Item Name</th>
          <th class="col-price" style="width: 20%;">Price</th>
          <th class="col-qty" style="width: 20%;">Qty/Wt</th>
          <th class="col-amount" style="width: 20%;">Amount</th>
        </tr>
      </thead>
      <tbody>
        @php
          $categoryGroups = collect($saleItems)->groupBy('category');
          $totalQty = 0;
          $totalItemsCount = count($saleItems);
        @endphp

        @foreach($categoryGroups as $categoryName => $groupItems)
          @foreach($groupItems as $item)
            @php 
                $totalQty += (float)$item['qty']; 
                $isKg = strtolower($item['unit']) === 'kg';
                $displayQty = (float)$item['qty'];
                $displayUnit = $item['unit'];
                
                // If weight is less than 1kg, convert to grams for display
                if ($isKg && $displayQty < 1) {
                    $displayQty = round($displayQty * 1000);
                    $displayUnit = 'GRAM';
                } else {
                    $displayQty = (float)number_format($displayQty, 3);
                }

                // Get custom label from 'color' field (stored as JSON array in Sale model)
                $customLabel = '';
                $cleanItemName = $item['item_name'];

                if (!empty($bill->color)) {
                    $colors = json_decode($bill->color, true);
                    if (isset($colors[$loop->parent->index + $loop->index])) {
                        $label = $colors[$loop->parent->index + $loop->index];
                        $decoded = json_decode($label, true);
                        if (json_last_error() === JSON_ERROR_NONE && !is_null($decoded)) {
                            $cleanLabel = is_array($decoded) ? ($decoded[0] ?? '') : $decoded;
                        } else {
                            $cleanLabel = $label;
                        }
                        
                        if (!empty($cleanLabel) && strtolower($cleanLabel) !== strtolower($item['item_name'])) {
                            $customLabel = ' (' . $cleanLabel . ')';
                            
                            // Remove common unit suffixes from name to avoid redundancy
                            // like "Barfi (1 Kg) (100g)" -> "Barfi (100g)"
                            $cleanItemName = preg_replace('/\s*\(1\s*(kg|kg\.|pound|lb|gm|unit)\)\s*/i', '', $cleanItemName);
                        }
                    }
                }
            @endphp
            <tr class="item-row">
              <td style="width: 40%;" class="bold">
                  {{ $cleanItemName }}
                  @if(isset($item['discount']) && $item['discount'] > 0)
                      <br><small style="font-weight:normal;font-size:12px;">Disc: Rs {{ number_format($item['discount'], 0) }}</small>
                  @endif
              </td>
              <td class="col-price" style="width: 20%;">{{ number_format($item['price'], 0) }}</td>
              <td class="col-qty" style="width: 20%;">{{ $displayQty }} {{ $displayUnit }}</td>
              <td class="col-amount" style="width: 20%; font-weight: bold;">{{ number_format($item['total'], 0) }}</td>
            </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>

    <div class="line"></div>

    <table class="totals-table">
      <tr>
        <th style="width: 40%;">Total Item</th>
        <td style="text-align: left;">: {{ $totalItemsCount }}</td>
      </tr>
    </table>

    <div class="line"></div>

    <!-- Summary -->
    <table class="totals-table">
      <tr>
        <th>Gross Amount</th>
        <td>{{ number_format($bill->total_bill_amount ?? 0, 0) }}</td>
      </tr>
      @if(!empty($bill->total_extradiscount) && $bill->total_extradiscount > 0)
      <tr>
        @php 
          $discPercent = ($bill->total_bill_amount > 0) ? ($bill->total_extradiscount / $bill->total_bill_amount * 100) : 0;
        @endphp
        <th>On Invoice Discount ({{ number_format($discPercent, 2) }}%)</th>
        <td>{{ number_format($bill->total_extradiscount, 0) }}</td>
      </tr>
      @endif
      
      {{-- Tax removed as per user request --}}

      <tr class="grand-total-row">
        <th>Net Amount</th>
        <td>{{ number_format($bill->total_net ?? 0, 0) }}</td>
      </tr>
    </table>



    <!-- Payment Section -->
    @if($bill->card > 0)
    <div style="border: 3px solid #000; padding: 5px; margin: 10px 0; text-align: center;">
      <div style="font-size: 20px; font-weight: 900; text-transform: uppercase;">*** CARD PAID ***</div>
      <div style="font-size: 18px; font-weight: 900;">Amount: {{ number_format($bill->card, 0) }}</div>
    </div>
    @endif

    <table class="payment-table" style="width: 100%;">
      @if($bill->cash > 0)
      <tr>
        <th style="width: 60%;">Cash Paid</th>
        <td style="text-align: right;">{{ number_format($bill->cash, 0) }}</td>
      </tr>
      <tr>
        <th>Cash Back</th>
        <td style="text-align: right;">{{ number_format($bill->change ?? 0, 0) }}</td>
      </tr>
      @endif
    </table>

    <!-- Footer -->
    <div class="footer center" style="font-weight: normal;">
      <p style="margin: 5px 0;">Please check bakery items at the time of purchase</p>
      <p style="margin: 5px 0;">Bakery & Sweets items are non-returnable</p>
      <p style="margin: 2px 0; font-size: 11px; font-weight: normal;">Develop By: ProWave Software Solutions</p>
      <p style="margin: 2px 0; font-size: 11px; font-weight: normal;">+92 317 3836 223 | +92 317 3859 647</p>
      <p style="margin: 10px 0;">*** Thank you for the visit ***</p>
    </div>
  </div>
  @endif

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const query = new URLSearchParams(window.location.search);
      const returnTo = query.get('return_to') || "{{ route('sale.index') }}";
      const autoprint = query.get('autoprint') === '1';

      document.getElementById('btnPrint').addEventListener('click', () => window.print());

      document.getElementById('btnBack').addEventListener('click', () => {
        if (returnTo) {
          try {
            window.location.href = decodeURIComponent(returnTo);
          } catch (e) {
            if (history.length > 1) history.back();
            else window.close();
          }
        } else {
          if (history.length > 1) history.back();
          else window.close();
        }
      });

      if (autoprint) {
        setTimeout(() => {
          window.print();
          const redirectAfterPrint = () => {
            try {
              const url = decodeURIComponent(returnTo);
              const a = document.createElement('a');
              a.href = url;
              if (a.origin === location.origin) {
                window.location.href = url;
              } else {
                window.location.href = "{{ route('sale.add') }}";
              }
            } catch (e) {
              if (history.length > 1) history.back();
              else window.location.href = "{{ route('sale.add') }}";
            }
          };

          if ('onafterprint' in window) {
            window.onafterprint = redirectAfterPrint;
          }
          setTimeout(redirectAfterPrint, 2000);
        }, 500);
      }

      document.addEventListener('keydown', (e) => {
        if ((e.altKey || e.metaKey) && e.key.toLowerCase() === 'b') {
          e.preventDefault();
          document.getElementById('btnBack').click();
        }
      });
    });
  </script>
</body>

</html>