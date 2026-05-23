<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt</title>
<style>
    body {
        font-family: 'Courier New', monospace;
        font-size: 12px;
        color: #000;
        background: #fff;
        margin: 0;
        padding: 0;
    }
    .receipt-container {
        width: 100%;
        max-width: 340px;
        margin: auto;
        padding: 10px;
    }
    .center {
        text-align: center;
    }
    .bold {
        font-weight: bold;
    }
    .line {
        border-top: 1px dashed #000;
        margin: 4px 0;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        padding: 2px 0;
    }
    th {
        text-align: left;
        font-size: 11px;
    }
    td {
        font-size: 11px;
    }
    td:last-child, th:last-child {
        text-align: right;
    }
    .footer {
        text-align: center;
        font-size: 11px;
        margin-top: 6px;
        border-top: 1px dashed #000;
        padding-top: 4px;
    }
</style>
</head>
<body>

<div class="receipt-container">

    <!-- Header -->
    <div class="center">
        <h2 style="margin:0;font-size:14px;" class="bold">Bin Sultan</h2>
        <p style="margin:0;">Sweets & Bakers</p>
        <p style="margin:0;">A-16/B Block-D Unit No. 6 Latifabad, Hyderabad</p>
        <p style="margin:0;">Phone: 0334 2615888</p>
    </div>

    <div class="line"></div>
    <div class="center bold">Cash Memo</div>
    <div class="line"></div>

    <!-- Details -->
    <table>
        <tr><th>Customer:</th><td>Counter Sale</td></tr>
        <tr><th>Reference:</th><td>#{{ $sale->id }}</td></tr>
        <tr><th>Order Type:</th><td>{{ $sale->order_type ?? 'Walk-in' }}</td></tr>
        @if(isset($sale->order_type) && $sale->order_type == 'Dine-in' && $sale->table_id)
        <tr><th>Table No:</th><td>{{ $sale->table->table_name ?? 'N/A' }}</td></tr>
        @endif
        <tr><th>Print Time:</th><td>{{ \Carbon\Carbon::parse($sale->created_at)->format('d-m-Y H:i:s') }}</td></tr>
    </table>

    <div class="line"></div>

    <!-- Items -->
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($saleItems as $item)
            <tr>
                <td>
                    {{ $item['item_name'] }}
                    @if(isset($item['discount']) && $item['discount'] > 0)
                        <br><small style="font-weight:normal;">Disc: {{ number_format($item['discount'], 0) }}</small>
                    @endif
                </td>
                <td>{{ $item['qty'] }} {{ $item['unit'] }}</td>
                <td>{{ number_format($item['price'], 0) }}</td>
                <td>{{ number_format($item['total'], 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <!-- Totals -->
    <table>
        <tr><th>Total Pieces</th><td>{{ $sale->total_items }}</td></tr>
        <tr><th>Sale Type</th><td>CASH</td></tr>
        <tr><th>Gross Amount</th><td>{{ number_format($sale->total_bill_amount ?? 0, 0) }}</td></tr>
        @if(!empty($sale->total_extradiscount) && $sale->total_extradiscount > 0)
        @php 
            $discPercent = ($sale->total_bill_amount > 0) ? ($sale->total_extradiscount / $sale->total_bill_amount * 100) : 0;
        @endphp
        <tr><th>Discount ({{ number_format($discPercent, 2) }}%)</th><td>{{ number_format($sale->total_extradiscount, 0) }}</td></tr>
        @endif
        <tr><th>Net Amount</th><td>{{ number_format($sale->total_net, 0) }}</td></tr>
        <tr><th>Cash</th><td>{{ number_format($sale->cash, 0) }}</td></tr>
        <tr><th>Change</th><td>{{ number_format($sale->change, 0) }}</td></tr>
    </table>



    <!-- Footer -->
    <div class="footer">
        <p>Please check bakery items at the time of purchase</p>
        <p>Bakery & sweets items are non-returnable</p>
        <p>*** Thank you for the visit
 ***</p>
    </div>
</div>

<script>
    window.onload = function () {
        window.print();
        setTimeout(function () {
            window.close(); // Close the tab after printing dialog is closed
        }, 800);
    };
</script>
</body>
</html>
