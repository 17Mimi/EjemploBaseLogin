<?php
session_start();

// Configuración de la base de datos
$servername = "localhost";
$database_username = "miriam";
$database_password = "12345";
$dbname = "company_info";

// Crear conexión
$conn = new mysqli($servername, $database_username, $database_password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $contrasena = $_POST['contrasena'];

    // Buscar usuario
    $sql = "SELECT id, usuario, HashMagic, secret_2fa FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $usuario_data = $result->fetch_assoc();
            
            // Verificar contraseña
            if (password_verify($contrasena, $usuario_data['HashMagic'])) {
                
                // ✅ VERIFICAR SI TIENE 2FA ACTIVADO
                if (!empty($usuario_data['secret_2fa'])) {
                    // 🔐 USUARIO CON 2FA - Redirigir a autenticar.php
                    $_SESSION['usuario_pendiente_2fa'] = $usuario_data['id'];
                    $_SESSION['usuario_nombre_pendiente'] = $usuario_data['usuario'];
                    $_SESSION['secret_2fa_pendiente'] = $usuario_data['secret_2fa'];
                    
                    $stmt->close();
                    $conn->close();
                    
                    header("Location: autenticar.php");
                    exit();
                    
                } else {
                    // ✅ USUARIO SIN 2FA - Login directo
                    $_SESSION['autenticado'] = "SI";
                    $_SESSION['usuario_id'] = $usuario_data['id'];
                    $_SESSION['Usuario'] = $usuario_data['usuario'];
                    
                    $stmt->close();
                    $conn->close();
                    
                    header("Location: formularios/PanelControl.php");
                    exit();
                }
                
            } else {
                // ❌ Contraseña incorrecta
                $_SESSION["emsg"] = 1;
                header("Location: login.php");
                exit();
            }
        } else {
            // ❌ Usuario no existe
            $_SESSION["emsg"] = 1;
            header("Location: login.php");
            exit();
        }
    } else {
        // ❌ Error en consulta
        $_SESSION["emsg"] = 1;
        header("Location: login.php");
        exit();
    }
} else {
    // ❌ No es POST
    header("Location: login.php");
    exit();
}
?>