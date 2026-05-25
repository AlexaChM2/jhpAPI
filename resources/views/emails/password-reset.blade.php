<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperación de Contraseña</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #ff3d00;">🔐 Recuperación de Contraseña</h2>
        
        <p>Hola <strong>{{ $nombre }}</strong>,</p>
        
        <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>
        
        <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}" 
               style="background: linear-gradient(135deg, #ff3d00 0%, #ff6b35 100%); 
                      color: white; 
                      padding: 12px 30px; 
                      text-decoration: none; 
                      border-radius: 25px; 
                      font-weight: bold;">
                Restablecer Contraseña
            </a>
        </div>
        
        <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
        <p style="background: #f4f4f4; padding: 10px; word-break: break-all;">{{ $resetUrl }}</p>
        
        <p>Tu token de recuperación es: <strong>{{ $token }}</strong></p>
        
        <p><strong>Importante:</strong> Este enlace expirará en 24 horas.</p>
        
        <hr>
        
        <p style="font-size: 12px; color: #666;">
            Si no solicitaste este cambio, ignora este mensaje. Tu contraseña permanecerá igual.
        </p>
        
        <p style="font-size: 12px; color: #666;">
            &copy; {{ date('Y') }} JHP Motos POS - Sistema de Gestión de Motocicletas
        </p>
    </div>
</body>
</html>