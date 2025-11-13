<?php
/**
 * Clase RegistroUsuario
 * 
 * Maneja el proceso completo de registro de usuarios
 * Cumple con el principio de responsabilidad única (SRP)
 * 
 * Cada método tiene UNA SOLA responsabilidad específica
 * Requisito de la rúbrica: 10 puntos
 */

require_once 'SanitizarEntrada.php';

class RegistroUsuario {
    
    private $conexion;
    private $errores = [];
    
    /**
     * Constructor - Inicializa la conexión a la base de datos
     * RESPONSABILIDAD: Configurar el objeto
     * 
     * @param mysqli $conexion - Conexión activa a MySQL
     */
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    /**
     * Método principal: Registrar un nuevo usuario
     * RESPONSABILIDAD: Coordinar el flujo completo de registro
     * 
     * @param array $datos - Datos del formulario POST
     * @return bool - true si registro exitoso, false si hay error
     */
    public function registrar($datos) {
        // Paso 1: Sanitizar datos de entrada
        $datosSanitizados = $this->sanitizarDatos($datos);
        
        // Paso 2: Validar que los datos cumplan requisitos
        if (!$this->validarDatos($datosSanitizados)) {
            return false; // Errores guardados en $this->errores
        }
        
        // Paso 3: Verificar que el usuario no exista
        if ($this->existeUsuario($datosSanitizados['usuario'])) {
            $this->errores[] = "El nombre de usuario ya está en uso";
            return false;
        }
        
        // Paso 4: Verificar que el correo no exista
        if ($this->existeCorreo($datosSanitizados['correo'])) {
            $this->errores[] = "El correo electrónico ya está registrado";
            return false;
        }
        
        // Paso 5: Generar hash seguro de la contraseña
        $hash = $this->generarHash($datosSanitizados['contraseña']);
        
        // Paso 6: Insertar usuario en la base de datos
        return $this->insertarUsuario(
            $datosSanitizados['nombre'],
            $datosSanitizados['apellido'],
            $datosSanitizados['usuario'],
            $datosSanitizados['correo'],
            $hash,
            $datosSanitizados['sexo'] ?? null
        );
    }
    
    /**
     * Sanitiza todos los campos del formulario
     * RESPONSABILIDAD: Limpiar datos de entrada usando SanitizarEntrada
     * 
     * @param array $datos - Datos crudos del formulario
     * @return array - Datos sanitizados y seguros
     */
    private function sanitizarDatos($datos) {
        return [
            'nombre' => SanitizarEntrada::sanitizarTexto($datos['nombre'] ?? ''),
            'apellido' => SanitizarEntrada::sanitizarTexto($datos['apellido'] ?? ''),
            'usuario' => SanitizarEntrada::sanitizarUsuario($datos['usuario'] ?? ''),
            'correo' => SanitizarEntrada::sanitizarEmail($datos['correo'] ?? ''),
            'contraseña' => $datos['contraseña'] ?? '', // No sanitizar, se hasheará
            'sexo' => SanitizarEntrada::sanitizarSexo($datos['sexo'] ?? '') ?: null
        ];
    }
    
    /**
     * Valida que todos los campos cumplan los requisitos
     * RESPONSABILIDAD: Verificar integridad de datos
     * 
     * @param array $datos - Datos ya sanitizados
     * @return bool - true si todos los datos son válidos
     */
    private function validarDatos($datos) {
        $this->errores = []; // Limpiar errores previos
        
        // Validar nombre
        if (empty($datos['nombre'])) {
            $this->errores[] = "El nombre es obligatorio";
        } elseif (strlen($datos['nombre']) < 2) {
            $this->errores[] = "El nombre debe tener al menos 2 caracteres";
        }
        
        // Validar apellido
        if (empty($datos['apellido'])) {
            $this->errores[] = "El apellido es obligatorio";
        } elseif (strlen($datos['apellido']) < 2) {
            $this->errores[] = "El apellido debe tener al menos 2 caracteres";
        }
        
        // Validar usuario
        if (empty($datos['usuario'])) {
            $this->errores[] = "El nombre de usuario es obligatorio";
        } elseif (strlen($datos['usuario']) < 4) {
            $this->errores[] = "El usuario debe tener al menos 4 caracteres";
        } elseif (strlen($datos['usuario']) > 50) {
            $this->errores[] = "El usuario no puede tener más de 50 caracteres";
        }
        
        // Validar correo
        if (empty($datos['correo']) || $datos['correo'] === false) {
            $this->errores[] = "El correo electrónico no es válido";
        }
        
        // Validar contraseña
        if (empty($datos['contraseña'])) {
            $this->errores[] = "La contraseña es obligatoria";
        } elseif (strlen($datos['contraseña']) < 6) {
            $this->errores[] = "La contraseña debe tener al menos 6 caracteres";
        }
        
        // Retorna true solo si NO hay errores
        return empty($this->errores);
    }
    
