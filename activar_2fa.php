<?php
session_start();
require 'vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

// VERIFICACIÓN ROBUSTA DE SESIÓN - CORREGIDA
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== "SI") {
    header("Location: login.php");
    exit();
}

// Verificar que usuario_id existe en sesión
if (!isset($_SESSION['usuario_id'])) {
    die("<div style='color: red; padding: 20px; text-align: center;'>
        <h3>❌ Error de Sesión</h3>
        <p>No se encontró el ID de usuario en la sesión.</p>
        <a href='login.php'>Volver al Login</a>
    </div>");
}

// Crear conexión MySQLi directamente
$servername = "localhost";
$username = "miriam";
$password = "12345";
$dbname = "company_info";

$mysqli = new mysqli($servername, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// VALORES POR DEFECTO SEGUROS
$usuario_id = $_SESSION['usuario_id'];
$usuario_nombre = isset($_SESSION['Usuario']) ? $_SESSION['Usuario'] : 'Usuario';

$mensaje = "";
$qr_url = "";
$secret = "";
$tiene_2fa = false;

// Función mejorada para generar QR
function generarQRCode($usuario_nombre, $secret, $issuer = "SistemaUTP") {
    try {
        // Limpiar caracteres especiales
        $usuario_limpio = preg_replace('/[^a-zA-Z0-9]/', '', $usuario_nombre);
        $issuer_limpio = preg_replace('/[^a-zA-Z0-9]/', '', $issuer);
        
        // Generar URL del QR
        $qr_url = GoogleQrUrl::generate(
            $usuario_limpio,
            $secret,
            $issuer_limpio
        );
        
        return $qr_url;
    } catch (Exception $e) {
        error_log("Error generando QR: " . $e->getMessage());
        return false;
    }
}

// Procesar activación/desactivación de 2FA
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['activar'])) {
        // Activar 2FA
        $g = new GoogleAuthenticator();
        $secret = $g->generateSecret();
        
        // Guardar en la base de datos
        $sql = "UPDATE usuarios SET secret_2fa = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("si", $secret, $usuario_id);
            
            if ($stmt->execute()) {
                $mensaje = "<div style='color: green; padding: 10px; border: 1px solid green; background: #f0fff0; margin: 10px 0;'>✅ 2FA activado correctamente. Escanea el código QR con Google Authenticator.</div>";
                $tiene_2fa = true;
                
                // Generar QR
                $qr_url = generarQRCode($usuario_nombre, $secret, "SistemaUTP");
                
            } else {
                $mensaje = "<div style='color: red; padding: 10px; border: 1px solid red; background: #fff0f0; margin: 10px 0;'>❌ Error al activar 2FA: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $mensaje = "<div style='color: red; padding: 10px; border: 1px solid red; background: #fff0f0; margin: 10px 0;'>❌ Error preparando la consulta: " . $mysqli->error . "</div>";
        }
        
    } elseif (isset($_POST['desactivar'])) {
        // Desactivar 2FA
        $sql = "UPDATE usuarios SET secret_2fa = NULL WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $usuario_id);
            
            if ($stmt->execute()) {
                $mensaje = "<div style='color: green; padding: 10px; border: 1px solid green; background: #f0fff0; margin: 10px 0;'>✅ 2FA desactivado correctamente.</div>";
                $tiene_2fa = false;
                $secret = "";
                $qr_url = "";
            } else {
                $mensaje = "<div style='color: red; padding: 10px; border: 1px solid red; background: #fff0f0; margin: 10px 0;'>❌ Error al desactivar 2FA: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $mensaje = "<div style='color: red; padding: 10px; border: 1px solid red; background: #fff0f0; margin: 10px 0;'>❌ Error preparando la consulta: " . $mysqli->error . "</div>";
        }
    }
}

