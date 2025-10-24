<?php
/**
 * API de Usuarios
 * Gestiona operaciones CRUD de usuarios de los tres segmentos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/config.php';
require_once '../includes/classes/Users.php';

// Obtener conexión a la base de datos
$db = get_database_connection();

if (!$db) {
    send_json_response([
        'success' => false,
        'message' => 'Error de conexión a la base de datos'
    ], 500);
}

// Crear instancia de Users
$users = new Users($db);

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtener parámetros
$segment = $_GET['segment'] ?? null;
$user_id = $_GET['user_id'] ?? null;
$user_type = $_GET['user_type'] ?? null;
$search = $_GET['search'] ?? null;
$action = $_GET['action'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($action === 'counts') {
                // Obtener conteos de usuarios por segmento
                $counts = $users->getUserCounts();
                send_json_response([
                    'success' => true,
                    'data' => $counts
                ]);

            } elseif ($search) {
                // Buscar usuarios
                $results = $users->searchUsers($search, $segment);
                send_json_response([
                    'success' => true,
                    'data' => $results
                ]);

            } elseif ($user_id && $user_type) {
                // Obtener usuario específico
                $user = $users->getUserById($user_id, $user_type);
                if ($user) {
                    send_json_response([
                        'success' => true,
                        'data' => $user
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Usuario no encontrado'
                    ], 404);
                }

            } elseif ($segment) {
                // Obtener usuarios por segmento
                $userList = $users->getUsersBySegment($segment);
                send_json_response([
                    'success' => true,
                    'data' => $userList
                ]);

            } else {
                // Obtener todos los usuarios de todos los segmentos
                $colaboradores = $users->getColaboradores();
                $clientes = $users->getClientes();
                $partners = $users->getPartners();

                send_json_response([
                    'success' => true,
                    'data' => [
                        'colaboradores' => $colaboradores,
                        'clientes' => $clientes,
                        'partners' => $partners
                    ]
                ]);
            }
            break;

        default:
            send_json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
    }

} catch (Exception $e) {
    log_error("Error en API de usuarios: " . $e->getMessage());
    send_json_response([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ], 500);
}

// Cerrar conexión
$db->close();
?>
