<?php
session_start();

// Configuración de la base de datos
$servername = "localhost";
$database_username = "miriam";
$database_password = "12345";
$dbname = "company_info";

// Crear conexión
$conn = new mysqli($servername, $database_username, $database_password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger y validar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';
    
    // ✅ CORRECCIÓN 1: Validar que la contraseña no sea null
    if (empty($contraseña)) {
        $_SESSION['error'] = "La contraseña es obligatoria";
        header("Location: FormularioRegistro.php");
        exit();
    }
    
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
    }
    
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    }
    
    if (strlen($contraseña) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Si no hay errores, proceder con el registro
    if (empty($errores)) {
        // ✅ CORRECCIÓN 2: Hash de la contraseña (seguro)
        $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
        
        // ✅ CORRECCIÓN 3: Verificar estructura de la tabla
        // Primero, verifiquemos qué columnas tiene tu tabla
        $check_table = "SHOW COLUMNS FROM usuarios LIKE 'sexo'";
        $result = $conn->query($check_table);
        
        if ($result->num_rows > 0) {
            // La columna 'sexo' existe - usar consulta con sexo
            $sql = "INSERT INTO usuarios (nombre, apellido, usuario, correo, HashMagic, sexo) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $sexo = $_POST['sexo'] ?? '';
                $stmt->bind_param("ssssss", $nombre, $apellido, $usuario, $correo, $contraseña_hash, $sexo);
            }
        } else {
            // La columna 'sexo' NO existe - usar consulta sin sexo
            $sql = "INSERT INTO usuarios (nombre, apellido, usuario, correo, HashMagic) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("sssss", $nombre, $apellido, $usuario, $correo, $contraseña_hash);
            }
        }
        
        if ($stmt && $stmt->execute()) {
            $usuario_id_nuevo = $conn->insert_id;

            // Crear sesión para activar_2fa.php
            $_SESSION['autenticado'] = "SI";
            $_SESSION['usuario_id'] = $usuario_id_nuevo;
            $_SESSION['Usuario'] = $usuario;

            header("Location: activar_2fa.php");
            exit();
        } else {
            if ($conn->errno == 1062) {
                $error_message = $conn->error;
                if (strpos($error_message, 'usuario') !== false) {
                    $_SESSION['error'] = "El nombre de usuario ya está en uso";
                } elseif (strpos($error_message, 'correo') !== false) {
                    $_SESSION['error'] = "El correo electrónico ya está registrado";
                } else {
                    $_SESSION['error'] = "El usuario o correo electrónico ya existen";
                }
            } else {
                $_SESSION['error'] = "Error al guardar los datos: " . ($stmt ? $stmt->error : $conn->error);
            }
            header("Location: FormularioRegistro.php");
            exit();
        }
        
        if ($stmt) {
            $stmt->close();
        }
        
    } else {
        // Si hay errores, redirigir con mensajes de error
        $_SESSION['error'] = implode(", ", $errores);
        header("Location: FormularioRegistro.php");
        exit();
    }
} else {
    // Si no es POST, redirigir al formulario
    header("Location: FormularioRegistro.php");
    exit();
}

$conn->close();
?>