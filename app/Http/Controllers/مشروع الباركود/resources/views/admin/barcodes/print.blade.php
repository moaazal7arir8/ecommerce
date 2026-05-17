<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>طباعة الباركود</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            direction: rtl;
            text-align: center;
        }

        .container {
            width: 100%;
        }

        .barcode-box {
            display: inline-block;
            width: 30%;
            border: 1px solid #000;
            margin: 10px;
            padding: 10px;
            vertical-align: top;
        }

        .barcode-box img {
            width: 150px;
            height: 150px;
        }

        .category-name {
            margin-top: 5px;
            font-weight: bold;
            font-size: 16px;
        }

        .info {
            margin-top: 5px;
            font-size: 13px;
        }

        .info p {
            margin: 3px 0;
        }

    </style>
</head>
<body>

    <div class="container">
        @foreach($barcodes as $barcode)
            <div class="barcode-box">
                
                {{-- QR Code --}}
                <img src="data:image/png;base64,{{ $barcode->qr_image }}" alt="QR Code">

                {{-- معلومات إضافية --}}
                <div class="info">
                    <p><strong>number of points:</strong> {{ $barcode->points }}</p>
                    <p><strong>التصنيف:</strong> {{ $barcode->category->name }}</p>
                </div>

            </div>
        @endforeach
    </div>

</body>
</html>
