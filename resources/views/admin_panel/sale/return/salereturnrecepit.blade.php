<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RETURN RECEIPT - {{ $return->id }}</title>
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
      font-family: 'Arial', sans-serif;
      font-size: 14px;
      line-height: 1.3;
      font-weight: 700;
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
      width: 80mm;
      margin: 0 auto;
      padding: 5mm 2mm;
      background: #fff;
    }

    .center {
      text-align: center;
    }

    .bold {
      font-weight: 900;
    }

    .line {
      border-top: 2px dashed #000;
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
        size: 80mm auto;
        margin: 0;
      }

      body {
        margin: 0;
        -webkit-print-color-adjust: exact;
      }

      .receipt-container {
        width: 100%;
        padding: 2mm;
      }
    }
  </style>
</head>

<body>

  <div class="actions">
    <button class="btn" id="btnBack" type="button">BACK</button>
    <button class="btn" id="btnPrint" type="button">PRINT</button>
  </div>

  <div class="receipt-container">
    <!-- Header -->
    <div class="center">
      <img src="{{ asset('assets/images/logo.jpeg') }}" alt="Logo" style="max-height: 80px; margin-bottom: 5px;">
      <div class="store-name">Bin Sultan</div>
      <div class="store-info">Sweets & Bakers</div>
      <div class="store-info">A-16/B Block-D Unit No. 6 Latifabad, Hyderabad</div>
      <div class="store-info">Phone: 0334 2615888</div>
      
      <div class="receipt-title">Return Receipt</div>
    </div>

    <!-- Metadata -->
    <table class="meta-table">
      <tr>
        <th>Return Date</th>
        <td>: {{ optional($return->created_at)->format('D, d-M-Y h:i A') }}</td>
      </tr>
      <tr>
        <th>Original Inv #</th>
        <td>: {{ $return->sale->invoice_no ?? 'N/A' }}</td>
      </tr>
      <tr>
        <th>Customer</th>
        <td>: {{ $return->sale->customer_relation->customer_name ?? 'Walk-in Customer' }}</td>
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
          $categoryGroups = collect($returnItems)->groupBy('category');
          $totalQty = 0;
          $totalItemsCount = count($returnItems);
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

                $cleanItemName = $item['item_name'];
                $customLabel = '';
                
                // Check if color field has additional info (stored as JSON array of strings)
                if (!empty($item['color'])) {
                    $cleanLabel = is_array($item['color']) ? ($item['color'][0] ?? '') : $item['color'];
                    
                    if (!empty($cleanLabel) && strtolower((string)$cleanLabel) !== strtolower((string)$item['item_name'])) {
                        $customLabel = ' (' . $cleanLabel . ')';
                        
                        // Remove common unit suffixes from name to avoid redundancy
                        $cleanItemName = preg_replace('/\s*\(1\s*(kg|kg\.|pound|lb|gm|unit)\)\s*/i', '', $cleanItemName);
                    }
                }
            @endphp
            <tr class="item-row">
              <td style="width: 40%;" class="bold">{{ $cleanItemName }}{{ $customLabel }}</td>
              <td class="col-price" style="width: 20%;">{{ number_format($item['price'], 0) }}</td>
              <td class="col-qty" style="width: 20%;">{{ (float)$displayQty }} {{ $displayUnit }}</td>
              <td class="col-amount" style="width: 20%; font-weight: bold;">{{ number_format($item['total'], 0) }}</td>
            </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>

    <div class="line"></div>

    <table class="totals-table">
      <tr>
        <th>Total Item</th>
        <td style="text-align: left; width: 30%;">: {{ $totalItemsCount }}</td>
        <th>Total Qty</th>
        <td style="text-align: right;">: {{ $totalQty }}</td>
      </tr>
    </table>

    <div class="line"></div>

    <!-- Summary -->
    <table class="totals-table">
      <tr>
        <th>Return Gross</th>
        <td>{{ number_format($return->total_bill_amount ?? 0, 0) }}</td>
      </tr>
      @if(!empty($return->total_extradiscount) && $return->total_extradiscount > 0)
      <tr>
        <th>Return Discount</th>
        <td>{{ number_format($return->total_extradiscount, 0) }}</td>
      </tr>
      @endif
      
      <tr class="grand-total-row">
        <th>Net Refund</th>
        <td>{{ number_format($return->total_net ?? 0, 0) }}</td>
      </tr>
    </table>

    <!-- Footer -->
    <div class="footer center" style="font-weight: normal;">
      <p style="margin: 5px 0;">Returned items confirmation receipt</p>
      <p style="margin: 2px 0; font-size: 11px; font-weight: normal;">Develop By: ProWave Software Solutions</p>
      <p style="margin: 10px 0;">*** Thank you ***</p>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const query = new URLSearchParams(window.location.search);
      const returnTo = query.get('return_to');
      const autoprint = query.get('autoprint') === '1';

      document.getElementById('btnPrint').addEventListener('click', () => window.print());
      document.getElementById('btnBack').addEventListener('click', () => {
        if (returnTo) {
          window.location.href = decodeURIComponent(returnTo);
        } else if (history.length > 1) {
          history.back();
        } else {
          window.location.href = "{{ route('sale.returns.index') }}";
        }
      });
      
      // Auto print logic
      if (autoprint) {
        setTimeout(() => {
          window.print();
          const redirectAfterPrint = () => {
            if (returnTo) {
              window.location.href = decodeURIComponent(returnTo);
            } else {
              window.location.href = "{{ route('sale.returns.index') }}";
            }
          };

          if ('onafterprint' in window) {
            window.onafterprint = redirectAfterPrint;
          }
          setTimeout(redirectAfterPrint, 3000); // Fallback
        }, 500);
      }
    });
  </script>
</body>

</html>