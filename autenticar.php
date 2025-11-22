<?php
session_start();

// Verificar que viene del proceso de login
if (!isset($_SESSION['usuario_pendiente_2fa'])) {
    header("Location: login.php");
    exit();
}

// MOVER LOS IMPORTS AL INICIO
require 'vendor/autoload.php';
use Sonata\GoogleAuthenticator\GoogleAuthenticator;

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['codigo_2fa'])) {
    $g = new GoogleAuthenticator();
    $codigo_ingresado = trim($_POST['codigo_2fa']);
    $secret = $_SESSION['secret_2fa_pendiente'];
    
    // Verificar el código 2FA
    if ($g->checkCode($secret, $codigo_ingresado)) {
        // Código correcto, completar login
        $_SESSION['autenticado'] = "SI";
        $_SESSION['usuario_id'] = $_SESSION['usuario_pendiente_2fa'];
        $_SESSION['Usuario'] = $_SESSION['usuario_nombre_pendiente'];
        
        // Limpiar variables temporales
        unset($_SESSION['usuario_pendiente_2fa']);
        unset($_SESSION['usuario_nombre_pendiente']);
        unset($_SESSION['secret_2fa_pendiente']);
        unset($_SESSION['intentos_2fa']);
        
        header("Location: formularios/PanelControl.php");
        exit();
    } else {
        // Incrementar intentos fallidos
        $_SESSION['intentos_2fa'] = ($_SESSION['intentos_2fa'] ?? 0) + 1;
        $mensaje = "<div style='color: red; padding: 10px; border: 1px solid red; background: #fff0f0; margin: 10px 0;'>❌ Código 2FA incorrecto. Intenta nuevamente.</div>";
        
        // Si hay muchos intentos fallidos, redirigir al login
        if ($_SESSION['intentos_2fa'] >= 3) {
            unset($_SESSION['usuario_pendiente_2fa']);
            unset($_SESSION['usuario_nombre_pendiente']);
            unset($_SESSION['secret_2fa_pendiente']);
            unset($_SESSION['intentos_2fa']);
            $_SESSION["emsg"] = 1;
            header("Location: login.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA</title>
    <link rel="stylesheet" href="Estilos/Techmania.css" type="text/css" />
    <style>
        .container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .input-2fa {
            font-size: 18px;
            padding: 12px;
            width: 200px;
            text-align: center;
            letter-spacing: 8px;
            border: 2px solid #ddd;
            border-radius: 5px;
            margin: 15px 0;
        }
        .btn-verificar {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-verificar:hover {
            background: #218838;
        }
        .info-box {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: left;
        }
    </style>
</head>
<body>
<div id="wrap">
    <div id="header"></div>
    
    <div class="container">
        <h2>🔐 Verificación en Dos Pasos</h2>
        <p>Por favor, ingresa el código de 6 dígitos de Google Authenticator</p>
        
        <?php echo $mensaje; ?>
        
        <div class="info-box">
            <strong>📱 Instrucciones:</strong><br>
            1. Abre Google Authenticator en tu dispositivo<br>
            2. Encuentra el código de 6 dígitos para <strong><?php echo $_SESSION['usuario_nombre_pendiente']; ?></strong><br>
            3. Ingresa el código a continuación
        </div>
        
        <form method="POST">
            <input type="text" 
                   name="codigo_2fa" 
                   class="input-2fa" 
                   placeholder="000000" 
                   maxlength="6" 
                   pattern="[0-9]{6}" 
                   required
                   autocomplete="off"
                   autofocus>
            <br>
            <button type="submit" class="btn-verificar">✅ Verificar Código</button>
        </form>
        
        <div style="margin-top: 20px;">
            <a href="login.php" style="color: #6c757d; text-decoration: none;">← Volver al Login</a>
        </div>
    </div>
    
    <?php include("comunes/footer.php"); ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.querySelector('input[name="codigo_2fa"]');
    input.focus();
    
    input.addEventListener('input', function() {
        if (this.value.length === 6) {
            this.form.submit();
        }
    });
});
</script>
</body>
</html>