<?php
session_start();
require 'vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

// Verificar que el usuario esté logueado
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== "SI") {
    header("Location: login.php");
    exit();
}

// Crear conexión MySQLi directamente
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "company_info";

$mysqli = new mysqli($servername, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nombre = $_SESSION['Usuario'];

$mensaje = "";
$qr_url = "";
$secret = "";
$tiene_2fa = false;

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
                
                // ⭐⭐ GENERAR QR CORREGIDO ⭐⭐
                $issuer = "SistemaUTP"; // Sin espacios
                $accountName = $usuario_nombre;
                
                // Método 1: Usar GoogleQrUrl directamente
                $qrContent = GoogleQrUrl::generate($accountName, $secret, $issuer);
                $qr_url = $qrContent;
                
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
            $issuer = "SistemaUTP";
            $accountName = $usuario_nombre;
            $qrContent = GoogleQrUrl::generate($accountName, $secret, $issuer);
            $qr_url = $qrContent;
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
        }
        .btn-activar {
            background: #28a745;
            color: white;
        }
        .btn-activar:hover {
            background: #218838;
        }
        .btn-desactivar {
            background: #dc3545;
            color: white;
        }
        .btn-desactivar:hover {
            background: #c82333;
        }
        .btn-volver {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            display: inline-block;
            border-radius: 5px;
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
            display: none; /* Oculto por defecto */
        }
    </style>
</head>
<body>
<div id="wrap">
    <div id="header"></div>
    
    <div class="container">
        <h2>🔐 Configurar Autenticación de Dos Factores</h2>
        
        <?php echo $mensaje; ?>
        
        <!-- Información de debug (puedes activarla si hay problemas) -->
        <div class="debug-info" id="debugInfo">
            <strong>Debug Info:</strong><br>
            Secret: <?php echo htmlspecialchars($secret); ?><br>
            QR URL Length: <?php echo strlen($qr_url); ?><br>
            Tiene 2FA: <?php echo $tiene_2fa ? 'Sí' : 'No'; ?>
        </div>
        
        <?php if ($tiene_2fa && !empty($secret)): ?>
            <div class="qr-code">
                <h3>✅ 2FA Activado</h3>
                
                <!-- ⭐⭐ QR CORREGIDO - Método directo ⭐⭐ -->
                <?php if (!empty($qr_url)): ?>
                    <img src="<?php echo $qr_url; ?>" alt="Código QR" style="border: 1px solid #ddd; max-width: 100%;">
                    <p><small>Escanea este código QR con Google Authenticator</small></p>
                <?php else: ?>
                    <div style="color: red; padding: 10px;">
                        ❌ Error generando el código QR
                    </div>
                <?php endif; ?>
                
                <div class="secret-code">
                    <strong>Si no puedes escanear el QR, ingresa este código manualmente:</strong><br>
                    <span style="font-size: 16px; font-weight: bold;"><?php echo $secret; ?></span>
                </div>
                
                <div class="instructions">
                    <strong>Instrucciones para agregar manualmente:</strong><br>
                    1. Abre Google Authenticator<br>
                    2. Toca "+" → "Ingresar una clave de configuración"<br>
                    3. Ingresa:<br>
                       - <strong>Cuenta:</strong> <?php echo htmlspecialchars($usuario_nombre); ?><br>
                       - <strong>Clave:</strong> <?php echo $secret; ?><br>
                    4. Asegúrate de que sea "Basado en el tiempo"
                </div>
            </div>
            
            <form method="POST" style="text-align: center;">
                <button type="submit" name="desactivar" class="btn btn-desactivar">🚫 Desactivar 2FA</button>
            </form>
            
        <?php else: ?>
            <div style="text-align: center;">
                <h3>🔓 2FA No Activado</h3>
                <p>La autenticación de dos factores añade una capa extra de seguridad a tu cuenta.</p>
                
                <div class="instructions">
                    <strong>¿Qué es 2FA?</strong><br>
                    - Requiere tu contraseña + un código temporal<br>
                    - El código cambia cada 30 segundos<br>
                    - Necesitas la app Google Authenticator en tu teléfono<br>
                    - Protege tu cuenta incluso si roban tu contraseña
                </div>
                
                <form method="POST">
                    <button type="submit" name="activar" class="btn btn-activar">✅ Activar 2FA</button>
                </form>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="formularios/PanelControl.php" class="btn-volver">← Volver al Panel</a>
        </div>
        
        <!-- Botón para mostrar debug info -->
        <div style="text-align: center; margin-top: 10px;">
            <button onclick="document.getElementById('debugInfo').style.display='block'" style="font-size: 10px; padding: 5px;">Mostrar Info Debug</button>
        </div>
    </div>
    
    <?php include("comunes/footer.php"); ?>
</div>
</body>
</html>