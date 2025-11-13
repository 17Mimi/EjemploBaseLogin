<?php
session_start();
require 'vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

// Verificar si el usuario está en proceso de login 2FA
if (!isset($_SESSION['usuario_pendiente_2fa'])) {
    header("Location: login.php");
    exit();
}

// Crear conexión MySQLi directamente
$servername = "localhost";
$username = "lab_2fa_user";      
$password = "Lab2FA#Secure2025";  
$dbname = "company_info";

$mysqli = new mysqli($servername, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

$usuario_id = $_SESSION['usuario_pendiente_2fa'];
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';

// Obtener el secreto de la base de datos
$sql = "SELECT secret_2fa FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("Error preparando consulta: " . $mysqli->error);
}

$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    // Error: usuario no encontrado
    $stmt->close();
    $mysqli->close();
    $_SESSION["emsg"] = 1;
    header("Location: login.php");
    exit();
}

$usuario_data = $result->fetch_assoc();
$secret = $usuario_data['secret_2fa'];
$mensaje = "";

// Procesar formulario de verificación 2FA
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim($_POST['codigo']);
    $g = new GoogleAuthenticator();

    if ($g->checkCode($secret, $codigo)) {
        // Código válido - acceso concedido
        $_SESSION['autenticado'] = "SI";
        $_SESSION['Usuario'] = $usuario_nombre;
        $_SESSION['usuario_id'] = $usuario_id;
        $_SESSION['autenticado_2fa'] = true;
        
        // Limpiar sesión temporal de 2FA
        unset($_SESSION['usuario_pendiente_2fa']);
        unset($_SESSION['usuario_nombre']);
        
        // Cerrar conexión
        $stmt->close();
        $mysqli->close();
        
        // Redirigir al panel de control
        header("Location: formularios/PanelControl.php");
        exit();
    } else {
        // Código incorrecto
        $mensaje = "<div style='color: red; padding: 10px; border: 1px solid red; background: #fff0f0; margin: 10px 0;'>✗ Código incorrecto. Intenta nuevamente.</div>";
    }
}

// Cerrar statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Dos Factores</title>
    
    <link rel="stylesheet" href="Estilos/Techmania.css" type="text/css" />
    <link rel="stylesheet" href="Estilos/general.css" type="text/css">
    <link rel="stylesheet" href="css/cmxform.css" type="text/css" />
    
    <style>
        .container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #333;
        }
        
        input[type="text"] {
            width: 150px;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 5px;
            font-weight: bold;
        }
        
        input[type="text"]:focus {
            border-color: #007bff;
            outline: none;
        }
        
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: #0056b3;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .instructions {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: left;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div id="wrap">
    <div id="headerlogin"></div>
    
    <div class="container">
        <h2>🔐 Verificación de Dos Factores</h2>
        
        <div class="user-info">
            <strong>Usuario:</strong> <?php echo htmlspecialchars($usuario_nombre); ?>
        </div>
        
        <div class="instructions">
            <strong>Instrucciones:</strong><br>
            1. Abre la app <strong>Google Authenticator</strong><br>
            2. Busca el código de 6 dígitos<br>
            3. Ingresa el código a continuación
        </div>
        
        <?php echo $mensaje; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="codigo">Código de Verificación:</label>
                <input type="text" id="codigo" name="codigo" required 
                       maxlength="6" pattern="[0-9]{6}" 
                       placeholder="123456" 
                       title="Ingresa los 6 dígitos de Google Authenticator"
                       autocomplete="off"
                       autofocus>
            </div>
            
            <button type="submit">✅ Verificar y Acceder</button>
        </form>
        
        <div style="margin-top: 20px; font-size: 12px; color: #666;">
            <p>¿Problemas con el código?</p>
            <ul style="text-align: left; display: inline-block;">
                <li>Asegúrate de que la hora de tu dispositivo esté sincronizada</li>
                <li>El código expira cada 30 segundos</li>
                <li>Si persisten los problemas, contacta al administrador</li>
            </ul>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="login.php" style="color: #007bff; text-decoration: none;">← Volver al Login</a>
        </div>
    </div>
    
    <?php include("comunes/footer.php"); ?>
</div>

<script>
// Auto-enfocar el campo de código y permitir solo números
document.getElementById('codigo').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Auto-submit cuando se ingresen 6 dígitos
document.getElementById('codigo').addEventListener('input', function(e) {
    if (this.value.length === 6) {
        this.form.submit();
    }
});
</script>
</body>
</html>

<?php
// Cerrar conexión al final
$mysqli->close();
?>