// Obtener estado actual del 2FA
$sql = "SELECT secret_2fa FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $usuario_data = $result->fetch_assoc();
        $tiene_2fa = !empty($usuario_data['secret_2fa']);
        $secret = $usuario_data['secret_2fa'] ?? '';
        
        // Generar QR si está activo
        if ($tiene_2fa && !empty($secret)) {
            $qr_url = generarQRCode($usuario_nombre, $secret, "SistemaUTP");
        }
    }
    $stmt->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar 2FA</title>
    <link rel="stylesheet" href="Estilos/Techmania.css" type="text/css" />
    <style>
        .container {
            max-width: 500px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .secret-code {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
            word-break: break-all;
            font-size: 14px;
        }
        .btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .btn-activar {
            background: #28a745;
            color: white;
        }
        .btn-activar:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-desactivar {
            background: #dc3545;
            color: white;
        }
        .btn-desactivar:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        .btn-volver {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            display: inline-block;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-volver:hover {
            background: #5a6268;
            text-decoration: none;
            color: white;
        }
        .instructions {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: left;
        }
        .debug-info {
            background: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 12px;
            display: none;
        }
        .security-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
        }
        .step-number {
            background: #007bff;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        .success-message {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .qr-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
    </style>
</head>
<body>
<div id="wrap">
    <div id="header"></div>
    
    <div class="container">
        <h2 style="text-align: center; color: #333; margin-bottom: 20px;">🔐 Configurar Autenticación de Dos Factores</h2>
        
        <div class="security-badge">
            <h3 style="margin: 0;">🛡️ Seguridad Mejorada</h3>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Protege tu cuenta con una capa adicional de seguridad</p>
        </div>
        
        <?php echo $mensaje; ?>
        
        <!-- Información de debug -->
        <div class="debug-info" id="debugInfo">
            <strong>🔧 Información de Debug:</strong><br>
            Usuario ID: <?php echo $usuario_id; ?><br>
            Usuario Nombre: <?php echo htmlspecialchars($usuario_nombre); ?><br>
            Secret: <?php echo htmlspecialchars($secret); ?><br>
            Tiene 2FA: <?php echo $tiene_2fa ? 'Sí' : 'No'; ?><br>
            Session Autenticado: <?php echo $_SESSION['autenticado'] ?? 'No definido'; ?><br>
        </div>
        
        <?php if ($tiene_2fa && !empty($secret)): ?>
            <div class="qr-container">
                <h3 style="color: #28a745; text-align: center;">✅ 2FA Activado</h3>
                
                <?php if ($qr_url): ?>
                    <div style="text-align: center; margin: 20px 0;">
                        <img src="<?php echo $qr_url; ?>" 
                             alt="Código QR para Google Authenticator" 
                             style="border: 2px solid #e9ecef; border-radius: 10px; max-width: 250px; height: auto;">
                        <p style="margin-top: 10px; color: #666;">
                            <small>Escanea este código QR con Google Authenticator</small>
                        </p>
                    </div>
                <?php else: ?>
                    <div style="color: orange; padding: 15px; background: #fff8e1; border: 1px solid #ffd54f; border-radius: 5px; margin: 15px 0;">
                        <strong>⚠️ Código QR no disponible</strong>
                        <p>Puedes usar el código secreto para configurar manualmente:</p>
                    </div>
                <?php endif; ?>
                
                <div class="secret-code">
                    <strong>🔑 Código Secreto (para configuración manual):</strong><br>
                    <span style="font-size: 16px; font-weight: bold; letter-spacing: 2px; color: #333;">
                        <?php echo chunk_split($secret, 4, ' '); ?>
                    </span>
                </div>
                
                <div class="instructions">
                    <h4 style="margin-top: 0;">📱 Instrucciones de Configuración:</h4>
                    <ol style="text-align: left; padding-left: 20px;">
                        <li><span class="step-number">1</span> Descarga <strong>Google Authenticator</strong> desde tu tienda de apps</li>
                        <li><span class="step-number">2</span> Abre la app y toca el botón <strong>"+"</strong></li>
                        <li><span class="step-number">3</span> Selecciona <strong>"Escanear código QR"</strong></li>
                        <li><span class="step-number">4</span> Apunta tu cámara al código QR de arriba</li>
                        <li><span class="step-number">5</span> <strong>O alternativamente:</strong> Selecciona "Introducir clave" y usa el código secreto</li>
                    </ol>
                </div>
                
                <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #28a745;">
                    <strong>✅ Verificación:</strong> Después de configurar, verifica que funciona ingresando un código generado en tu app.
                </div>
            </div>
            
            <form method="POST" style="text-align: center;">
                <button type="submit" name="desactivar" class="btn btn-desactivar" 
                        onclick="return confirm('¿Estás seguro de que quieres desactivar 2FA? Esto reduce la seguridad de tu cuenta.')">
                    🚫 Desactivar 2FA
                </button>
            </form>
            
        <?php else: ?>
            <div style="text-align: center; padding: 20px;">
                <h3 style="color: #6c757d;">🔓 2FA No Activado</h3>
                <p style="color: #666; margin-bottom: 20px;">La autenticación de dos factores añade una capa extra de seguridad a tu cuenta.</p>
                
                <div class="instructions">
                    <h4 style="margin-top: 0;">🛡️ ¿Qué es 2FA?</h4>
                    <ul style="text-align: left; padding-left: 20px;">
                        <li>✅ Requiere tu contraseña + un código temporal</li>
                        <li>✅ El código cambia cada 30 segundos</li>
                        <li>✅ Necesitas la app Google Authenticator en tu teléfono</li>
                        <li>✅ Protege tu cuenta incluso si roban tu contraseña</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <button type="submit" name="activar" class="btn btn-activar">
                        ✅ Activar Autenticación de Dos Factores
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="formularios/PanelControl.php" class="btn-volver">← Volver al Panel de Control</a>
        </div>
        
        <!-- Botón para mostrar debug info -->
        <div style="text-align: center; margin-top: 15px;">
            <button onclick="document.getElementById('debugInfo').style.display='block'" 
                    style="font-size: 10px; padding: 5px 10px; background: #ffc107; border: none; border-radius: 3px; cursor: pointer;">
                🔧 Mostrar Info Debug
            </button>
        </div>
    </div>
    
    <?php include("comunes/footer.php"); ?>
</div>

<script>
// Efectos de loading para botones
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const buttons = this.querySelectorAll('button[type="submit"]');
            buttons.forEach(button => {
                button.innerHTML = '⏳ Procesando...';
                button.classList.add('loading');
            });
        });
    });
    
    // Mostrar mensajes de éxito con animación
    const successMessages = document.querySelectorAll('[style*="color: green"]');
    successMessages.forEach(msg => {
        msg.classList.add('success-message');
    });
});
</script>
</body>
</html>