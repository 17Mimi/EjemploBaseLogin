<?php
/**
 * ProcesarRegistro.php - VERSIÓN REFACTORIZADA CON POO
 * 
 * Este archivo ahora usa la clase RegistroUsuario que implementa
 * el principio de responsabilidad única (Single Responsibility Principle)
 * 
 * Cumple requisito de la rúbrica:
 * "Clase para el Formulario Registro de Datos. Los métodos deben tener 
 *  un mínimo de responsabilidad." (10 puntos)
 */

// Incluir las clases necesarias
require_once 'clases/RegistroUsuario.php';
require_once 'clases/SanitizarEntrada.php';

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================

$servername = "localhost";

// ⚠️ IMPORTANTE: Actualizar con tu usuario de privilegios mínimos
// Después de crear el usuario MySQL, cambia estos valores:
$username = "lab_2fa_user";      
$password = "Lab2FA#Secure2025";  
$dbname = "company_info";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// ============================================
// PROCESAR FORMULARIO USANDO POO
// ============================================

// Verificar que sea una petición POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Crear instancia de la clase RegistroUsuario
    // Esta clase maneja TODA la lógica de registro
    $registro = new RegistroUsuario($conn);
    
    // Intentar registrar el usuario
    // La clase se encarga automáticamente de:
    // 1. Sanitizar todos los datos (usando SanitizarEntrada)
    // 2. Validar todos los campos
    // 3. Verificar duplicados (usuario y correo)
    // 4. Generar hash de contraseña
    // 5. Insertar en la base de datos
    if ($registro->registrar($_POST)) {
        
        // ✅ REGISTRO EXITOSO
        // Redirigir al formulario con mensaje de éxito
        header("Location: FormularioRegistro.php?success=1");
        exit();
        
    } else {
        
        // ❌ ERROR EN EL REGISTRO
        // Obtener todos los errores de la clase
        $errores = $registro->getErroresString();
        
        // Redirigir al formulario mostrando los errores
        header("Location: FormularioRegistro.php?error=" . urlencode($errores));
        exit();
    }
    
} else {
    // Si no es POST, redirigir al formulario
    header("Location: FormularioRegistro.php");
    exit();
}

// Cerrar conexión
$conn->close();

?>