<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $order->order_number }} — {{ $barcodeValue }}</title>
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

        .customer-name {
            font-size: 12px;
            font-weight: 600;
            line-height: 1.15;
            margin-bottom: 6px;
            word-wrap: break-word;
        }

        .barcode img {
            width: 140px;
            height: 40px;
        }

        .barcode-value {
            font-size: 11px;
            letter-spacing: 3px;
            font-weight: 600;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="customer-name">{{ \Illuminate\Support\Str::limit($order->customer->display_name ?? 'N/A', 46) }}</div>
        <div class="barcode">
            <img src="data:image/png;base64,{{ $barcodeImage }}" alt="Código de barras {{ $barcodeValue }}">
        </div>
        <div class="barcode-value">{{ $barcodeValue }}</div>
    </div>
</body>
</html>

