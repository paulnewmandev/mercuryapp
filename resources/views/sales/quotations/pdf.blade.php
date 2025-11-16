<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización - {{ $quotation->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #1e293b;
            line-height: 1.5;
            padding: 0;
            background: #fff;
        }
        .container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
        }
        .invoice-box {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            overflow: hidden;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 24px;
        }
        .header h3 {
            font-size: 20px;
            font-weight: 500;
            color: #1f2937;
            margin: 0;
        }
        .header h4 {
            font-size: 16px;
            font-weight: 500;
            color: #4b5563;
            margin: 0;
        }
        .content {
            padding: 20px 32px;
        }
        .info-section {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 36px;
            gap: 24px;
        }
        .info-left, .info-right {
            flex: 1;
        }
        .info-right {
            text-align: right;
        }
        .info-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 4px;
        }
        .info-name {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .info-text {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .info-date-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 6px;
        }
        .info-date-value {
            display: block;
            font-size: 14px;
            color: #6b7280;
        }
        .divider {
            width: 1px;
            background: #e5e7eb;
            min-height: 158px;
            flex-shrink: 0;
        }
        .table-wrapper {
            border: 1px solid #f3f4f6;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #f9fafb;
        }
        th {
            padding: 12px 20px;
            text-align: left;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        th.text-center {
            text-align: center;
        }
        th.text-right {
            text-align: right;
        }
        th.product-header {
            font-size: 12px;
            color: #6b7280;
        }
        tbody tr {
            border-bottom: 1px solid #f3f4f6;
        }
        tbody tr:last-child {
            border-bottom: none;
        }
        td {
            padding: 12px 20px;
            font-size: 14px;
            color: #6b7280;
        }
        td.number {
            color: #6b7280;
        }
        td.product {
            font-weight: 500;
            color: #1f2937;
        }
        td.text-center {
            text-align: center;
        }
        td.text-right {
            text-align: right;
        }
        .summary-section {
            display: flex;
            justify-content: flex-end;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 24px;
            margin-bottom: 24px;
        }
        .summary-box {
            width: 220px;
        }
        .summary-title {
            font-size: 14px;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 16px;
            text-align: left;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .summary-item .label {
            color: #6b7280;
        }
        .summary-item .value {
            color: #374151;
            font-weight: 500;
        }
        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            padding-top: 8px;
        }
        .summary-total .label {
            font-weight: 500;
            color: #374151;
        }
        .summary-total .value {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        .notes-section {
            margin-bottom: 24px;
        }
        .notes-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .notes-text {
            font-size: 14px;
            color: #1f2937;
        }
        @media print {
            body {
                padding: 0;
            }
            .invoice-box {
                border: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="invoice-box">
            <div class="header">
                <h3>{{ gettext('Cotización') }}</h3>
                <h4>{{ gettext('ID') }} : #{{ $quotation->invoice_number }}</h4>
            </div>
            <div class="content">
                <div class="info-section">
                    <div class="info-left">
                        <span class="info-label">{{ gettext('De') }}</span>
                        <h5 class="info-name">{{ $quotation->company->name ?? $quotation->company->legal_name }}</h5>
                        <p class="info-text">
                            @if($quotation->company->address)
                                {{ $quotation->company->address }}
                            @endif
                            @if($quotation->branch && $quotation->branch->address)
                                <br>{{ $quotation->branch->address }}
                            @endif
                        </p>
                        <span class="info-date-label">{{ gettext('Emitida el') }}:</span>
                        <span class="info-date-value">{{ $quotation->issue_date->translatedFormat('d F, Y') }}</span>
                    </div>
                    <div class="divider"></div>
                    <div class="info-right">
                        <span class="info-label">{{ gettext('Para') }}</span>
                        <h5 class="info-name">{{ $quotation->customer->display_name }}</h5>
                        <p class="info-text">
                            @if($quotation->customer->address)
                                {{ $quotation->customer->address }}
                            @endif
                            @if($quotation->customer->document_number)
                                <br>{{ gettext('Documento') }}: {{ $quotation->customer->document_number }}
                            @endif
                        </p>
                        <span class="info-date-label">{{ gettext('Vence el') }}:</span>
                        <span class="info-date-value">{{ $quotation->due_date ? $quotation->due_date->translatedFormat('d F, Y') : '-' }}</span>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-sm">{{ gettext('N.º') }}</th>
                                <th class="product-header">{{ gettext('Productos') }}</th>
                                <th class="text-center">{{ gettext('Cantidad') }}</th>
                                <th class="text-center">{{ gettext('Precio Unit.') }}</th>
                                <th class="text-center">{{ gettext('Descuento') }}</th>
                                <th class="text-right">{{ gettext('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quotation->items as $index => $item)
                                @php
                                    $name = '';
                                    if ($item->item_type === 'product') {
                                        $name = $products->get($item->item_id)?->name ?? 'Producto';
                                    } elseif ($item->item_type === 'service') {
                                        $name = $services->get($item->item_id)?->name ?? 'Servicio';
                                    }
                                @endphp
                                <tr>
                                    <td class="number">{{ $index + 1 }}</td>
                                    <td class="product">{{ $name }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-center">USD {{ number_format($item->unit_price, 2, '.', ',') }}</td>
                                    <td class="text-center">0%</td>
                                    <td class="text-right">USD {{ number_format($item->subtotal, 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="summary-section">
                    <div class="summary-box">
                        <p class="summary-title">{{ gettext('Resumen de orden') }}</p>
                        <div class="summary-item">
                            <span class="label">{{ gettext('Sub Total') }}</span>
                            <span class="value">USD {{ number_format($quotation->subtotal, 2, '.', ',') }}</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">{{ gettext('IVA (15%)') }}:</span>
                            <span class="value">USD {{ number_format($quotation->tax_amount, 2, '.', ',') }}</span>
                        </div>
                        <div class="summary-total">
                            <span class="label">{{ gettext('Total') }}</span>
                            <span class="value">USD {{ number_format($quotation->total_amount, 2, '.', ',') }}</span>
                        </div>
                    </div>
                </div>

                @if($quotation->notes)
                    <div class="notes-section">
                        <p class="notes-label">{{ gettext('Notas') }}</p>
                        <p class="notes-text">{{ $quotation->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
