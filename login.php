<?php
session_start();

// Incluir autoload de Composer para Google Authenticator
require 'vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

// Configuración
$g = new GoogleAuthenticator();

// Funciones de base de datos (debes adaptarlas a tu sistema)
function verificarCredenciales($usuario, $contrasena) {
    // Tu lógica de verificación aquí
    // Retorna el ID del usuario si es válido, false si no
    // Ejemplo temporal:
    if ($usuario === "admin" && $contrasena === "123456") {
        return 1;
    }
    return false;
}

function obtenerSecret2FA($usuario_id) {
    // Conectar a tu base de datos y obtener el secret_2fa
    // Ejemplo temporal:
    return isset($_SESSION['user_secret']) ? $_SESSION['user_secret'] : '';
}

function guardarSecret2FA($usuario_id, $secret) {
    // Guardar en tu base de datos
    // Ejemplo temporal:
    $_SESSION['user_secret'] = $secret;
    return true;
}

function obtenerUsuarioId($usuario) {
    // Obtener ID de usuario desde BD
    return 1; // Ejemplo
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tolog'])) {
    // Verificar CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token CSRF inválido");
    }
    
    $usuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    
    // Verificar credenciales básicas
    $usuario_id = verificarCredenciales($usuario, $contrasena);
    
    if ($usuario_id) {
        // Credenciales correctas - verificar si tiene 2FA activado
        $secret_2fa = obtenerSecret2FA($usuario_id);
        
        if (!empty($secret_2fa)) {
            // Usuario tiene 2FA activado - redirigir a verificación
            $_SESSION['usuario_pendiente_2fa'] = $usuario_id;
            $_SESSION['usuario_temp'] = $usuario;
            $_SESSION['secret_2fa_temp'] = $secret_2fa;
            header('Location: verificar_2fa.php');
            exit;
        } else {
            // Usuario no tiene 2FA - login directo
            $_SESSION['usuario'] = $usuario;
            $_SESSION['loggedin'] = true;
            $_SESSION['usuario_id'] = $usuario_id;
            header('Location: dashboard.php');
            exit;
        }
    } else {
        // Credenciales incorrectas
        $_SESSION["emsg"] = 1;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>