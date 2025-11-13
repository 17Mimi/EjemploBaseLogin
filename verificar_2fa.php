<?php
session_start();
require 'vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

if (!isset($_SESSION['usuario_pendiente_2fa'])) {
    header('Location: login.php');
    exit;
}

$g = new GoogleAuthenticator();
$secret = obtenerSecret2FA($_SESSION['usuario_pendiente_2fa']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $_POST['codigo_2fa'] ?? '';
    
    if ($g->checkCode($secret, $codigo)) {
        // Código correcto - login completo
        $_SESSION['usuario'] = $_SESSION['usuario_temp'];
        $_SESSION['loggedin'] = true;
        unset($_SESSION['usuario_pendiente_2fa'], $_SESSION['usuario_temp']);
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Código 2FA incorrecto. Por favor, intente nuevamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación 2FA</title>
    <style>
        .verification-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
        }
        .code-input {
            font-size: 18px;
            padding: 10px;
            width: 200px;
            text-align: center;
            letter-spacing: 5px;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h2>🔒 Verificación en Dos Pasos</h2>
        <p>Por favor, ingrese el código de 6 dígitos de Google Authenticator</p>
        
        <?php if (isset($error)): ?>
            <div style="color: red; margin: 10px 0;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="text" name="codigo_2fa" class="code-input" maxlength="6" 
                   pattern="[0-9]{6}" placeholder="000000" required>
            <br><br>
            <button type="submit" style="padding: 10px 20px;">Verificar</button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="login.php">← Volver al login</a>
        </p>
    </div>
</body>
</html>