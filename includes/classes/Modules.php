<?php
/**
 * Clase Modules
 * Gestiona los módulos y menús del sistema
 */

class Modules {
    private $db;

    public function __construct($database_connection) {
        $this->db = $database_connection;
    }

    /**
     * Obtiene todos los módulos
     * @param string $segment Filtrar por segmento (opcional)
     * @param bool $only_active Solo módulos activos
     * @return array Lista de módulos
     */
    public function getModules($segment = null, $only_active = false) {
        $sql = "SELECT * FROM menu_modules WHERE 1=1";

        if ($segment) {
            $segment = $this->db->real_escape_string($segment);
            $sql .= " AND segment = '$segment'";
        }

        if ($only_active) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY segment, parent_id, order_position";

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al obtener módulos: " . $this->db->error);
            return [];
        }

        $modules = [];
        while ($row = $result->fetch_assoc()) {
            $modules[] = $row;
        }

        return $modules;
    }

    /**
     * Obtiene módulos organizados jerárquicamente
     * @param string $segment Segmento específico
     * @param bool $only_active Solo módulos activos
     * @return array Árbol de módulos
     */
    public function getModulesHierarchy($segment, $only_active = true) {
        $modules = $this->getModules($segment, $only_active);
        return $this->buildTree($modules);
    }

    /**
     * Construye un árbol jerárquico de módulos
     * @param array $modules Lista plana de módulos
     * @param int $parent_id ID del padre
     * @return array Árbol de módulos
     */
    private function buildTree($modules, $parent_id = null) {
        $tree = [];

        foreach ($modules as $module) {
            if ($module['parent_id'] == $parent_id) {
                $children = $this->buildTree($modules, $module['id']);
                if ($children) {
                    $module['children'] = $children;
                }
                $tree[] = $module;
            }
        }

        return $tree;
    }

    /**
     * Obtiene un módulo por ID
     * @param int $module_id ID del módulo
     * @return array|null Datos del módulo o null
     */
    public function getModuleById($module_id) {
        $module_id = (int)$module_id;
        $sql = "SELECT * FROM menu_modules WHERE id = $module_id";

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al obtener módulo: " . $this->db->error);
            return null;
        }

        return $result->fetch_assoc();
    }

    /**
     * Crea un nuevo módulo
     * @param array $data Datos del módulo
     * @return int|bool ID del módulo creado o false en caso de error
     */
    public function createModule($data) {
        $name = $this->db->real_escape_string($data['name']);
        $description = $this->db->real_escape_string($data['description'] ?? '');
        $icon = $this->db->real_escape_string($data['icon'] ?? '');
        $url = $this->db->real_escape_string($data['url']);
        $parent_id = isset($data['parent_id']) && $data['parent_id'] ? (int)$data['parent_id'] : 'NULL';
        $segment = $this->db->real_escape_string($data['segment']);
        $order_position = (int)($data['order_position'] ?? 0);
        $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        $sql = "INSERT INTO menu_modules
                (name, description, icon, url, parent_id, segment, order_position, is_active)
                VALUES
                ('$name', '$description', '$icon', '$url', $parent_id, '$segment', $order_position, $is_active)";

        if ($this->db->query($sql)) {
            return $this->db->insert_id;
        } else {
            log_error("Error al crear módulo: " . $this->db->error, $sql);
            return false;
        }
    }

    /**
     * Actualiza un módulo existente
     * @param int $module_id ID del módulo
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function updateModule($module_id, $data) {
        $module_id = (int)$module_id;
        $updates = [];

        if (isset($data['name'])) {
            $name = $this->db->real_escape_string($data['name']);
            $updates[] = "name = '$name'";
        }

        if (isset($data['description'])) {
            $description = $this->db->real_escape_string($data['description']);
            $updates[] = "description = '$description'";
        }

        if (isset($data['icon'])) {
            $icon = $this->db->real_escape_string($data['icon']);
            $updates[] = "icon = '$icon'";
        }

        if (isset($data['url'])) {
            $url = $this->db->real_escape_string($data['url']);
            $updates[] = "url = '$url'";
        }

        if (isset($data['parent_id'])) {
            $parent_id = $data['parent_id'] ? (int)$data['parent_id'] : 'NULL';
            $updates[] = "parent_id = $parent_id";
        }

        if (isset($data['segment'])) {
            $segment = $this->db->real_escape_string($data['segment']);
            $updates[] = "segment = '$segment'";
        }

        if (isset($data['order_position'])) {
            $order_position = (int)$data['order_position'];
            $updates[] = "order_position = $order_position";
        }

        if (isset($data['is_active'])) {
            $is_active = (int)$data['is_active'];
            $updates[] = "is_active = $is_active";
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE menu_modules SET " . implode(', ', $updates) . " WHERE id = $module_id";

        if ($this->db->query($sql)) {
            return true;
        } else {
            log_error("Error al actualizar módulo: " . $this->db->error, $sql);
            return false;
        }
    }

    /**
     * Elimina un módulo
     * @param int $module_id ID del módulo
     * @return bool True si se eliminó correctamente
     */
    public function deleteModule($module_id) {
        $module_id = (int)$module_id;
        $sql = "DELETE FROM menu_modules WHERE id = $module_id";

        if ($this->db->query($sql)) {
            return true;
        } else {
            log_error("Error al eliminar módulo: " . $this->db->error);
            return false;
        }
    }

    /**
     * Activa o desactiva un módulo
     * @param int $module_id ID del módulo
     * @param bool $active Estado activo (true) o inactivo (false)
     * @return bool True si se actualizó correctamente
     */
    public function toggleModuleStatus($module_id, $active) {
        $module_id = (int)$module_id;
        $is_active = $active ? 1 : 0;

        $sql = "UPDATE menu_modules SET is_active = $is_active WHERE id = $module_id";

        if ($this->db->query($sql)) {
            return true;
        } else {
            log_error("Error al cambiar estado del módulo: " . $this->db->error);
            return false;
        }
    }

    /**
     * Reordena los módulos
     * @param array $order Array con [id => position]
     * @return bool True si se reordenó correctamente
     */
    public function reorderModules($order) {
        $this->db->begin_transaction();

        try {
            foreach ($order as $id => $position) {
                $id = (int)$id;
                $position = (int)$position;

                $sql = "UPDATE menu_modules SET order_position = $position WHERE id = $id";

                if (!$this->db->query($sql)) {
                    throw new Exception("Error al reordenar módulo $id: " . $this->db->error);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            log_error("Error al reordenar módulos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene módulos por segmento organizados para menú
     * @param string $segment Segmento
     * @return array Módulos organizados
     */
    public function getMenuBySegment($segment) {
        $segment = $this->db->real_escape_string($segment);

        $sql = "SELECT
                    id,
                    name,
                    description,
                    icon,
                    url,
                    parent_id,
                    order_position
                FROM menu_modules
                WHERE segment = '$segment' AND is_active = 1
                ORDER BY parent_id, order_position";

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al obtener menú: " . $this->db->error);
            return [];
        }

        $modules = [];
        while ($row = $result->fetch_assoc()) {
            $modules[] = $row;
        }

        return $this->buildTree($modules);
    }

    /**
     * Obtiene estadísticas de módulos
     * @return array Estadísticas
     */
    public function getModuleStats() {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'by_segment' => []
        ];

        // Total de módulos
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
                FROM menu_modules";

        $result = $this->db->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total'] = (int)$row['total'];
            $stats['active'] = (int)$row['active'];
            $stats['inactive'] = (int)$row['inactive'];
        }

        // Por segmento
        $sql = "SELECT
                    segment,
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
                FROM menu_modules
                GROUP BY segment";

        $result = $this->db->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['by_segment'][$row['segment']] = [
                    'total' => (int)$row['total'],
                    'active' => (int)$row['active']
                ];
            }
        }

        return $stats;
    }

    /**
     * Duplica un módulo
     * @param int $module_id ID del módulo a duplicar
     * @return int|bool ID del nuevo módulo o false
     */
    public function duplicateModule($module_id) {
        $module = $this->getModuleById($module_id);

        if (!$module) {
            return false;
        }

        $module['name'] .= ' (Copia)';
        unset($module['id']);
        unset($module['created_at']);
        unset($module['updated_at']);

        return $this->createModule($module);
    }
}

?>
