<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BOOKING RECEIPT - {{ $booking->id }}</title>
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
      position: relative;
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

    .copy-label {
      font-size: 16px;
      font-weight: 900;
      text-decoration: underline;
      margin-bottom: 10px;
      display: block;
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

    .page-break {
      page-break-after: always;
    }

    /* Ribbon */
    .ribbon-wrap {
      position: absolute;
      top: 0;
      right: 0;
      width: 70px;
      height: 70px;
      overflow: hidden;
      z-index: 99;
    }

    .ribbon {
      position: absolute;
      top: 20px;
      right: -32px;
      width: 128px;
      text-align: center;
      background: #000;
      color: #fff;
      font-size: 12px;
      font-weight: 900;
      letter-spacing: 1px;
      padding: 5px 0;
      transform: rotate(45deg);
    }
  </style>
</head>

<body>

  <div class="actions">
    <button class="btn" id="btnBack" type="button">BACK</button>
    <button class="btn" id="btnPrint" type="button">PRINT</button>
  </div>

  @php
    $copies = ['Customer Copy', 'Bakery Copy'];
  @endphp

  @foreach($copies as $copy)
  <div class="receipt-container {{ !$loop->last ? 'page-break' : '' }}">
    <div class="ribbon-wrap">
        <div class="ribbon">BOOKED</div>
    </div>
    <!-- Header -->
    <div class="center">
      <div class="store-name">Bin Sultan</div>
      <div class="store-info">Sweet & Bakers</div>
      <div class="store-info">A-16/B Block-D Unit No. 6 Latifabad, Hyderabad</div>
      <div class="store-info">Phone: 0334 2615888</div>
      
      <div class="receipt-title">Booking Receipt</div>
      <span class="copy-label">{{ $copy }}</span>
    </div>

    <!-- Metadata -->
    <table class="meta-table">
      <tr>
        <th>Booking #</th>
        <td>: {{ $booking->id }}</td>
      </tr>
      <tr>
        <th>Customer</th>
        <td>: {{ $booking->customer_relation->customer_name ?? 'N/A' }}</td>
      </tr>
      <tr>
        <th>Reference</th>
        <td>: {{ $booking->reference ?? 'N/A' }}</td>
      </tr>
      <tr>
        <th>Date</th>
        <td>: {{ \Carbon\Carbon::parse($booking->booking_date)->format('D, d-M-Y h:i A') }}</td>
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
          $totalQty = 0;
          $totalItemsCount = count($bookingItems);
        @endphp

        @foreach($bookingItems as $item)
            @php 
                $totalQty += (float)$item['qty']; 
                $isKg = strtolower($item['unit']) === 'kg';
                $displayQty = (float)$item['qty'];
                $displayUnit = $item['unit'];
                
                if ($isKg && $displayQty < 1) {
                    $displayQty = round($displayQty * 1000);
                    $displayUnit = 'GRAM';
                } else {
                    $displayQty = (float)number_format($displayQty, 3);
                }
            @endphp
            <tr class="item-row">
              <td style="width: 40%;" class="bold">{{ $item['item_name'] }}</td>
              <td class="col-price" style="width: 20%;">{{ number_format($item['price'], 0) }}</td>
              <td class="col-qty" style="width: 20%;">{{ $displayQty }} {{ $displayUnit }}</td>
              <td class="col-amount" style="width: 20%; font-weight: bold;">{{ number_format($item['total'], 0) }}</td>
            </tr>
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
        <th>Gross Amount</th>
        <td>{{ number_format($booking->total_bill_amount ?? 0, 0) }}</td>
      </tr>
      @if(!empty($booking->total_extradiscount) && $booking->total_extradiscount > 0)
      <tr>
        <th>Discount</th>
        <td>{{ number_format($booking->total_extradiscount, 0) }}</td>
      </tr>
      @endif

      <tr class="grand-total-row">
        <th>Net Amount</th>
        <td>{{ number_format($booking->total_net ?? 0, 0) }}</td>
      </tr>
      @php
        $totalPaid = floatval($booking->advance_payment ?? 0) + floatval($booking->cash ?? 0) + floatval($booking->card ?? 0);
        $remaining = max(($booking->total_net ?? 0) - $totalPaid, 0);
      @endphp
      @if(floatval($booking->advance_payment) > 0)
      <tr>
        <th>Advance Paid</th>
        <td>{{ number_format($booking->advance_payment, 0) }}</td>
      </tr>
      @endif

      @if(floatval($booking->cash ?? 0) > 0 || floatval($booking->card ?? 0) > 0)
      <tr>
        <th>Final Payment</th>
        <td>{{ number_format(floatval($booking->cash ?? 0) + floatval($booking->card ?? 0), 0) }}</td>
      </tr>
      @endif

      <tr style="font-size: 16px;">
        <th>Remaining Amount</th>
        <td>{{ number_format($remaining, 0) }}</td>
      </tr>
    </table>

    <div class="line"></div>

    <!-- Footer -->
    <div class="footer center" style="font-weight: normal;">
      <p style="margin: 5px 0;">Please check bakery items at the time of purchase</p>
      <p style="margin: 5px 0;">Bakery & sweets items are non-returnable</p>
      <p style="margin: 2px 0; font-size: 11px; font-weight: normal;">Develop By: ProWave Software Solutions</p>
      <p style="margin: 2px 0; font-size: 11px; font-weight: normal;">+92 317 3836 223 | +92 317 3859 647</p>
      <p style="margin: 10px 0;">*** Thank you for the visit ***</p>
    </div>
  </div>
  @endforeach

  <script>
    document.addEventListener("DOMContentLoaded", function() {


      // ===== ACTIONS =====
      const query = new URLSearchParams(window.location.search);
      const returnTo = query.get('return_to') || "{{ route('sale.add') }}";
      const autoprint = query.get('autoprint') === '1';

      document.getElementById('btnPrint').addEventListener('click', () => window.print());

      document.getElementById('btnBack').addEventListener('click', () => {
        window.location.href = decodeURIComponent(returnTo);
      });

      if (autoprint) {
        setTimeout(() => {
          window.print();
          const goBack = () => {
            window.location.href = decodeURIComponent(returnTo);
          };
          if ('onafterprint' in window) {
            window.onafterprint = goBack;
          }
          setTimeout(goBack, 2000);
        }, 500);
      }
    });
  </script>
</body>

</html>