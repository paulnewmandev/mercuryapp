<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket - {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            margin: 0;
            padding: 0;
            width: 3.15in;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            width: 3.15in;
            margin: 0;
            padding: 2mm;
            overflow: visible;
        }

        .ticket {
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .company-info {
            font-size: 9px;
            margin: 2px 0;
        }

        .order-title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin: 8px 0;
        }

        .order-number {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .section {
            margin: 8px 0;
            border-top: 1px dashed #000;
            padding-top: 6px;
        }

        .section-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .section-content {
            font-size: 9px;
            line-height: 1.5;
        }

        .section-content p {
            margin: 2px 0;
        }

        .work-description {
            font-size: 8px;
            line-height: 1.4;
            margin-top: 4px;
            word-wrap: break-word;
        }

        .totals {
            margin-top: 8px;
            border-top: 1px dashed #000;
            padding-top: 6px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 9px;
        }

        .total-row.total {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 4px;
        }

        .advances-table {
            width: 100%;
            font-size: 8px;
            margin-top: 4px;
        }

        .advances-table th,
        .advances-table td {
            padding: 2px;
            text-align: left;
        }

        .advances-table th {
            font-weight: bold;
            border-bottom: 1px solid #000;
        }

        .footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px dashed #000;
            font-size: 8px;
        }

        .print-date {
            font-size: 8px;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <div class="company-name">{{ $company->name ?? 'Especialista Mac' }}</div>
            @if($order->branch)
                <div class="company-info">{{ $order->branch->address ?? '' }}</div>
            @endif
            @if($company)
                <div class="company-info">{{ $company->phone_number ?? '' }}</div>
                <div class="company-info">{{ $company->website ?? '' }}</div>
                <div class="company-info">{{ $company->email ?? '' }}</div>
            @endif
        </div>

        <div class="order-title">ORDEN DE TRABAJO</div>
        <div class="order-number">N° {{ $order->order_number }}</div>

        <div class="section">
            <div class="section-title">ORDEN DE TRABAJO #{{ $order->order_number }}</div>
            <div class="section-content">
                <p><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                @if($order->advance_amount)
                    <p><strong>Adelanto:</strong> {{ number_format($order->advance_amount, 2, '.', ',') }}</p>
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">CLIENTE</div>
            <div class="section-content">
                <p><strong>{{ $order->customer->display_name ?? 'N/A' }}</strong></p>
                @if($order->customer?->document_number)
                    <p>({{ $order->customer->document_number }})</p>
                @endif
                @if($order->customer?->phone_number)
                    <p>Tel: {{ $order->customer->phone_number }}</p>
                @endif
                @if($order->customer?->email)
                    <p>Correo: {{ $order->customer->email }}</p>
                @endif
                @if($order->customer?->address)
                    <p>Dirección: {{ $order->customer->address }}</p>
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">EQUIPO</div>
            <div class="section-content">
                @if($order->equipment)
                    <p><strong>Marca:</strong> {{ $order->equipment->brand?->name ?? '' }} {{ $order->equipment->model?->name ?? '' }}</p>
                    <p><strong>Tipo:</strong> {{ $order->equipment->brand?->name ?? 'N/A' }}</p>
                    @if($order->equipment->identifier)
                        <p><strong>N° Serie:</strong> {{ $order->equipment->identifier }}</p>
                    @endif
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">INFORMACIÓN DE LA ORDEN</div>
            <div class="section-content">
                @if($order->note)
                    <p><strong>Trabajo:</strong></p>
                    <div class="work-description">
                        {{ $order->note }}
                    </div>
                @endif
                <p><strong>Prioridad:</strong> {{ $order->priority ?? 'Normal' }}</p>
            </div>
        </div>

        <div class="totals">
            <div class="section-title">RESUMEN DE COSTOS</div>
            <div class="total-row">
                <span>Subtotal productos:</span>
                <span>{{ number_format($order->items->where('status', 'A')->sum('subtotal'), 2, '.', ',') }}</span>
            </div>
            <div class="total-row">
                <span>Subtotal servicios:</span>
                <span>{{ number_format($order->services->where('status', 'A')->sum('subtotal'), 2, '.', ',') }}</span>
            </div>
            <div class="total-row total">
                <span>Costo total:</span>
                <span>{{ number_format($order->total_cost ?? ($order->items->where('status', 'A')->sum('subtotal') + $order->services->where('status', 'A')->sum('subtotal')), 2, '.', ',') }}</span>
            </div>
            <div class="total-row">
                <span>Total pagado (abonos):</span>
                <span>{{ number_format($order->total_paid ?? $order->advances->where('status', 'A')->sum('amount'), 2, '.', ',') }}</span>
            </div>
            <div class="total-row total">
                <span>Balance pendiente:</span>
                <span>{{ number_format($order->balance ?? (($order->items->where('status', 'A')->sum('subtotal') + $order->services->where('status', 'A')->sum('subtotal')) - $order->advances->where('status', 'A')->sum('amount')), 2, '.', ',') }}</span>
            </div>
        </div>

        @if($order->advances->where('status', 'A')->isNotEmpty())
            <div class="section">
                <div class="section-title">ABONOS</div>
                <table class="advances-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Método</th>
                            <th>Monto</th>
                            <th>Ref.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->advances->where('status', 'A') as $advance)
                            <tr>
                                <td>{{ $advance->payment_date->format('d M Y') }}</td>
                                <td>{{ $advance->paymentMethod?->name ?? 'N/A' }}</td>
                                <td>{{ number_format($advance->amount, 2, '.', ',') }}</td>
                                <td>{{ $advance->reference ?? 'N/A' }}</td>
                            </tr>
                            @if($advance->notes)
                                <tr>
                                    <td colspan="4" style="font-size: 7px; padding-left: 10px;">{{ $advance->notes }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="footer">
            @if($order->promised_at)
                <p><strong>Fecha prometida:</strong> {{ $order->promised_at->format('d/m/Y') }}</p>
            @endif
            <div class="print-date">
                Fecha Impresión: {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</body>
</html>

