<?php
require 'includes/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $sexo = $_POST['sexo'];
    
    $sql = "INSERT INTO usuarios (nombre, apellido, email, HashMagic, sexo) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $apellido, $email, $password, $sexo]);
    
    echo "Usuario registrado exitosamente!";
}
?>

<form method="POST">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="text" name="apellido" placeholder="Apellido" required>
    <input type="email" name="email" placeholder="Correo electrónico" required>
    <input type="password" name="password" placeholder="Contraseña" required>
    <select name="sexo" required>
        <option value="M">Masculino</option>
        <option value="F">Femenino</option>
    </select>
    <button type="submit">Registrar</button>
</form>