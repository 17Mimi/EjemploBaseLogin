<?php
/**
 * activar_2fa.php - VERSIÓN MEJORADA
 * 
 * Permite activar/desactivar la autenticación de dos factores
 * Consistente con el resto de archivos del proyecto
 */

session_start();
require 'vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

// ============================================
// VERIFICAR AUTENTICACIÓN
// ============================================

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== "SI") {
    header("Location: login.php");
    exit();
}

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================

$servername = "localhost";
$username = "lab_2fa_user";      // ✅ Usuario con privilegios mínimos
$password = "Lab2FA#Secure2025"; // ✅ Cambiar por tu contraseña
$dbname = "company_info";

// Crear conexión MySQLi
$mysqli = new mysqli($servername, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// ============================================
// OBTENER DATOS DEL USUARIO
// ============================================

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_nombre = $_SESSION['Usuario'] ?? 'Usuario';

// Verificar que tengamos un ID válido
if ($usuario_id == 0) {
    die("Error: No se pudo identificar al usuario");
}

// ============================================
// VARIABLES DE ESTADO
// ============================================

$mensaje = "";
$error = "";
$qr_url = "";
$secret = "";
$tiene_2fa = false;
$modo_activacion = false; // true cuando está en proceso de activación

// ============================================
// PROCESAR FORMULARIOS
// ============================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ========================================
    // OPCIÓN 1: ACTIVAR 2FA
    // ========================================
    if (isset($_POST['activar'])) {
        $g = new GoogleAuthenticator();
        $secret = $g->generateSecret();
        
        // Guardar secreto en base de datos
        $sql = "UPDATE usuarios SET secret_2fa = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("si", $secret, $usuario_id);
            
            if ($stmt->execute()) {
                $mensaje = "✅ 2FA activado correctamente. Escanea el código QR con Google Authenticator.";
                $tiene_2fa = true;
                
                // Generar código QR
                $issuer = "SistemaUTP";
                $accountName = $usuario_nombre;
                $qr_url = GoogleQrUrl::generate($accountName, $secret, $issuer);
                
            } else {
                $error = "Error al activar 2FA: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Error preparando la consulta: " . $mysqli->error;
        }
    }
    
    // ========================================
    // OPCIÓN 2: DESACTIVAR 2FA
    // ========================================
    elseif (isset($_POST['desactivar'])) {
        $sql = "UPDATE usuarios SET secret_2fa = NULL WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $usuario_id);
            
            if ($stmt->execute()) {
                $mensaje = "✅ 2FA desactivado correctamente.";
                $tiene_2fa = false;
                $secret = "";
                $qr_url = "";
            } else {
                $error = "Error al desactivar 2FA: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Error preparando la consulta: " . $mysqli->error;
        }
    }
}

// ============================================
// OBTENER ESTADO ACTUAL DEL 2FA
// ============================================

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
        
        // Generar QR si está activo y no hay mensaje de activación reciente
        if ($tiene_2fa && !empty($secret) && empty($mensaje)) {
            $issuer = "SistemaUTP";
            $accountName = $usuario_nombre;
            $qr_url = GoogleQrUrl::generate($accountName, $secret, $issuer);
        }
    }
    $stmt->close();
}