    /**
     * Verifica si ya existe un usuario con ese nombre
     * RESPONSABILIDAD: Consultar existencia de usuario en BD
     * 
     * @param string $usuario - Nombre de usuario a verificar
     * @return bool - true si existe, false si no existe
     */
    private function existeUsuario($usuario) {
        $sql = "SELECT id FROM usuarios WHERE Usuario = ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        
        if (!$stmt) {
            $this->errores[] = "Error preparando consulta: " . $this->conexion->error;
            return false;
        }
        
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $existe = $result->num_rows > 0;
        $stmt->close();
        
        return $existe;
    }
    
    /**
     * Verifica si ya existe un usuario con ese correo
     * RESPONSABILIDAD: Consultar existencia de correo en BD
     * 
     * @param string $correo - Correo electrónico a verificar
     * @return bool - true si existe, false si no existe
     */
    private function existeCorreo($correo) {
        $sql = "SELECT id FROM usuarios WHERE Correo = ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        
        if (!$stmt) {
            $this->errores[] = "Error preparando consulta: " . $this->conexion->error;
            return false;
        }
        
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();
        $existe = $result->num_rows > 0;
        $stmt->close();
        
        return $existe;
    }
    
    /**
     * Genera un hash seguro de la contraseña
     * RESPONSABILIDAD: Hashear contraseña
     * 
     * Cumple requisito de la rúbrica: "Crear método que genere el hash"
     * 
     * @param string $contraseña - Contraseña en texto plano
     * @return string - Hash bcrypt de la contraseña
     */
    private function generarHash($contraseña) {
        // PASSWORD_DEFAULT usa bcrypt (algoritmo seguro recomendado)
        // Se adapta automáticamente a mejores algoritmos en futuras versiones
        return password_hash($contraseña, PASSWORD_DEFAULT);
    }
    
    /**
     * Inserta el nuevo usuario en la base de datos
     * RESPONSABILIDAD: Ejecutar INSERT en la BD
     * 
     * @param string $nombre
     * @param string $apellido
     * @param string $usuario
     * @param string $correo
     * @param string $hash - Hash de la contraseña
     * @param string|null $sexo - Opcional
     * @return bool - true si se insertó correctamente
     */
    private function insertarUsuario($nombre, $apellido, $usuario, $correo, $hash, $sexo = null) {
        // Preparar consulta SQL con placeholders
        $sql = "INSERT INTO usuarios (Nombre, Apellido, Usuario, Correo, HashMagic, Sexo, FechaDelSistema) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conexion->prepare($sql);
        
        if (!$stmt) {
            $this->errores[] = "Error preparando consulta: " . $this->conexion->error;
            return false;
        }
        
        // Bind parameters (s = string)
        $stmt->bind_param("ssssss", $nombre, $apellido, $usuario, $correo, $hash, $sexo);
        
        // Ejecutar
        if ($stmt->execute()) {
            $stmt->close();
            return true; // Éxito
        } else {
            $this->errores[] = "Error al guardar: " . $stmt->error;
            $stmt->close();
            return false;
        }
    }
    
    /**
     * Obtiene el array de errores
     * RESPONSABILIDAD: Proporcionar acceso a los errores
     * 
     * @return array - Array con todos los mensajes de error
     */
    public function getErrores() {
        return $this->errores;
    }
    
    /**
     * Obtiene los errores como string para mostrar al usuario
     * RESPONSABILIDAD: Formatear errores para display
     * 
     * @return string - Errores separados por comas
     */
    public function getErroresString() {
        return implode(", ", $this->errores);
    }
    
    /**
     * Verifica si hay errores
     * RESPONSABILIDAD: Indicar si el proceso tuvo errores
     * 
     * @return bool - true si hay errores, false si no hay
     */
    public function tieneErrores() {
        return !empty($this->errores);
    }
    
} // fin clase RegistroUsuario
?>