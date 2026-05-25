<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperación de contraseña JHP</title>
</head>
<body style="margin:0;padding:0;background:#eef4f7;font-family:Arial,Helvetica,sans-serif;color:#102a43;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef4f7;margin:0;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #d8e3e8;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#0f5f7a;padding:24px 28px;text-align:center;">
                            <div style="display:inline-block;background:#ffffff;border-radius:12px;padding:10px 14px;color:#0f5f7a;font-weight:900;font-size:22px;letter-spacing:.5px;">
                                JHP Motos
                            </div>
                            <h1 style="margin:18px 0 0;color:#ffffff;font-size:24px;line-height:1.25;font-weight:900;">
                                Recuperación de contraseña
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 28px 8px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#334e68;">
                                Recibimos una solicitud para restablecer la contraseña de tu cuenta en JHP Motos.
                            </p>
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.6;color:#334e68;">
                                Usa el siguiente código en la pantalla de recuperación:
                            </p>
                            <div style="margin:24px 0;text-align:center;">
                                <div style="display:inline-block;background:#f8fbfc;border:2px solid #0f5f7a;border-radius:12px;padding:18px 28px;color:#102a43;font-size:34px;font-weight:900;letter-spacing:8px;">
                                    {{ $token }}
                                </div>
                            </div>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#627d98;text-align:center;">
                                Este código vence en {{ $expiresIn }} minutos.
                            </p>

                            @if (!empty($resetUrl))
                                <div style="margin:24px 0;text-align:center;">
                                    <a href="{{ $resetUrl }}" style="display:inline-block;background:#0f5f7a;color:#ffffff;text-decoration:none;border-radius:10px;padding:14px 22px;font-size:15px;font-weight:800;">
                                        Abrir recuperación
                                    </a>
                                </div>
                            @endif

                            <p style="margin:20px 0 0;font-size:14px;line-height:1.6;color:#627d98;">
                                Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña actual seguirá siendo válida.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 28px 28px;">
                            <div style="border-top:1px solid #d8e3e8;padding-top:18px;">
                                <p style="margin:0;font-size:12px;line-height:1.5;color:#829ab1;">
                                    Correo enviado a {{ $correo }} por JHP Motos. Por seguridad, no compartas este código con nadie.
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
