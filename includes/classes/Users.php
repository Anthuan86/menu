<?php
/**
 * Clase Users
 * Gestiona los usuarios de los tres segmentos: Colaboradores, Clientes y Partners
 */

class Users {
    private $db;

    public function __construct($database_connection) {
        $this->db = $database_connection;
    }

    /**
     * Obtiene todos los colaboradores activos
     * @return array Lista de colaboradores
     */
    public function getColaboradores() {
        $sql = "SELECT
                    id,
                    user_name,
                    first_name,
                    last_name,
                    email1 as email,
                    status,
                    'colaborador' as user_type
                FROM vtiger_users
                WHERE status = 'Active'
                ORDER BY first_name, last_name";

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al obtener colaboradores: " . $this->db->error);
            return [];
        }

        $colaboradores = [];
        while ($row = $result->fetch_assoc()) {
            $colaboradores[] = $row;
        }

        return $colaboradores;
    }

    /**
     * Obtiene todos los clientes
     * @return array Lista de clientes
     */
    public function getClientes() {
        $sql = "SELECT
                    c.contactid as id,
                    c.accountid,
                    c.firstname as first_name,
                    c.lastname as last_name,
                    c.email,
                    a.accountname,
                    'cliente' as user_type
                FROM vtiger_contactdetails c
                LEFT JOIN vtiger_account a ON c.accountid = a.accountid
                WHERE c.email IS NOT NULL AND c.email != ''
                ORDER BY c.firstname, c.lastname";

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al obtener clientes: " . $this->db->error);
            return [];
        }

        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }

        return $clientes;
    }

    /**
     * Obtiene todos los partners (contactos que también son usuarios)
     * @return array Lista de partners
     */
    public function getPartners() {
        $sql = "SELECT
                    c.contactid as id,
                    c.accountid,
                    c.firstname as first_name,
                    c.lastname as last_name,
                    c.email,
                    a.accountname,
                    u.id as user_id,
                    u.user_name,
                    u.status,
                    'partner' as user_type
                FROM vtiger_contactdetails c
                INNER JOIN vtiger_users u ON c.email = u.email1
                LEFT JOIN vtiger_account a ON c.accountid = a.accountid
                WHERE u.status = 'Active'
                    AND c.email IS NOT NULL
                    AND c.email != ''
                ORDER BY c.firstname, c.lastname";

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al obtener partners: " . $this->db->error);
            return [];
        }

        $partners = [];
        while ($row = $result->fetch_assoc()) {
            $partners[] = $row;
        }

        return $partners;
    }

    /**
     * Obtiene un usuario específico por ID y tipo
     * @param string $user_id ID del usuario
     * @param string $user_type Tipo de usuario (colaborador, cliente, partner)
     * @return array|null Datos del usuario o null si no existe
     */
    public function getUserById($user_id, $user_type) {
        $user_id = $this->db->real_escape_string($user_id);
        $user_type = $this->db->real_escape_string($user_type);

        switch ($user_type) {
            case 'colaborador':
                $sql = "SELECT
                            id,
                            user_name,
                            first_name,
                            last_name,
                            email1 as email,
                            status,
                            'colaborador' as user_type
                        FROM vtiger_users
                        WHERE id = '$user_id' AND status = 'Active'";
                break;

            case 'cliente':
                $sql = "SELECT
                            c.contactid as id,
                            c.accountid,
                            c.firstname as first_name,
                            c.lastname as last_name,
                            c.email,
                            a.accountname,
                            'cliente' as user_type
                        FROM vtiger_contactdetails c
                        LEFT JOIN vtiger_account a ON c.accountid = a.accountid
                        WHERE c.contactid = '$user_id'";
                break;

            case 'partner':
                $sql = "SELECT
                            c.contactid as id,
                            c.accountid,
                            c.firstname as first_name,
                            c.lastname as last_name,
                            c.email,
                            a.accountname,
                            u.id as user_id,
                            u.user_name,
                            u.status,
                            'partner' as user_type
                        FROM vtiger_contactdetails c
                        INNER JOIN vtiger_users u ON c.email = u.email1
                        LEFT JOIN vtiger_account a ON c.accountid = a.accountid
                        WHERE c.contactid = '$user_id' AND u.status = 'Active'";
                break;

            default:
                return null;
        }

        $result = $this->db->query($sql);

        if (!$result) {
            log_error("Error al obtener usuario: " . $this->db->error);
            return null;
        }

        return $result->fetch_assoc();
    }

    /**
     * Obtiene todos los usuarios de un segmento específico
     * @param string $segment Segmento (colaborador, cliente, partner)
     * @return array Lista de usuarios
     */
    public function getUsersBySegment($segment) {
        switch ($segment) {
            case 'colaborador':
                return $this->getColaboradores();
            case 'cliente':
                return $this->getClientes();
            case 'partner':
                return $this->getPartners();
            default:
                return [];
        }
    }

    /**
     * Busca usuarios por nombre o email en un segmento
     * @param string $search_term Término de búsqueda
     * @param string $segment Segmento (colaborador, cliente, partner)
     * @return array Lista de usuarios que coinciden
     */
    public function searchUsers($search_term, $segment = null) {
        $search_term = $this->db->real_escape_string($search_term);
        $results = [];

        if (!$segment || $segment === 'colaborador') {
            $sql = "SELECT
                        id,
                        user_name,
                        first_name,
                        last_name,
                        email1 as email,
                        status,
                        'colaborador' as user_type
                    FROM vtiger_users
                    WHERE status = 'Active'
                        AND (first_name LIKE '%$search_term%'
                            OR last_name LIKE '%$search_term%'
                            OR email1 LIKE '%$search_term%'
                            OR user_name LIKE '%$search_term%')
                    ORDER BY first_name, last_name";

            $result = $this->db->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $results[] = $row;
                }
            }
        }

        if (!$segment || $segment === 'cliente') {
            $sql = "SELECT
                        c.contactid as id,
                        c.accountid,
                        c.firstname as first_name,
                        c.lastname as last_name,
                        c.email,
                        a.accountname,
                        'cliente' as user_type
                    FROM vtiger_contactdetails c
                    LEFT JOIN vtiger_account a ON c.accountid = a.accountid
                    WHERE (c.firstname LIKE '%$search_term%'
                        OR c.lastname LIKE '%$search_term%'
                        OR c.email LIKE '%$search_term%'
                        OR a.accountname LIKE '%$search_term%')
                    ORDER BY c.firstname, c.lastname";

            $result = $this->db->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $results[] = $row;
                }
            }
        }

        if (!$segment || $segment === 'partner') {
            $sql = "SELECT
                        c.contactid as id,
                        c.accountid,
                        c.firstname as first_name,
                        c.lastname as last_name,
                        c.email,
                        a.accountname,
                        u.id as user_id,
                        u.user_name,
                        u.status,
                        'partner' as user_type
                    FROM vtiger_contactdetails c
                    INNER JOIN vtiger_users u ON c.email = u.email1
                    LEFT JOIN vtiger_account a ON c.accountid = a.accountid
                    WHERE u.status = 'Active'
                        AND (c.firstname LIKE '%$search_term%'
                            OR c.lastname LIKE '%$search_term%'
                            OR c.email LIKE '%$search_term%'
                            OR a.accountname LIKE '%$search_term%')
                    ORDER BY c.firstname, c.lastname";

            $result = $this->db->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $results[] = $row;
                }
            }
        }

        return $results;
    }

    /**
     * Obtiene el conteo de usuarios por segmento
     * @return array Array con el conteo de cada segmento
     */
    public function getUserCounts() {
        $counts = [
            'colaboradores' => 0,
            'clientes' => 0,
            'partners' => 0
        ];

        // Contar colaboradores
        $sql = "SELECT COUNT(*) as total FROM vtiger_users WHERE status = 'Active'";
        $result = $this->db->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $counts['colaboradores'] = (int)$row['total'];
        }

        // Contar clientes
        $sql = "SELECT COUNT(*) as total FROM vtiger_contactdetails WHERE email IS NOT NULL AND email != ''";
        $result = $this->db->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $counts['clientes'] = (int)$row['total'];
        }

        // Contar partners
        $sql = "SELECT COUNT(*) as total
                FROM vtiger_contactdetails c
                INNER JOIN vtiger_users u ON c.email = u.email1
                WHERE u.status = 'Active'";
        $result = $this->db->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $counts['partners'] = (int)$row['total'];
        }

        return $counts;
    }
}

?>
