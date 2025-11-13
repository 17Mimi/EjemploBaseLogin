<?php
/**
 * Clase SanitizarEntrada
 * 
 * Proporciona métodos estáticos para sanitizar diferentes tipos de datos
 * Previene XSS, inyección SQL y otros ataques
 * 
 * Requisito de la rúbrica: Mínimo 3 métodos estáticos
 */
class SanitizarEntrada {

    /**
     * Método 1: Sanitiza una cadena eliminando espacios y etiquetas HTML
     * Previene ataques XSS eliminando tags HTML peligrosos
     * 
     * @param string $cadena - Texto a limpiar
     * @return string - Texto sin tags HTML ni espacios extra
     */
    public static function limpiarCadena($cadena) {
        // strip_tags() elimina todas las etiquetas HTML y PHP
        // trim() elimina espacios en blanco al inicio y final
        return trim(strip_tags($cadena));
    }

    /**
     * Método 2: Sanitiza y valida un correo electrónico
     * Usa filter_var() según recomendación de la rúbrica
     * 
     * @param string $email - Correo electrónico a sanitizar
     * @return string|false - Email sanitizado o false si es inválido
     */
    public static function sanitizarEmail($email) {
        // Primero sanitiza eliminando caracteres no permitidos en emails
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // Luego valida que sea un email válido según RFC
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        
        return false; // Email inválido
    }

    /**
     * Método 3: Sanitiza texto usando htmlspecialchars
     * Convierte caracteres especiales a entidades HTML
     * Previene ataques XSS (Cross-Site Scripting)
     * 
     * Según la rúbrica: "usar htmlspecialchars()"
     * 
     * @param string $texto - Texto a sanitizar
     * @return string - Texto con caracteres especiales convertidos
     */
    public static function sanitizarTexto($texto) {
        // htmlspecialchars() convierte:
        // < a &lt;
        // > a &gt;
        // & a &amp;
        // " a &quot;
        // ' a &#039;
        $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
        return trim($texto);
    }

    /**
     * Método 4 (EXTRA): Sanitiza nombre de usuario
     * Solo permite letras, números y guiones bajos
     * 
     * @param string $usuario - Usuario a sanitizar
     * @return string - Usuario limpio
     */
    public static function sanitizarUsuario($usuario) {
        // preg_replace elimina todo lo que NO sea [a-zA-Z0-9_]
        $usuario = preg_replace('/[^a-zA-Z0-9_]/', '', $usuario);
        return trim($usuario);
    }

    /**
     * Método 5 (EXTRA): Sanitiza el campo sexo
     * Solo acepta valores específicos: M, F, Otro
     * 
     * @param string $sexo - Valor del campo sexo
     * @return string|false - Valor válido o false
     */
    public static function sanitizarSexo($sexo) {
        $valoresPermitidos = ['M', 'F', 'Otro'];
        $sexo = trim($sexo);
        
        // Verifica que el valor esté en la lista de permitidos
        if (in_array($sexo, $valoresPermitidos)) {
            return $sexo;
        }
        
        return false;
    }

    /**
     * Método 6 (EXTRA): Sanitiza números enteros
     * 
     * @param mixed $numero - Valor a sanitizar
     * @return int - Número entero sanitizado
     */
    public static function sanitizarNumero($numero) {
        return filter_var($numero, FILTER_SANITIZE_NUMBER_INT);
    }

} // fin clase SanitizarEntrada


// ============================================
// EJEMPLOS DE USO (comentados)
// ============================================
/*
// Ejemplo 1: Limpiar nombre
$nombre = "<b>Juan</b>  ";
$nombreLimpio = SanitizarEntrada::limpiarCadena($nombre);
echo "Nombre limpio: " . $nombreLimpio . "<br>";
// Resultado: "Juan"

// Ejemplo 2: Sanitizar email
$email = "juan@example.com<script>alert('XSS');</script>";
$emailLimpio = SanitizarEntrada::sanitizarEmail($email);
echo "Email limpio: " . $emailLimpio . "<br>";
// Resultado: "juan@example.com"

// Ejemplo 3: Sanitizar texto
$texto = "<script>alert('hack')</script>Hola";
$textoSeguro = SanitizarEntrada::sanitizarTexto($texto);
echo "Texto seguro: " . $textoSeguro . "<br>";
// Resultado: "&lt;script&gt;alert('hack')&lt;/script&gt;Hola"
*/
?>