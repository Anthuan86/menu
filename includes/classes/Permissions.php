<?php
/**
 * Clase Permissions
 * Gestiona los permisos de acceso a módulos para usuarios
 */

class Permissions {
    private $db;

    public function __construct($database_connection) {
        $this->db = $database_connection;
    }

    /**
     * Obtiene el menú de un usuario con sus permisos
     * @param string $user_id ID del usuario
     * @param string $user_type Tipo de usuario
     * @return array Menú con permisos del usuario
     */
    public function getUserMenu($user_id, $user_type) {
        $user_id = $this->db->real_escape_string($user_id);
        $user_type = $this->db->real_escape_string($user_type);

        $sql = "SELECT
                    m.id,
                    m.name,
                    m.description,
                    m.icon,
                    m.url,
                    m.parent_id,
                    m.order_position,
                    COALESCE(up.can_access, 1) as has_permission
                FROM menu_modules m
                LEFT JOIN menu_user_permissions up ON (
                    m.id = up.module_id
                    AND up.user_id = '$user_id'
                    AND up.user_type = '$user_type'
                )
                WHERE m.segment = '$user_type'
                    AND m.is_active = 1
                    AND (up.can_access = 1 OR up.can_access IS NULL)
                ORDER BY m.parent_id, m.order_position";

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al obtener menú de usuario: " . $this->db->error);
            return [];
        }

        $modules = [];
        while ($row = $result->fetch_assoc()) {
            $modules[] = $row;
        }

