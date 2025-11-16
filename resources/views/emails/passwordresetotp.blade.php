<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecimiento de Contraseña - MercuryApp</title>
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
                            <div style="color:#222; font-size:16px; line-height:1.7; margin-bottom:24px; text-align:center;">
                                Hola {{ $user_name }},<br><br>
                                Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.<br>
                                Utiliza el siguiente código para continuar con el proceso:
                            </div>
                            <div style="background:#f1f5f9; color:#1e293b; font-size:28px; font-weight:bold; letter-spacing:8px; border-radius:8px; padding:18px 0; text-align:center; margin-bottom:24px;">
                                {{ $reset_code }}
                            </div>
                            <div style="color:#64748b; font-size:15px; line-height:1.6; text-align:center;">
                                Este código es válido por 10 minutos. Ingresa este código en la plataforma para continuar con el proceso de restablecimiento de contraseña.
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