// Cerrar conexión
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar 2FA - Sistema UTP</title>
    <link rel="stylesheet" href="Estilos/Techmania.css" type="text/css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 600px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            background: #f9f9f9;
        }
        
        .qr-section img {
            max-width: 250px;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        
        .secret-code {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            margin: 15px 0;
            word-break: break-all;
            border-left: 4px solid #007bff;
        }
        
        .instructions {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        
        .instructions h3 {
            margin-top: 0;
            color: #0056b3;
        }
        
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin: 8px 0;
        }
        
        .btn {
            padding: 12px 30px;
            margin: 10px 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-activar {
            background: #28a745;
            color: white;
        }
        
        .btn-activar:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-desactivar {
            background: #dc3545;
            color: white;
        }
        
        .btn-desactivar:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-volver {
            background: #6c757d;
            color: white;
        }
        
        .btn-volver:hover {
            background: #5a6268;
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
        }
        
        .mensaje-exito {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensaje-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .status-activo {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactivo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .center {
            text-align: center;
        }
        
        .info-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
        
        .info-box strong {
            color: #856404;
        }
    </style>
</head>
<body>
<div id="wrap">
    <div id="header"></div>
    
    <div class="container">
        <h2>🔐 Configurar Autenticación de Dos Factores</h2>
        
        <!-- Usuario actual -->
        <div class="center">
            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario_nombre); ?></p>
            <span class="status-badge <?php echo $tiene_2fa ? 'status-activo' : 'status-inactivo'; ?>">
                <?php echo $tiene_2fa ? '✅ 2FA Activado' : '🔓 2FA Desactivado'; ?>
            </span>
        </div>
        
        <!-- Mensajes -->
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje mensaje-exito">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="mensaje mensaje-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- ==================== -->
        <!-- SI 2FA ESTÁ ACTIVO -->
        <!-- ==================== -->
        <?php if ($tiene_2fa && !empty($secret)): ?>
            
            <div class="qr-section">
                <h3>✅ Tu 2FA está Activo</h3>
                
                <?php if (!empty($qr_url)): ?>
                    <p>Escanea este código QR con Google Authenticator:</p>
                    <img src="<?php echo htmlspecialchars($qr_url); ?>" alt="Código QR 2FA">
                <?php else: ?>
                    <p style="color: #dc3545;">⚠️ Error generando el código QR</p>
                <?php endif; ?>
                
                <div class="secret-code">
                    <strong>Código manual:</strong><br>
                    <?php echo chunk_split($secret, 4, ' '); ?>
                </div>
            </div>
            
            <div class="instructions">
                <h3>📱 Instrucciones</h3>
                <ol>
                    <li>Descarga <strong>Google Authenticator</strong> en tu teléfono</li>
                    <li>Abre la app y toca <strong>"+"</strong></li>
                    <li>Selecciona <strong>"Escanear código QR"</strong> o <strong>"Introducir clave de configuración"</strong></li>
                    <li>Si escaneas: Apunta la cámara al código QR arriba</li>
                    <li>Si introduces manualmente:
                        <ul>
                            <li><strong>Cuenta:</strong> <?php echo htmlspecialchars($usuario_nombre); ?></li>
                            <li><strong>Clave:</strong> <?php echo $secret; ?></li>
                            <li><strong>Tipo:</strong> Basado en el tiempo</li>
                        </ul>
                    </li>
                    <li>La app te mostrará un código de 6 dígitos que cambia cada 30 segundos</li>
                    <li>Usa ese código cada vez que inicies sesión</li>
                </ol>
            </div>
            
            <div class="info-box">
                <strong>⚠️ Importante:</strong> Guarda el código manual en un lugar seguro. 
                Si pierdes tu teléfono, lo necesitarás para configurar 2FA en un nuevo dispositivo.
            </div>
            
            <form method="POST" style="text-align: center; margin-top: 30px;">
                <button type="submit" name="desactivar" class="btn btn-desactivar" 
                        onclick="return confirm('¿Estás seguro de desactivar 2FA? Tu cuenta será menos segura.')">
                    🚫 Desactivar 2FA
                </button>
            </form>
            
        <!-- ==================== -->
        <!-- SI 2FA NO ESTÁ ACTIVO -->
        <!-- ==================== -->
        <?php else: ?>
            
            <div class="instructions">
                <h3>🔒 ¿Qué es la Autenticación de Dos Factores?</h3>
                <p>La autenticación de dos factores (2FA) añade una capa extra de seguridad a tu cuenta:</p>
                <ul>
                    <li>✅ Requiere tu contraseña + un código temporal</li>
                    <li>✅ El código cambia cada 30 segundos</li>
                    <li>✅ Solo tú puedes acceder con tu teléfono</li>
                    <li>✅ Protege tu cuenta incluso si roban tu contraseña</li>
                </ul>
            </div>
            
            <div class="info-box">
                <strong>📱 Necesitarás:</strong> La app <strong>Google Authenticator</strong> 
                instalada en tu teléfono (disponible gratis para iOS y Android).
            </div>
            
            <form method="POST" style="text-align: center; margin-top: 30px;">
                <button type="submit" name="activar" class="btn btn-activar">
                    ✅ Activar 2FA
                </button>
            </form>
            
        <?php endif; ?>
        
        <!-- Botón volver -->
        <div style="margin-top: 40px; text-align: center;">
            <a href="formularios/PanelControl.php" class="btn btn-volver">
                ← Volver al Panel de Control
            </a>
        </div>
    </div>
    
    <?php include("comunes/footer.php"); ?>
</div>
</body>
</html>