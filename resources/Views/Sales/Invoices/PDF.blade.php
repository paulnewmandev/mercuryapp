<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #000;
            padding: 8mm;
            line-height: 1.2;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
        }
        .header-left {
            flex: 0 0 55%;
        }
        .header-right {
            flex: 0 0 45%;
            text-align: right;
        }
        .factura-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .header-field {
            font-size: 7px;
            margin-bottom: 2px;
            line-height: 1.1;
        }
        .header-label {
            font-weight: bold;
        }
        .barcode-container {
            margin-top: 4px;
            text-align: center;
        }
        .barcode-label {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 2px;
        }
        .barcode-value {
            font-size: 6px;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .barcode img {
            max-width: 100%;
            height: auto;
            max-height: 35px;
        }
        .company-section {
            margin-bottom: 6px;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
        }
        .company-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .company-field {
            font-size: 7px;
            margin-bottom: 1px;
        }
        .company-label {
            font-weight: bold;
        }
        .customer-section {
            margin-bottom: 6px;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
        }
        .customer-field {
            font-size: 7px;
            margin-bottom: 1px;
        }
        .customer-label {
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 7px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 2px;
            text-align: left;
        }
        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .items-table td.text-right {
            text-align: right;
        }
        .items-table td.text-center {
            text-align: center;
        }
        .items-table .cod-principal {
            width: 12%;
        }
        .items-table .cant {
            width: 8%;
        }
        .items-table .descripcion {
            width: 40%;
        }
        .items-table .precio-unit {
            width: 12%;
        }
        .items-table .desct {
            width: 10%;
        }
        .items-table .precio-total {
            width: 18%;
        }
        .summary-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .summary-left {
            flex: 0 0 48%;
        }
        .summary-right {
            flex: 0 0 48%;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .summary-table td {
            padding: 2px 5px;
            border-bottom: 1px solid #ddd;
        }
        .summary-table td.label {
            font-weight: bold;
            text-align: left;
        }
        .summary-table td.value {
            text-align: right;
        }
        .summary-table .total-row {
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        .payment-methods {
            margin-bottom: 6px;
            border: 1px solid #000;
            padding: 4px;
        }
        .payment-methods-title {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 2px;
        }
        .payment-method-row {
            display: flex;
            justify-content: space-between;
            font-size: 7px;
            margin-bottom: 1px;
        }
        .payment-method-label {
            font-weight: bold;
        }
        .additional-info {
            margin-bottom: 6px;
            border: 1px solid #000;
            padding: 4px;
        }
        .additional-info-title {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 2px;
        }
        .additional-info-field {
            font-size: 7px;
            margin-bottom: 1px;
        }
        .footer {
            margin-top: 8px;
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    {{-- Header Superior --}}
    <div class="header">
        <div class="header-left">
            {{-- Logo o espacio para logo --}}
            @if($company->logo_url && file_exists(public_path($company->logo_url)))
                <img src="{{ public_path($company->logo_url) }}" alt="Logo" style="max-height: 35px; margin-bottom: 4px;">
            @endif
        </div>
        <div class="header-right">
            <div class="factura-title">FACTURA</div>
            <div class="header-field">
                <span class="header-label">No.:</span> {{ $invoice->invoice_number }}
            </div>
            @if($invoice->authorization_number)
                <div class="header-field">
                    <span class="header-label">NÚMERO DE AUTORIZACIÓN:</span><br>
                    {{ $invoice->authorization_number }}
                </div>
                <div class="header-field">
                    <span class="header-label">FECHA Y HORA DE AUTORIZACIÓN:</span><br>
                    {{ $invoice->authorized_at ? $invoice->authorized_at->format('Y-m-d\TH:i:s') : '' }}
                </div>
            @endif
            <div class="header-field">
                <span class="header-label">AMBIENTE:</span> {{ ($invoice->sri_environment ?? $company->sri_environment ?? 'development') === 'production' ? 'Produccion' : 'Pruebas' }}
            </div>
            <div class="header-field">
                <span class="header-label">EMISIÓN:</span> Normal
            </div>
            {{-- Código de Barras en el Header --}}
            @if($invoice->access_key && $barcodeImage)
                <div class="barcode-container">
                    <div class="barcode-label">CLAVE DE ACCESO</div>
                    <div class="barcode-value">{{ $invoice->access_key }}</div>
                    <img src="data:image/png;base64,{{ $barcodeImage }}" alt="Código de Barras">
                </div>
            @endif
        </div>
    </div>

    {{-- Información del Emisor --}}
    <div class="company-section">
        <div class="company-name">{{ $company->legal_name ?? $company->name }}</div>
        <div class="company-field">
            <span class="company-label">{{ $company->name }}</span>
        </div>
        <div class="company-field">
            <span class="company-label">RUC:</span> {{ $company->number_tax ?? 'N/A' }}
        </div>
        <div class="company-field">
            <span class="company-label">Dirección Matriz:</span> {{ $company->address ?? '' }}
        </div>
        <div class="company-field">
            <span class="company-label">Contribuyente Especial #:</span>
        </div>
        <div class="company-field">
            <span class="company-label">Obligado a Llevar Contabilidad:</span> NO
        </div>
    </div>

    {{-- Información del Comprador --}}
    <div class="customer-section">
        <div class="customer-field">
            <span class="customer-label">Razón Social / Nombres y Apellidos:</span> {{ $customer->display_name ?? 'CONSUMIDOR FINAL' }}
        </div>
        <div class="customer-field">
            <span class="customer-label">Identificación:</span> {{ $customer->document_number ?? '9999999999' }}
        </div>
        <div class="customer-field">
            <span class="customer-label">Dirección:</span> {{ $customer->address ?? '' }}
        </div>
        <div class="customer-field">
            <span class="customer-label">Fecha Emisión:</span> {{ $invoice->issue_date->format('Y') }}{{ strtoupper($invoice->issue_date->format('M')) }}{{ $invoice->issue_date->format('d') }}
        </div>
    </div>

    {{-- Tabla de Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="cod-principal">Cod. Principal</th>
                <th class="cant">Cant</th>
                <th class="descripcion">Descripción</th>
                <th class="precio-unit">Precio Unitario</th>
                <th class="desct">Desct.</th>
                <th class="precio-total">Precio Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalDescuento = 0;
                $valorTotal = 0;
            @endphp
            @foreach($items as $item)
                @php
                    $name = '';
                    $codPrincipal = '';
                    if ($item->item_type === 'product') {
                        $product = $products->get($item->item_id);
                        $name = $product?->name ?? 'Producto';
                        $codPrincipal = $product?->sku ?? substr($item->item_id, 0, 15);
                    } elseif ($item->item_type === 'service') {
                        $name = $services->get($item->item_id)?->name ?? 'Servicio';
                        $codPrincipal = substr($item->item_id, 0, 15);
                    }
                    $descuento = 0; // Por ahora sin descuentos
                    $valorItem = $item->quantity * $item->unit_price;
                    $precioConDescuento = $valorItem - $descuento;
                    $totalDescuento += $descuento;
                    $valorTotal += $valorItem;
                @endphp
                <tr>
                    <td class="cod-principal">{{ $codPrincipal }}</td>
                    <td class="cant text-center">{{ number_format($item->quantity, 2, '.', ',') }}</td>
                    <td class="descripcion">{{ $name }}</td>
                    <td class="precio-unit text-right">{{ number_format($item->unit_price, 3, '.', ',') }}</td>
                    <td class="desct text-right">{{ number_format($descuento, 2, '.', ',') }}</td>
                    <td class="precio-total text-right">{{ number_format($precioConDescuento, 2, '.', ',') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Resumen y Forma de Pago --}}
    <div class="summary-section">
        {{-- Forma de Pago --}}
        <div class="summary-left">
            <div class="payment-methods">
                <div class="payment-methods-title">Forma de Pago</div>
                @php
                    $payments = $invoice->payments ?? collect();
                    $efectivo = $payments->where('payment_method_id', 'EFECTIVO')->sum('amount');
                    $transferencia = $payments->where('payment_method_id', 'TRANSFERENCIA')->sum('amount');
                    $tarjetaCredito = $payments->where('payment_method_id', 'TARJETA')->sum('amount');
                    $otros = $invoice->total_amount - $efectivo - $transferencia - $tarjetaCredito;
                @endphp
                <div class="payment-method-row">
                    <span class="payment-method-label">Efectivo:</span>
                    <span>{{ $efectivo > 0 ? number_format($efectivo, 2, '.', ',') : '' }}</span>
                </div>
                <div class="payment-method-row">
                    <span class="payment-method-label">Dinero Electrónico:</span>
                    <span></span>
                </div>
                <div class="payment-method-row">
                    <span class="payment-method-label">Tarjeta de Credito:</span>
                    <span>{{ $tarjetaCredito > 0 ? number_format($tarjetaCredito, 2, '.', ',') : '' }}</span>
                </div>
                <div class="payment-method-row">
                    <span class="payment-method-label">Tarjeta de Debito:</span>
                    <span></span>
                </div>
                <div class="payment-method-row">
                    <span class="payment-method-label">Otros con Utilización del Sistema Financiero:</span>
                    <span>{{ $otros > 0 ? number_format($otros, 2, '.', ',') : ($invoice->total_amount > 0 ? number_format($invoice->total_amount, 2, '.', ',') : '') }}</span>
                </div>
            </div>
            
            {{-- Información Adicional --}}
            <div class="additional-info" style="margin-top: 6px;">
                <div class="additional-info-title">Información Adicional</div>
                <div class="additional-info-field">
                    <span class="customer-label">Email:</span> {{ $customer->email ?? $company->email ?? '' }}
                </div>
                <div class="additional-info-field">
                    <span class="customer-label">Teléfono:</span> {{ $customer->phone_number ?? $company->phone_number ?? '' }}
                </div>
            </div>
        </div>
        
        {{-- Resumen de Totales --}}
        <div class="summary-right">
            <table class="summary-table">
                <tr>
                    <td class="label">Valor:</td>
                    <td class="value">{{ number_format($valorTotal ?? $invoice->subtotal, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td class="label">Descuento:</td>
                    <td class="value">{{ number_format($totalDescuento, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="value">{{ number_format($invoice->subtotal, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td class="label">Subtotal 15 %:</td>
                    <td class="value">{{ number_format($invoice->subtotal, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td class="label">Subtotal 0 %:</td>
                    <td class="value">0.00</td>
                </tr>
                <tr>
                    <td class="label">Iva:</td>
                    <td class="value">{{ number_format($invoice->tax_amount, 2, '.', ',') }}</td>
                </tr>
                <tr class="total-row">
                    <td class="label">Total:</td>
                    <td class="value">{{ number_format($invoice->total_amount, 2, '.', ',') }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>Este documento ha sido generado electrónicamente por MercuryApp</p>
        <p>Es válido sin firma ni sello según Art. 10 del Acuerdo Ministerial 0126 del 20 de mayo de 2011</p>
    </div>
</body>
</html>
