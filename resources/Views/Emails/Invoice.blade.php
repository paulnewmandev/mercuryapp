<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Electrónica - {{ $invoice->invoice_number }}</title>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;margin:0; padding:0; font-family: Arial, sans-serif;">
        <tr>
            <td align="center" style="padding:0 8px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(59,130,246,0.08); margin:30px 0; max-width:600px; width:100%;">
                    {{-- Header --}}
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #001A35 0%, #14385E 100%); padding: 32px 20px 20px 20px; border-radius:10px 10px 0 0;">
                            <span style="display:inline-block; font-size:24px; font-weight:bold; color:#ffffff;">MercuryApp</span>
                        </td>
                    </tr>
                    
                    {{-- Content --}}
                    <tr>
                        <td style="padding:36px 16px 24px 16px;">
                            <div style="color:#0f172a; font-size:18px; font-weight:600; margin-bottom:18px; text-align:center;">
                                Hola {{ $customer->display_name ?? 'Cliente' }},
                            </div>
                            
                            <p style="color:#475569; font-size:15px; line-height:1.7; margin:0 0 24px 0; text-align:center;">
                                Te enviamos tu <strong>Factura Electrónica {{ $invoice->invoice_number }}</strong> autorizada por el SRI.
                            </p>

                            {{-- Invoice Details Table --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px 0; border-collapse:separate; border-spacing:0; background:#f8fafc; border-radius:10px; overflow:hidden;">
                                <tr>
                                    <td colspan="2" style="padding:16px 20px; background:#e2e8f0; color:#0f172a; font-size:15px; font-weight:600;">
                                        Detalles de la Factura
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:45%; padding:14px 20px; color:#475569; font-size:14px; font-weight:600; border-bottom:1px solid #e2e8f0;">
                                        Número de Factura
                                    </td>
                                    <td style="padding:14px 20px; color:#0f172a; font-size:14px; border-bottom:1px solid #e2e8f0;">
                                        {{ $invoice->invoice_number }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:45%; padding:14px 20px; color:#475569; font-size:14px; font-weight:600; border-bottom:1px solid #e2e8f0;">
                                        Fecha de Emisión
                                    </td>
                                    <td style="padding:14px 20px; color:#0f172a; font-size:14px; border-bottom:1px solid #e2e8f0;">
                                        {{ $invoice->issue_date->format('d/m/Y') }}
                                    </td>
                                </tr>
                                @if($invoice->authorization_number)
                                <tr>
                                    <td style="width:45%; padding:14px 20px; color:#475569; font-size:14px; font-weight:600; border-bottom:1px solid #e2e8f0;">
                                        Número de Autorización
                                    </td>
                                    <td style="padding:14px 20px; color:#0f172a; font-size:14px; border-bottom:1px solid #e2e8f0;">
                                        {{ $invoice->authorization_number }}
                                    </td>
                                </tr>
                                @endif
                                @if($invoice->access_key)
                                <tr>
                                    <td style="width:45%; padding:14px 20px; color:#475569; font-size:14px; font-weight:600; border-bottom:1px solid #e2e8f0;">
                                        Clave de Acceso
                                    </td>
                                    <td style="padding:14px 20px; color:#0f172a; font-size:12px; border-bottom:1px solid #e2e8f0; word-break:break-all;">
                                        {{ $invoice->access_key }}
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="width:45%; padding:14px 20px; color:#475569; font-size:14px; font-weight:600; border-bottom:1px solid #e2e8f0;">
                                        Subtotal
                                    </td>
                                    <td style="padding:14px 20px; color:#0f172a; font-size:14px; border-bottom:1px solid #e2e8f0;">
                                        USD {{ number_format($invoice->subtotal, 2, '.', ',') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:45%; padding:14px 20px; color:#475569; font-size:14px; font-weight:600; border-bottom:1px solid #e2e8f0;">
                                        IVA (15%)
                                    </td>
                                    <td style="padding:14px 20px; color:#0f172a; font-size:14px; border-bottom:1px solid #e2e8f0;">
                                        USD {{ number_format($invoice->tax_amount, 2, '.', ',') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:45%; padding:14px 20px; color:#0f172a; font-size:15px; font-weight:700;">
                                        Total
                                    </td>
                                    <td style="padding:14px 20px; color:#0f172a; font-size:15px; font-weight:700;">
                                        USD {{ number_format($invoice->total_amount, 2, '.', ',') }}
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#64748b; font-size:14px; line-height:1.6; text-align:center; margin:0 0 24px 0;">
                                En los archivos adjuntos encontrarás el PDF y XML de tu factura electrónica autorizada por el SRI.
                            </p>

                            <div style="text-align:center; margin:24px 0;">
                                <p style="color:#94a3b8; font-size:12px; line-height:1.5; margin:0;">
                                    Este es un correo automático, por favor no respondas a este mensaje.
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f8fafc; text-align:center; padding:24px 16px 20px 16px; border-radius:0 0 10px 10px; color:#94a3b8; font-size:12px;">
                            <p style="margin:0 0 8px 0;">
                                © 2025 {{ $company->name ?? 'MercuryApp' }}. Todos los derechos reservados.
                            </p>
                            @if($company->email)
                            <p style="margin:0 0 8px 0;">
                                <a href="mailto:{{ $company->email }}" style="color:#64748b; text-decoration:none;">{{ $company->email }}</a>
                            </p>
                            @endif
                            @if($company->phone_number)
                            <p style="margin:0;">
                                Teléfono: {{ $company->phone_number }}
                            </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

