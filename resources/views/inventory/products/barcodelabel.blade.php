<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $product->name }} — {{ $barcodeValue }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        html {
            width: 100%;
            height: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            width: 100%;
            height: 100%;
            position: relative;
        }

        .label {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 150px;
            text-align: center;
        }

        .product-name {
            font-size: 12px;
            font-weight: 600;
            line-height: 1.15;
            margin-bottom: 6px;
        }

        .barcode img {
            width: 140px;
            height: 40px;
        }

        .sku {
            font-size: 11px;
            letter-spacing: 3px;
            font-weight: 600;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="label">
        <h1 class="product-name">{{ \Illuminate\Support\Str::limit($product->name, 46) }}</h1>
        <div class="barcode">
            <img src="data:image/png;base64,{{ $barcodeImage }}" alt="Código de barras {{ $barcodeValue }}">
        </div>
        <div class="sku">{{ $barcodeValue }}</div>
    </div>
</body>
</html>

