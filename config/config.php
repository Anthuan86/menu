<?php
/**
 * Archivo de configuración principal
 * Sistema de Administración de Menús
 */

// Configuración de la base de datos
$db_config = array(
    'db_server' => 'mysql-docker',
    'db_port' => 3306,
    'db_username' => 'preprodtrascendadmin',
    'db_password' => 'Tr45c3nd1t.2021@pr3pr0d',
    'db_name' => 'bd_vtigertrascend2023'
);

// Configuración adicional del portal
$portal_config = array(
    'base_url' => 'https://cpadm.academiadeguerra.cl',
    'portal_name' => 'Portal del Colaborador',
    'debug_mode' => true, // Cambiar a false en producción
    'session_timeout' => 3600 // Tiempo de vida de la sesión en segundos (1 hora)
);

// Configurar manejo de errores según el modo debug
if ($portal_config['debug_mode']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configurar zona horaria (ajusta según tu ubicación)
date_default_timezone_set('America/Bogota');

// Función de utilidad para logging de errores
function log_error($message, $context = '') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    if (!empty($context)) {
        $log_message .= " | Context: $context";
    }
    error_log($log_message . PHP_EOL, 3, __DIR__ . '/../logs/portal_errors.log');
}

// Función para conexión segura a la base de datos
function get_database_connection() {
    global $db_config;

    try {
        $mysqli = new mysqli(
            $db_config['db_server'],
            $db_config['db_username'],
            $db_config['db_password'],
            $db_config['db_name'],
            $db_config['db_port']
        );

        // Configurar charset para evitar problemas de codificación
        $mysqli->set_charset("utf8mb4");

        if ($mysqli->connect_error) {
            throw new Exception("Error de conexión: " . $mysqli->connect_error);
        }

        return $mysqli;
    } catch (Exception $e) {
        log_error("Error de conexión a BD: " . $e->getMessage());
        return false;
    }
}

// Función para enviar respuestas JSON
function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Función para validar sesión
function validate_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    global $portal_config;

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }

    // Verificar timeout de sesión
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $portal_config['session_timeout'])) {
        session_unset();
        session_destroy();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

// Tipos de segmentos de usuarios
define('USER_TYPE_COLABORADOR', 'colaborador');
define('USER_TYPE_CLIENTE', 'cliente');
define('USER_TYPE_PARTNER', 'partner');

?>