        return $this->buildMenuTree($modules);
    }

    /**
     * Construye árbol jerárquico del menú
     * @param array $modules Lista de módulos
     * @param int $parent_id ID del padre
     * @return array Árbol de menú
     */
    private function buildMenuTree($modules, $parent_id = null) {
        $tree = [];

        foreach ($modules as $module) {
            if ($module['parent_id'] == $parent_id) {
                $children = $this->buildMenuTree($modules, $module['id']);
                if ($children) {
                    $module['children'] = $children;
                }
                $tree[] = $module;
            }
        }

        return $tree;
    }

    /**
     * Obtiene los permisos de un usuario específico
     * @param string $user_id ID del usuario
     * @param string $user_type Tipo de usuario
     * @return array Lista de permisos
     */
    public function getUserPermissions($user_id, $user_type) {
        $user_id = $this->db->real_escape_string($user_id);
        $user_type = $this->db->real_escape_string($user_type);

        $sql = "SELECT
                    up.id,
                    up.module_id,
                    up.can_access,
                    m.name as module_name,
                    m.segment,
                    m.is_active as module_active
                FROM menu_user_permissions up
                INNER JOIN menu_modules m ON up.module_id = m.id
                WHERE up.user_id = '$user_id' AND up.user_type = '$user_type'
                ORDER BY m.segment, m.order_position";

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al obtener permisos de usuario: " . $this->db->error);
            return [];
        }

        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row;
        }

        return $permissions;
    }

    /**
     * Asigna o actualiza un permiso para un usuario
     * @param string $user_id ID del usuario
     * @param int $module_id ID del módulo
     * @param string $user_type Tipo de usuario
     * @param bool $can_access Puede acceder (true/false)
     * @return bool True si se asignó correctamente
     */
    public function setPermission($user_id, $module_id, $user_type, $can_access) {
        $user_id = $this->db->real_escape_string($user_id);
        $module_id = (int)$module_id;
        $user_type = $this->db->real_escape_string($user_type);
        $can_access = $can_access ? 1 : 0;

        $sql = "INSERT INTO menu_user_permissions
                (user_id, module_id, user_type, can_access)
                VALUES
                ('$user_id', $module_id, '$user_type', $can_access)
                ON DUPLICATE KEY UPDATE
                    can_access = $can_access,
                    updated_at = CURRENT_TIMESTAMP";

        if ($this->db->query($sql)) {
            return true;
        } else {
            log_error("Error al asignar permiso: " . $this->db->error, $sql);
            return false;
        }
    }

    /**
     * Asigna múltiples permisos a un usuario
     * @param string $user_id ID del usuario
     * @param string $user_type Tipo de usuario
     * @param array $permissions Array de [module_id => can_access]
     * @return bool True si se asignaron correctamente
     */
    public function setUserPermissions($user_id, $user_type, $permissions) {
        $this->db->begin_transaction();

        try {
            foreach ($permissions as $module_id => $can_access) {
                if (!$this->setPermission($user_id, $module_id, $user_type, $can_access)) {
                    throw new Exception("Error al asignar permiso para módulo $module_id");
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            log_error("Error al asignar permisos múltiples: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un permiso específico
     * @param int $permission_id ID del permiso
     * @return bool True si se eliminó correctamente
     */
    public function deletePermission($permission_id) {
        $permission_id = (int)$permission_id;

        $sql = "DELETE FROM menu_user_permissions WHERE id = $permission_id";

        if ($this->db->query($sql)) {
            return true;
        } else {
            log_error("Error al eliminar permiso: " . $this->db->error);
            return false;
        }
    }

    /**
     * Elimina todos los permisos de un usuario
     * @param string $user_id ID del usuario
     * @param string $user_type Tipo de usuario
     * @return bool True si se eliminaron correctamente
     */
    public function deleteUserPermissions($user_id, $user_type) {
        $user_id = $this->db->real_escape_string($user_id);
        $user_type = $this->db->real_escape_string($user_type);

        $sql = "DELETE FROM menu_user_permissions
                WHERE user_id = '$user_id' AND user_type = '$user_type'";

        if ($this->db->query($sql)) {
            return true;
        } else {
            log_error("Error al eliminar permisos de usuario: " . $this->db->error);
            return false;
        }
    }

    /**
     * Verifica si un usuario tiene permiso para acceder a un módulo
     * @param string $user_id ID del usuario
     * @param int $module_id ID del módulo
     * @param string $user_type Tipo de usuario
     * @return bool True si tiene permiso
     */
    public function hasPermission($user_id, $module_id, $user_type) {
        $user_id = $this->db->real_escape_string($user_id);
        $module_id = (int)$module_id;
        $user_type = $this->db->real_escape_string($user_type);

        $sql = "SELECT can_access
                FROM menu_user_permissions
                WHERE user_id = '$user_id'
                    AND module_id = $module_id
                    AND user_type = '$user_type'";

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al verificar permiso: " . $this->db->error);
            return true; // Por defecto, dar acceso si no hay restricción explícita
        }

        if ($result->num_rows === 0) {
            return true; // Si no hay registro, se asume que tiene permiso
        }

        $row = $result->fetch_assoc();
        return (bool)$row['can_access'];
    }

    /**
     * Obtiene todos los módulos con el estado de permisos para un usuario
     * @param string $user_id ID del usuario
     * @param string $user_type Tipo de usuario
     * @return array Lista de módulos con permisos
     */
    public function getModulesWithPermissions($user_id, $user_type) {
        $user_id = $this->db->real_escape_string($user_id);
        $user_type = $this->db->real_escape_string($user_type);

        $sql = "SELECT
                    m.id,
                    m.name,
                    m.description,
                    m.icon,
                    m.url,
                    m.parent_id,
                    m.segment,
                    m.order_position,
                    m.is_active,
                    COALESCE(up.can_access, 1) as has_permission,
                    up.id as permission_id
                FROM menu_modules m
                LEFT JOIN menu_user_permissions up ON (
                    m.id = up.module_id
                    AND up.user_id = '$user_id'
                    AND up.user_type = '$user_type'
                )
                WHERE m.segment = '$user_type'
                ORDER BY m.parent_id, m.order_position";

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al obtener módulos con permisos: " . $this->db->error);
            return [];
        }

        $modules = [];
        while ($row = $result->fetch_assoc()) {
            $modules[] = $row;
        }

        return $modules;
    }

    /**
     * Registra un acceso a un módulo
     * @param string $user_id ID del usuario
     * @param string $user_type Tipo de usuario
     * @param int $module_id ID del módulo
     * @param string $ip_address Dirección IP
     * @param string $user_agent User agent del navegador
     * @return bool True si se registró correctamente
     */
    public function logAccess($user_id, $user_type, $module_id, $ip_address = null, $user_agent = null) {
        $user_id = $this->db->real_escape_string($user_id);
        $user_type = $this->db->real_escape_string($user_type);
        $module_id = (int)$module_id;
        $ip_address = $ip_address ? $this->db->real_escape_string($ip_address) : '';
        $user_agent = $user_agent ? $this->db->real_escape_string($user_agent) : '';

        $sql = "INSERT INTO menu_access_log
                (user_id, user_type, module_id, ip_address, user_agent)
                VALUES
                ('$user_id', '$user_type', $module_id, '$ip_address', '$user_agent')";

        if ($this->db->query($sql)) {
            return true;
        } else {
            log_error("Error al registrar acceso: " . $this->db->error);
            return false;
        }
    }

    /**
     * Obtiene estadísticas de acceso
     * @param array $filters Filtros opcionales
     * @return array Estadísticas
     */
    public function getAccessStats($filters = []) {
        $where = [];

        if (isset($filters['user_id'])) {
            $user_id = $this->db->real_escape_string($filters['user_id']);
            $where[] = "user_id = '$user_id'";
        }

        if (isset($filters['user_type'])) {
            $user_type = $this->db->real_escape_string($filters['user_type']);
            $where[] = "user_type = '$user_type'";
        }

        if (isset($filters['module_id'])) {
            $module_id = (int)$filters['module_id'];
            $where[] = "module_id = $module_id";
        }

        if (isset($filters['date_from'])) {
            $date_from = $this->db->real_escape_string($filters['date_from']);
            $where[] = "access_time >= '$date_from'";
        }

        if (isset($filters['date_to'])) {
            $date_to = $this->db->real_escape_string($filters['date_to']);
            $where[] = "access_time <= '$date_to'";
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT
                    COUNT(*) as total_accesses,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT module_id) as modules_accessed
                FROM menu_access_log
                $where_clause";

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al obtener estadísticas de acceso: " . $this->db->error);
            return [];
        }

        return $result->fetch_assoc();
    }

    /**
     * Copia permisos de un usuario a otro
     * @param string $source_user_id ID del usuario origen
     * @param string $target_user_id ID del usuario destino
     * @param string $user_type Tipo de usuario
     * @return bool True si se copiaron correctamente
     */
    public function copyPermissions($source_user_id, $target_user_id, $user_type) {
        $source_permissions = $this->getUserPermissions($source_user_id, $user_type);

        $this->db->begin_transaction();

        try {
            // Primero eliminar permisos existentes del usuario destino
            $this->deleteUserPermissions($target_user_id, $user_type);

            // Copiar cada permiso
            foreach ($source_permissions as $permission) {
                $this->setPermission(
                    $target_user_id,
                    $permission['module_id'],
                    $user_type,
                    $permission['can_access']
                );
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            log_error("Error al copiar permisos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resetea permisos de un usuario (dar acceso a todos los módulos)
     * @param string $user_id ID del usuario
     * @param string $user_type Tipo de usuario
     * @return bool True si se resetearon correctamente
     */
    public function resetPermissions($user_id, $user_type) {
        return $this->deleteUserPermissions($user_id, $user_type);
    }
}

?>
