<?php
/**
 * API de Permisos
 * Gestiona operaciones de permisos de usuarios a módulos
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
require_once '../includes/classes/Permissions.php';

// Obtener conexión a la base de datos
$db = get_database_connection();

if (!$db) {
    send_json_response([
        'success' => false,
        'message' => 'Error de conexión a la base de datos'
    ], 500);
}

// Crear instancia de Permissions
$permissions = new Permissions($db);

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtener parámetros
$user_id = $_GET['user_id'] ?? null;
$user_type = $_GET['user_type'] ?? null;
$module_id = $_GET['module_id'] ?? null;
$permission_id = $_GET['permission_id'] ?? null;
$action = $_GET['action'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($action === 'menu' && $user_id && $user_type) {
                // Obtener menú completo del usuario
                $menu = $permissions->getUserMenu($user_id, $user_type);
                send_json_response([
                    'success' => true,
                    'data' => $menu
                ]);

            } elseif ($action === 'modules' && $user_id && $user_type) {
                // Obtener todos los módulos con estado de permisos para el usuario
                $modules = $permissions->getModulesWithPermissions($user_id, $user_type);
                send_json_response([
                    'success' => true,
                    'data' => $modules
                ]);

            } elseif ($action === 'check' && $user_id && $module_id && $user_type) {
                // Verificar si tiene permiso a un módulo específico
                $has_permission = $permissions->hasPermission($user_id, $module_id, $user_type);
                send_json_response([
                    'success' => true,
                    'data' => ['has_permission' => $has_permission]
                ]);

            } elseif ($action === 'stats') {
                // Obtener estadísticas de acceso
                $filters = [];
                if ($user_id) $filters['user_id'] = $user_id;
                if ($user_type) $filters['user_type'] = $user_type;
                if ($module_id) $filters['module_id'] = $module_id;
                if (isset($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
                if (isset($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];

                $stats = $permissions->getAccessStats($filters);
                send_json_response([
                    'success' => true,
                    'data' => $stats
                ]);

            } elseif ($user_id && $user_type) {
                // Obtener permisos del usuario
                $userPermissions = $permissions->getUserPermissions($user_id, $user_type);
                send_json_response([
                    'success' => true,
                    'data' => $userPermissions
                ]);

            } else {
                send_json_response([
                    'success' => false,
                    'message' => 'Parámetros insuficientes'
                ], 400);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                send_json_response([
                    'success' => false,
                    'message' => 'Datos inválidos'
                ], 400);
            }

            if ($action === 'bulk') {
                // Asignar múltiples permisos
                if (empty($input['user_id']) || empty($input['user_type']) || empty($input['permissions'])) {
                    send_json_response([
                        'success' => false,
                        'message' => 'Faltan campos requeridos: user_id, user_type, permissions'
                    ], 400);
                }

                $result = $permissions->setUserPermissions(
                    $input['user_id'],
                    $input['user_type'],
                    $input['permissions']
                );

                if ($result) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Permisos asignados correctamente'
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al asignar permisos'
                    ], 500);
                }

            } elseif ($action === 'copy') {
                // Copiar permisos de un usuario a otro
                if (empty($input['source_user_id']) || empty($input['target_user_id']) || empty($input['user_type'])) {
                    send_json_response([
                        'success' => false,
                        'message' => 'Faltan campos requeridos: source_user_id, target_user_id, user_type'
                    ], 400);
                }

                $result = $permissions->copyPermissions(
                    $input['source_user_id'],
                    $input['target_user_id'],
                    $input['user_type']
                );

                if ($result) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Permisos copiados correctamente'
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al copiar permisos'
                    ], 500);
                }

            } elseif ($action === 'log') {
                // Registrar acceso a módulo
                if (empty($input['user_id']) || empty($input['user_type']) || empty($input['module_id'])) {
                    send_json_response([
                        'success' => false,
                        'message' => 'Faltan campos requeridos: user_id, user_type, module_id'
                    ], 400);
                }

                $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

                $result = $permissions->logAccess(
                    $input['user_id'],
                    $input['user_type'],
                    $input['module_id'],
                    $ip_address,
                    $user_agent
                );

                if ($result) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Acceso registrado correctamente'
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al registrar acceso'
                    ], 500);
                }

            } else {
                // Asignar permiso individual
                if (empty($input['user_id']) || empty($input['module_id']) || empty($input['user_type'])) {
                    send_json_response([
                        'success' => false,
                        'message' => 'Faltan campos requeridos: user_id, module_id, user_type'
                    ], 400);
                }

                $can_access = isset($input['can_access']) ? (bool)$input['can_access'] : true;

                $result = $permissions->setPermission(
                    $input['user_id'],
                    $input['module_id'],
                    $input['user_type'],
                    $can_access
                );

                if ($result) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Permiso asignado correctamente'
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al asignar permiso'
                    ], 500);
                }
            }
            break;

        case 'DELETE':
            if ($action === 'reset' && $user_id && $user_type) {
                // Resetear todos los permisos de un usuario
                $result = $permissions->resetPermissions($user_id, $user_type);

                if ($result) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Permisos reseteados correctamente'
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al resetear permisos'
                    ], 500);
                }

            } elseif ($user_id && $user_type) {
                // Eliminar todos los permisos de un usuario
                $result = $permissions->deleteUserPermissions($user_id, $user_type);

                if ($result) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Permisos eliminados correctamente'
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al eliminar permisos'
                    ], 500);
                }

            } elseif ($permission_id) {
                // Eliminar un permiso específico
                $result = $permissions->deletePermission($permission_id);

                if ($result) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Permiso eliminado correctamente'
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al eliminar permiso'
                    ], 500);
                }

            } else {
                send_json_response([
                    'success' => false,
                    'message' => 'Parámetros insuficientes'
                ], 400);
            }
            break;

        default:
            send_json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
    }

} catch (Exception $e) {
    log_error("Error en API de permisos: " . $e->getMessage());
    send_json_response([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ], 500);
}

// Cerrar conexión
$db->close();
?>
