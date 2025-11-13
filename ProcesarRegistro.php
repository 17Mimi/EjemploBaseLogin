<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "company_info";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger y validar datos
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $usuario = trim($_POST['usuario']);
    $correo = trim($_POST['correo']);
    $contraseña = $_POST['contraseña'];
    
    // Validaciones básicas
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (empty($apellido)) {
        $errores[] = "El apellido es obligatorio";
    }
    
    if (empty($usuario)) {
        $errores[] = "El nombre de usuario es obligatorio";
    } elseif (strlen($usuario) < 4) {
        $errores[] = "El nombre de usuario debe tener al menos 4 caracteres";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $usuario)) {
        $errores[] = "El nombre de usuario solo puede contener letras, números y guiones bajos";
    }
    
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    }
    
    if (empty($contraseña) || strlen($contraseña) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Si no hay errores, proceder con el registro
    if (empty($errores)) {
        // Hash de la contraseña (importante para seguridad)
        $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
        
        // Preparar la consulta SQL
        $sql = "INSERT INTO usuarios (Nombre, Apellido, Usuario, Correo, HashMagic) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $nombre, $apellido, $usuario, $correo, $contraseña_hash);
        
        if ($stmt->execute()) {
            // Redirigir al formulario con mensaje de éxito
            header("Location: FormularioRegistro.php?success=1");
            exit();
        } else {
            // Verificar si es error de usuario o correo duplicado
            if ($conn->errno == 1062) {
                $error_message = $conn->error;
                if (strpos($error_message, 'usuario') !== false) {
                    header("Location: FormularioRegistro.php?error=El nombre de usuario ya está en uso");
                } elseif (strpos($error_message, 'correo') !== false) {
                    header("Location: FormularioRegistro.php?error=El correo electrónico ya está registrado");
                } else {
                    header("Location: FormularioRegistro.php?error=El usuario o correo electrónico ya existen");
                }
            } else {
                header("Location: FormularioRegistro.php?error=Error al guardar los datos: " . $conn->error);
            }
            exit();
        }
        
        $stmt->close();
    } else {
        // Si hay errores, redirigir con mensajes de error
        header("Location: FormularioRegistro.php?error=" . urlencode(implode(", ", $errores)));
        exit();
    }
} else {
    // Si no es POST, redirigir al formulario
    header("Location: FormularioRegistro.php");
    exit();
}

$conn->close();
?>