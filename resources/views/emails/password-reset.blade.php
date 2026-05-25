<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .content {
            padding: 40px 30px;
            color: #333;
        }
        
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .reset-section {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 6px;
            margin: 30px 0;
            border-left: 4px solid #667eea;
        }
        
        .reset-section p {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: #666;
        }
        
        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 40px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        
        .reset-button:hover {
            transform: scale(1.05);
        }
        
        .token-info {
            background-color: #e8f4f8;
            border: 1px solid #b3dfe8;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 12px;
            color: #555;
        }
        
        .token-info strong {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }
        
        .token {
            word-break: break-all;
            font-family: monospace;
            background-color: #fff;
            padding: 10px;
            border-radius: 4px;
            margin-top: 8px;
        }
        
        .warning {
            color: #d32f2f;
            font-size: 13px;
            margin: 20px 0;
            padding: 10px;
            background-color: #ffebee;
            border-left: 3px solid #d32f2f;
            border-radius: 3px;
        }
        
        .footer {
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
            color: #999;
            font-size: 12px;
            border-top: 1px solid #eee;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .divider {
            height: 1px;
            background-color: #eee;
            margin: 30px 0;
        }
        
        ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        
        ul li {
            margin: 8px 0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔐 Recuperación de Contraseña</h1>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="greeting">
               <p>¡Hola {{ $usuario->cli_correo ?? $usuario->emp_correo ?? $usuario->correo ?? $usuario->email }}!</p>
                <p>Hemos recibido una solicitud para recuperar tu contraseña en JHP API. Si no fuiste tú, puedes ignorar este correo de forma segura.</p>
            </div>
            
            <div class="reset-section">
                <p><strong>Para resetear tu contraseña, haz clic en el siguiente enlace:</strong></p>
                
                <center>
                    <a href="{{ $resetUrl }}" class="reset-button">Recuperar Contraseña</a>
                </center>
                
                <p style="text-align: center; font-size: 12px; color: #999;">
                    O copia y pega este enlace en tu navegador:<br>
                    <code style="word-break: break-all; color: #666;">{{ $resetUrl }}</code>
                </p>
            </div>
            
            <div class="warning">
                <strong>⚠️ Importante:</strong>
                <ul style="margin: 10px 0;">
                    <li>Este enlace es válido por 24 horas</li>
                    <li>Este enlace solo se puede usar una vez</li>
                    <li>No compartas este correo ni el enlace con nadie</li>
                </ul>
            </div>
            
            <div class="token-info">
                <strong>Token de recuperación (en caso de que lo necesites):</strong>
                <div class="token">{{ $token }}</div>
            </div>
            
            <div class="divider"></div>
            
            <p style="color: #999; font-size: 12px; margin-top: 20px;">
                <strong>Si no solicitaste recuperar tu contraseña:</strong><br>
                Si recibiste este correo por error y no solicitaste recuperar tu contraseña, 
                por favor ignóralo. Tu cuenta está segura y solo se verá afectada si alguien 
                logra acceder al enlace de recuperación.
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 10px 0;">
                © {{ date('Y') }} JHP API - Sistema de Gestión Automotriz
            </p>
            <p style="margin: 0;">
                Por preguntas o problemas, contacta a <a href="mailto:anne2jhp@gmail.com">anne2jhp@gmail.com</a>
            </p>
            <p style="margin: 10px 0 0 0; color: #bbb;">
                Este es un correo automatizado. No respondas a este correo.
            </p>
        </div>
    </div>
</body>
</html>
