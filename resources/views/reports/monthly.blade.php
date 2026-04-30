<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Report</title>

    <style>
        body {
            font-family: DejaVu Sans;
            padding: 20px;
        }

        h2 {
            color: #333;
            text-align: center;
        }

        p {
            font-size: 14px;
            margin: 8px 0;
        }
    </style>
</head>
<body>

    <h2>📊 Monthly Report</h2>

    <p><strong>Total Sales:</strong> {{ $report['total_sales'] }}</p>
    <p><strong>Total Profits:</strong> {{ $report['total_profits'] }}</p>
    <p><strong>Total Orders:</strong> {{ $report['total_orders'] }}</p>

</body>
</html>