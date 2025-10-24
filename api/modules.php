<?php
/**
 * API de Módulos
 * Gestiona operaciones CRUD de módulos del menú
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
require_once '../includes/classes/Modules.php';

// Obtener conexión a la base de datos
$db = get_database_connection();

if (!$db) {
    send_json_response([
        'success' => false,
        'message' => 'Error de conexión a la base de datos'
    ], 500);
}

// Crear instancia de Modules
$modules = new Modules($db);

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtener parámetros
$segment = $_GET['segment'] ?? null;
$module_id = $_GET['module_id'] ?? null;
$only_active = isset($_GET['only_active']) ? (bool)$_GET['only_active'] : false;
$action = $_GET['action'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($action === 'stats') {
                // Obtener estadísticas de módulos
                $stats = $modules->getModuleStats();
                send_json_response([
                    'success' => true,
                    'data' => $stats
                ]);

            } elseif ($action === 'hierarchy' && $segment) {
                // Obtener módulos jerárquicos por segmento
                $hierarchy = $modules->getModulesHierarchy($segment, $only_active);
                send_json_response([
                    'success' => true,
                    'data' => $hierarchy
                ]);

            } elseif ($module_id) {
                // Obtener módulo específico
                $module = $modules->getModuleById($module_id);
                if ($module) {
                    send_json_response([
                        'success' => true,
                        'data' => $module
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Módulo no encontrado'
                    ], 404);
                }

            } else {
                // Obtener todos los módulos
                $moduleList = $modules->getModules($segment, $only_active);
                send_json_response([
                    'success' => true,
                    'data' => $moduleList
                ]);
            }
            break;

        case 'POST':
            // Crear nuevo módulo
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                send_json_response([
                    'success' => false,
                    'message' => 'Datos inválidos'
                ], 400);
            }

            // Validar campos requeridos
            if (empty($input['name']) || empty($input['url']) || empty($input['segment'])) {
                send_json_response([
                    'success' => false,
                    'message' => 'Faltan campos requeridos: name, url, segment'
                ], 400);
            }

            if ($action === 'duplicate' && $module_id) {
                // Duplicar módulo
                $new_id = $modules->duplicateModule($module_id);
                if ($new_id) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Módulo duplicado correctamente',
                        'data' => ['id' => $new_id]
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al duplicar módulo'
                    ], 500);
                }

            } else {
                // Crear módulo
                $new_id = $modules->createModule($input);

                if ($new_id) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Módulo creado correctamente',
                        'data' => ['id' => $new_id]
                    ], 201);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al crear módulo'
                    ], 500);
                }
            }
            break;

        case 'PUT':
            // Actualizar módulo
            if (!$module_id) {
                send_json_response([
                    'success' => false,
                    'message' => 'ID de módulo requerido'
                ], 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                send_json_response([
                    'success' => false,
                    'message' => 'Datos inválidos'
                ], 400);
            }

            if ($action === 'toggle') {
                // Cambiar estado activo/inactivo
                $is_active = isset($input['is_active']) ? (bool)$input['is_active'] : true;
                $result = $modules->toggleModuleStatus($module_id, $is_active);

                if ($result) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Estado del módulo actualizado correctamente'
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al actualizar estado'
                    ], 500);
                }

            } elseif ($action === 'reorder') {
                // Reordenar módulos
                if (!isset($input['order']) || !is_array($input['order'])) {
                    send_json_response([
                        'success' => false,
                        'message' => 'Orden inválido'
                    ], 400);
                }

                $result = $modules->reorderModules($input['order']);

                if ($result) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Módulos reordenados correctamente'
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al reordenar módulos'
                    ], 500);
                }

            } else {
                // Actualizar módulo
                $result = $modules->updateModule($module_id, $input);

                if ($result) {
                    send_json_response([
                        'success' => true,
                        'message' => 'Módulo actualizado correctamente'
                    ]);
                } else {
                    send_json_response([
                        'success' => false,
                        'message' => 'Error al actualizar módulo'
                    ], 500);
                }
            }
            break;

        case 'DELETE':
            // Eliminar módulo
            if (!$module_id) {
                send_json_response([
                    'success' => false,
                    'message' => 'ID de módulo requerido'
                ], 400);
            }

            $result = $modules->deleteModule($module_id);

            if ($result) {
                send_json_response([
                    'success' => true,
                    'message' => 'Módulo eliminado correctamente'
                ]);
            } else {
                send_json_response([
                    'success' => false,
                    'message' => 'Error al eliminar módulo'
                ], 500);
            }
            break;

        default:
            send_json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
    }

} catch (Exception $e) {
    log_error("Error en API de módulos: " . $e->getMessage());
    send_json_response([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ], 500);
}

// Cerrar conexión
$db->close();
?>
