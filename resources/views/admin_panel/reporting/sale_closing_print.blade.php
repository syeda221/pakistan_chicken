<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sale Closing Receipt - {{ $startDate }}</title>
    <style>
        @media print {
            @page { margin: 0; }
            body { margin: 0; padding: 5px; }
            .no-print { display: none; }
        }
        body {
            font-family: 'Arial', sans-serif;
            width: 80mm;
            margin: 0 auto;
            color: #000;
            font-weight: 700;
            font-size: 14px;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; font-size: 18px; }
        .header p { margin: 2px 0; font-size: 12px; }
        
        .divider { border-top: 2px dashed #000; margin: 10px 0; }
        .row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .bold { font-weight: 900; }
        .title { text-align: center; border: 2px solid #000; padding: 5px; margin-bottom: 10px; }
        
        .footer { text-align: center; margin-top: 20px; font-size: 11px; }
        .text-end { text-align: right; }
        .net-box { border: 3px solid #000; padding: 10px; text-align: center; font-size: 18px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; padding: 10px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #000; color: #fff; border: none;">Print Receipt</button>
    </div>

    <div class="header">
        <h2>Bin Sultan Sweets</h2>
        <p>Sale Closing Report</p>
        <p>Period: {{ $startDate }} to {{ $endDate }}</p>
        <p>User: {{ $userName }}</p>
    </div>

    <div class="title">DAILY CLOSING</div>

    <div class="row">
        <span>Total Sales Count:</span>
        <span>{{ $salesCount }}</span>
    </div>
    <div class="row">
        <span>Total Expenses Count:</span>
        <span>{{ $expensesCount }}</span>
    </div>

    <div class="divider"></div>

    <div class="row" style="font-size: 16px;">
        <span>TOTAL SALES (+)</span>
        <span>Rs {{ number_format($totalSale, 0) }}</span>
    </div>
    <div class="row" style="font-size: 16px; color: #000;">
        <span>TOTAL EXPENSES (-)</span>
        <span>Rs {{ number_format($totalExpense, 0) }}</span>
    </div>

    <div class="divider"></div>

    <div class="net-box">
        <div style="font-size: 12px;">NET CASH IN HAND</div>
        <div class="bold">Rs {{ number_format($netAmount, 0) }}</div>
    </div>

    <div class="divider"></div>
    <p style="text-align: center; font-size: 10px; margin: 0;">Generated on: {{ date('d-M-Y H:i A') }}</p>

    <div class="footer">
        <p>*** Bin Sultan Sweets ***</p>
        <p style="font-size: 11px; font-weight: normal; margin: 2px 0;">Develop By: ProWave Software Solutions</p>
    </div>

    <script>
        window.onload = function() {
            // Uncomment if you want auto-print
            // window.print();
        };
    </script>
</body>
</html>
