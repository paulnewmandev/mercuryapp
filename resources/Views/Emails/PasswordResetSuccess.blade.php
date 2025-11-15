<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu contraseña fue actualizada - MercuryApp</title>
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
                            <div style="color:#0f172a; font-size:18px; font-weight:600; margin-bottom:18px; text-align:center;">
                                Hola {{ $user_name }},
                            </div>
                            <p style="color:#475569; font-size:15px; line-height:1.7; margin:0 0 24px 0; text-align:center;">
                                Tu contraseña de acceso a <strong>MercuryApp</strong> fue actualizada exitosamente. A continuación te compartimos los datos que necesitas para iniciar sesión.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px 0; border-collapse:separate; border-spacing:0; background:#f8fafc; border-radius:10px; overflow:hidden;">
                                <tr>
                                    <td colspan="2" style="padding:16px 20px; background:#e2e8f0; color:#0f172a; font-size:15px; font-weight:600;">
                                        Tus credenciales MercuryApp
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:45%; padding:14px 20px; color:#475569; font-size:14px; font-weight:600; border-bottom:1px solid #e2e8f0;">
                                        Correo electrónico
                                    </td>
                                    <td style="padding:14px 20px; color:#0f172a; font-size:14px; border-bottom:1px solid #e2e8f0;">
                                        {{ $user_email }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:45%; padding:14px 20px; color:#475569; font-size:14px; font-weight:600;">
                                        Nueva contraseña
                                    </td>
                                    <td style="padding:14px 20px; color:#0f172a; font-size:14px;">
                                        {{ $new_password }}
                                    </td>
                                </tr>
                            </table>

                            <div style="text-align:center; margin-bottom:24px;">
                                <a href="{{ $login_url }}" style="display:inline-block; padding:14px 28px; background:#001A35; color:#ffffff; text-decoration:none; font-size:15px; font-weight:600; border-radius:10px;">
                                    Iniciar sesión en MercuryApp
                                </a>
                            </div>

                            <p style="color:#64748b; font-size:14px; line-height:1.6; margin:0; text-align:center;">
                                Si no reconoces este cambio, contáctanos de inmediato para ayudarte a asegurar tu cuenta. Te recomendamos actualizar tu contraseña periódicamente y mantenerla privada.
                            </p>
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

