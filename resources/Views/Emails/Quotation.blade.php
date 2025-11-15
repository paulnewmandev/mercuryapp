<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización - MercuryApp</title>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;margin:0; padding:0; font-family: Arial, sans-serif;">
        <tr>
            <td align="center" style="padding:0 8px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(59,130,246,0.08); margin:30px 0; max-width:600px; width:100%;">
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #001A35 0%, #14385E 100%); padding: 32px 20px 20px 20px; border-radius:10px 10px 0 0;">
                            <span style="display:inline-block; font-size:24px; font-weight:bold; color:#ffffff;">MercuryApp</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 16px 24px 16px;">
                            <div style="color:#222; font-size:16px; line-height:1.7; margin-bottom:24px;">
                                Hola {{ $customer_name }},<br><br>
                                Adjuntamos la cotización solicitada:
                            </div>

                            <div style="background:#f8fafc; border-radius:8px; padding:20px; margin-bottom:24px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding-bottom:12px;">
                                            <strong style="color:#1e293b; font-size:14px;">{{ gettext('Número de Cotización') }}:</strong>
                                            <span style="color:#64748b; font-size:14px; margin-left:8px;">{{ $quotation_number }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom:12px;">
                                            <strong style="color:#1e293b; font-size:14px;">{{ gettext('Fecha') }}:</strong>
                                            <span style="color:#64748b; font-size:14px; margin-left:8px;">{{ $issue_date }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom:12px;">
                                            <strong style="color:#1e293b; font-size:14px;">{{ gettext('Vence el') }}:</strong>
                                            <span style="color:#64748b; font-size:14px; margin-left:8px;">{{ $due_date }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div style="margin-bottom:24px;">
                                <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; border:1px solid #e2e8f0;">
                                    <thead>
                                        <tr style="background:#f1f5f9;">
                                            <th style="text-align:left; padding:12px; border-bottom:1px solid #e2e8f0; color:#1e293b; font-size:13px; font-weight:600;">{{ gettext('Producto/Servicio') }}</th>
                                            <th style="text-align:center; padding:12px; border-bottom:1px solid #e2e8f0; color:#1e293b; font-size:13px; font-weight:600;">{{ gettext('Cantidad') }}</th>
                                            <th style="text-align:right; padding:12px; border-bottom:1px solid #e2e8f0; color:#1e293b; font-size:13px; font-weight:600;">{{ gettext('Precio Unit.') }}</th>
                                            <th style="text-align:right; padding:12px; border-bottom:1px solid #e2e8f0; color:#1e293b; font-size:13px; font-weight:600;">{{ gettext('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $item)
                                            <tr>
                                                <td style="padding:12px; border-bottom:1px solid #e2e8f0; color:#334155; font-size:14px;">{{ $item['name'] }}</td>
                                                <td style="text-align:center; padding:12px; border-bottom:1px solid #e2e8f0; color:#64748b; font-size:14px;">{{ $item['quantity'] }}</td>
                                                <td style="text-align:right; padding:12px; border-bottom:1px solid #e2e8f0; color:#64748b; font-size:14px;">USD {{ number_format($item['unit_price'], 2, '.', ',') }}</td>
                                                <td style="text-align:right; padding:12px; border-bottom:1px solid #e2e8f0; color:#1e293b; font-size:14px; font-weight:600;">USD {{ number_format($item['subtotal'], 2, '.', ',') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div style="background:#f8fafc; border-radius:8px; padding:20px; margin-bottom:24px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding-bottom:8px; text-align:right;">
                                            <span style="color:#64748b; font-size:14px;">{{ gettext('Sub Total') }}:</span>
                                            <span style="color:#1e293b; font-size:14px; font-weight:600; margin-left:16px;">USD {{ number_format($subtotal, 2, '.', ',') }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom:8px; text-align:right;">
                                            <span style="color:#64748b; font-size:14px;">{{ gettext('IVA (15%)') }}:</span>
                                            <span style="color:#1e293b; font-size:14px; font-weight:600; margin-left:16px;">USD {{ number_format($tax_amount, 2, '.', ',') }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top:8px; border-top:2px solid #e2e8f0; text-align:right;">
                                            <span style="color:#1e293b; font-size:16px; font-weight:700;">{{ gettext('Total') }}:</span>
                                            <span style="color:#1e293b; font-size:18px; font-weight:700; margin-left:16px;">USD {{ number_format($total_amount, 2, '.', ',') }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            @if($notes)
                                <div style="background:#fef3c7; border-left:4px solid #f59e0b; padding:16px; margin-bottom:24px; border-radius:4px;">
                                    <strong style="color:#92400e; font-size:14px;">{{ gettext('Notas') }}:</strong>
                                    <p style="color:#78350f; font-size:14px; margin-top:8px; margin-bottom:0;">{{ $notes }}</p>
                                </div>
                            @endif

                            <div style="color:#64748b; font-size:15px; line-height:1.6; text-align:center;">
                                Esta cotización es válida hasta {{ $due_date }}. Si tiene alguna pregunta, no dude en contactarnos.
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc; text-align:center; padding:24px 16px 20px 16px; border-radius:0 0 10px 10px; color:#94a3b8; font-size:12px;">
                            © 2025 MercuryApp. Todos los derechos reservados.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

