-- ============================================
-- Sistema de Administración de Menús
-- Script de creación de tablas
-- ============================================

-- Tabla de módulos/menús
CREATE TABLE IF NOT EXISTS menu_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nombre del módulo',
    description TEXT COMMENT 'Descripción del módulo',
    icon VARCHAR(50) COMMENT 'Clase del icono (ej: fa-dashboard)',
    url VARCHAR(255) NOT NULL COMMENT 'URL del módulo',
    parent_id INT DEFAULT NULL COMMENT 'ID del módulo padre (para submenús)',
    segment VARCHAR(20) NOT NULL COMMENT 'Segmento: colaborador, cliente, partner',
    order_position INT DEFAULT 0 COMMENT 'Orden de visualización',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES menu_modules(id) ON DELETE CASCADE,
    INDEX idx_segment (segment),
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Módulos y menús del sistema';

-- Tabla de permisos de usuarios a módulos
CREATE TABLE IF NOT EXISTS menu_user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL COMMENT 'ID del usuario según su segmento',
    module_id INT NOT NULL COMMENT 'ID del módulo',
    user_type VARCHAR(20) NOT NULL COMMENT 'Tipo: colaborador, cliente, partner',
    can_access TINYINT(1) DEFAULT 1 COMMENT '1=Puede acceder, 0=No puede',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES menu_modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_module (user_id, module_id, user_type),
    INDEX idx_user (user_id, user_type),
    INDEX idx_module (module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permisos de acceso de usuarios a módulos';

-- Tabla de log de accesos (opcional, para auditoría)
CREATE TABLE IF NOT EXISTS menu_access_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    user_type VARCHAR(20) NOT NULL,
    module_id INT NOT NULL,
    access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_user_access (user_id, user_type, access_time),
    INDEX idx_module_access (module_id, access_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de accesos a módulos';

-- ============================================
-- Datos de ejemplo
-- ============================================

-- Módulos para COLABORADORES
INSERT INTO menu_modules (name, description, icon, url, parent_id, segment, order_position, is_active) VALUES
('Dashboard', 'Panel principal de colaboradores', 'fa-tachometer-alt', '/colaboradores/dashboard.php', NULL, 'colaborador', 1, 1),
('Mi Perfil', 'Información personal', 'fa-user', '/colaboradores/perfil.php', NULL, 'colaborador', 2, 1),
('Reportes', 'Reportes y estadísticas', 'fa-chart-bar', '/colaboradores/reportes.php', NULL, 'colaborador', 3, 1),
('Tareas', 'Gestión de tareas', 'fa-tasks', '/colaboradores/tareas.php', NULL, 'colaborador', 4, 1),
('Documentos', 'Gestión documental', 'fa-folder', '/colaboradores/documentos.php', NULL, 'colaborador', 5, 1),
('Configuración', 'Configuración del sistema', 'fa-cog', '/colaboradores/configuracion.php', NULL, 'colaborador', 6, 1);

-- Módulos para CLIENTES
INSERT INTO menu_modules (name, description, icon, url, parent_id, segment, order_position, is_active) VALUES
('Dashboard', 'Panel principal de clientes', 'fa-home', '/clientes/dashboard.php', NULL, 'cliente', 1, 1),
('Mis Servicios', 'Servicios contratados', 'fa-briefcase', '/clientes/servicios.php', NULL, 'cliente', 2, 1),
('Facturas', 'Historial de facturación', 'fa-file-invoice', '/clientes/facturas.php', NULL, 'cliente', 3, 1),
('Soporte', 'Centro de soporte', 'fa-headset', '/clientes/soporte.php', NULL, 'cliente', 4, 1),
('Mi Cuenta', 'Información de la cuenta', 'fa-user-circle', '/clientes/cuenta.php', NULL, 'cliente', 5, 1);

-- Módulos para PARTNERS
INSERT INTO menu_modules (name, description, icon, url, parent_id, segment, order_position, is_active) VALUES
('Dashboard', 'Panel principal de partners', 'fa-chart-line', '/partners/dashboard.php', NULL, 'partner', 1, 1),
('Mis Clientes', 'Gestión de clientes', 'fa-users', '/partners/clientes.php', NULL, 'partner', 2, 1),
('Comisiones', 'Historial de comisiones', 'fa-dollar-sign', '/partners/comisiones.php', NULL, 'partner', 3, 1),
('Estadísticas', 'Estadísticas de ventas', 'fa-chart-pie', '/partners/estadisticas.php', NULL, 'partner', 4, 1),
('Recursos', 'Materiales y recursos', 'fa-book', '/partners/recursos.php', NULL, 'partner', 5, 1),
('Mi Perfil', 'Información del partner', 'fa-id-card', '/partners/perfil.php', NULL, 'partner', 6, 1);

-- Submódulos de ejemplo para Colaboradores > Reportes
INSERT INTO menu_modules (name, description, icon, url, parent_id, segment, order_position, is_active) VALUES
('Reporte Mensual', 'Reporte mensual de actividades', 'fa-calendar', '/colaboradores/reportes/mensual.php', 3, 'colaborador', 1, 1),
('Reporte Anual', 'Reporte anual', 'fa-calendar-alt', '/colaboradores/reportes/anual.php', 3, 'colaborador', 2, 1);

-- ============================================
-- Vistas útiles
-- ============================================

-- Vista de módulos con información del padre
CREATE OR REPLACE VIEW view_modules_hierarchy AS
SELECT
    m.id,
    m.name,
    m.description,
    m.icon,
    m.url,
    m.parent_id,
    p.name as parent_name,
    m.segment,
    m.order_position,
    m.is_active,
    m.created_at,
    m.updated_at
FROM menu_modules m
LEFT JOIN menu_modules p ON m.parent_id = p.id
ORDER BY m.segment, m.parent_id, m.order_position;

-- Vista de permisos con información de módulo
CREATE OR REPLACE VIEW view_user_permissions_detail AS
SELECT
    up.id,
    up.user_id,
    up.user_type,
    up.can_access,
    m.id as module_id,
    m.name as module_name,
    m.description as module_description,
    m.icon,
    m.url,
    m.segment,
    m.order_position,
    m.is_active as module_active
FROM menu_user_permissions up
INNER JOIN menu_modules m ON up.module_id = m.id
WHERE m.is_active = 1
ORDER BY up.user_id, m.order_position;

-- ============================================
-- Procedimientos almacenados
-- ============================================

DELIMITER //

-- Procedimiento para obtener el menú de un usuario
CREATE PROCEDURE IF NOT EXISTS sp_get_user_menu(
    IN p_user_id VARCHAR(50),
    IN p_user_type VARCHAR(20)
)
BEGIN
    SELECT
        m.id,
        m.name,
        m.description,
        m.icon,
        m.url,
        m.parent_id,
        m.order_position,
        COALESCE(up.can_access, 0) as has_permission
    FROM menu_modules m
    LEFT JOIN menu_user_permissions up ON (
        m.id = up.module_id
        AND up.user_id = p_user_id
        AND up.user_type = p_user_type
    )
    WHERE m.segment = p_user_type
        AND m.is_active = 1
        AND (up.can_access = 1 OR up.can_access IS NULL)
    ORDER BY m.parent_id, m.order_position;
END //

-- Procedimiento para asignar permiso a un usuario
CREATE PROCEDURE IF NOT EXISTS sp_assign_permission(
    IN p_user_id VARCHAR(50),
    IN p_module_id INT,
    IN p_user_type VARCHAR(20),
    IN p_can_access TINYINT(1)
)
BEGIN
    INSERT INTO menu_user_permissions (user_id, module_id, user_type, can_access)
    VALUES (p_user_id, p_module_id, p_user_type, p_can_access)
    ON DUPLICATE KEY UPDATE
        can_access = p_can_access,
        updated_at = CURRENT_TIMESTAMP;
END //

-- Procedimiento para registrar acceso a módulo
CREATE PROCEDURE IF NOT EXISTS sp_log_access(
    IN p_user_id VARCHAR(50),
    IN p_user_type VARCHAR(20),
    IN p_module_id INT,
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT
)
BEGIN
    INSERT INTO menu_access_log (user_id, user_type, module_id, ip_address, user_agent)
    VALUES (p_user_id, p_user_type, p_module_id, p_ip_address, p_user_agent);
END //

DELIMITER ;

-- ============================================
-- Fin del script
-- ============================================
