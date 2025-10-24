# Sistema de Administración de Menús

Sistema completo de administración de menús personalizados para tres segmentos de usuarios: **Colaboradores**, **Clientes** y **Partners**.

## Características Principales

- **Gestión de Módulos**: Crear, editar y eliminar módulos del menú
- **Menús Jerárquicos**: Soporte para submenús y estructura de árbol
- **Tres Segmentos**: Colaboradores, Clientes y Partners
- **Permisos Personalizados**: Asignación de permisos por usuario y módulo
- **Interfaz Moderna**: Panel de administración intuitivo con diseño responsivo
- **API REST**: APIs completas para integración con otros sistemas
- **Vista Previa**: Visualización de menús por segmento y usuario
- **Auditoría**: Log de accesos a módulos

## Estructura del Proyecto

```
menu/
├── admin/                      # Panel de administración
│   ├── index.html             # Página principal de administración
│   └── menu-viewer.html       # Visualizador de menús
├── api/                       # APIs REST
│   ├── modules.php           # API de módulos
│   ├── permissions.php       # API de permisos
│   └── users.php             # API de usuarios
├── assets/                    # Recursos estáticos
│   ├── css/
│   │   └── admin.css         # Estilos del panel
│   ├── js/
│   │   └── admin.js          # JavaScript del panel
│   └── images/
├── config/                    # Configuración
│   ├── config.php            # Configuración principal
│   └── database.sql          # Script de creación de BD
├── includes/
│   └── classes/              # Clases PHP
│       ├── Users.php         # Gestión de usuarios
│       ├── Modules.php       # Gestión de módulos
│       └── Permissions.php   # Gestión de permisos
└── logs/                      # Archivos de log
```

## Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP requeridas:
  - mysqli
  - json

## Instalación

### 1. Clonar o copiar el proyecto

```bash
git clone [repository-url]
cd menu
```

### 2. Configurar la base de datos

Editar el archivo `config/config.php` con las credenciales de tu base de datos:

```php
$db_config = array(
    'db_server' => 'tu-servidor',
    'db_port' => 3306,
    'db_username' => 'tu-usuario',
    'db_password' => 'tu-contraseña',
    'db_name' => 'bd_vtigertrascend2023'
);
```

### 3. Ejecutar el script SQL

```bash
mysql -u tu-usuario -p bd_vtigertrascend2023 < config/database.sql
```

O desde MySQL:

```sql
USE bd_vtigertrascend2023;
SOURCE /ruta/al/proyecto/config/database.sql;
```

### 4. Configurar permisos

```bash
chmod 777 logs/
```

### 5. Acceder al panel de administración

Abrir en el navegador:
```
http://tu-dominio/menu/admin/index.html
```

## Uso del Sistema

### Panel de Administración

El panel de administración se divide en 4 secciones principales:

#### 1. Dashboard

Muestra estadísticas generales:
- Total de usuarios por segmento
- Total de módulos activos/inactivos
- Estadísticas de uso

#### 2. Módulos

Gestión completa de módulos del menú:

**Crear un módulo:**
1. Hacer clic en "Nuevo Módulo"
2. Completar el formulario:
   - Nombre del módulo
   - Descripción (opcional)
   - URL del módulo
   - Icono (Font Awesome class)
   - Segmento (Colaborador/Cliente/Partner)
   - Módulo padre (para submenús)
   - Orden de visualización
   - Estado (Activo/Inactivo)
3. Guardar

**Editar un módulo:**
1. Hacer clic en el botón de editar
2. Modificar los campos necesarios
3. Guardar cambios

**Eliminar un módulo:**
1. Hacer clic en el botón de eliminar
2. Confirmar la acción

#### 3. Permisos

Asignación de permisos por usuario:

1. Seleccionar el segmento
2. Seleccionar el usuario
3. Marcar/desmarcar los módulos a los que tiene acceso
4. Guardar cambios

**Opciones adicionales:**
- **Resetear permisos**: Elimina todas las restricciones del usuario
- **Copiar permisos**: Copiar permisos de un usuario a otro (vía API)

#### 4. Usuarios

Visualización de todos los usuarios del sistema:

- Filtrar por segmento
- Buscar por nombre o email
- Acceder rápidamente a la gestión de permisos

### Visualizador de Menús

Herramienta para previsualizar los menús:

#### Vista por Segmento
Muestra el menú completo disponible para cada segmento.

#### Vista por Usuario
Muestra el menú personalizado de un usuario específico según sus permisos.

#### Vista de Comparación
Muestra los tres menús (Colaboradores, Clientes, Partners) lado a lado.

## API REST

### Endpoints de Usuarios

#### Obtener todos los usuarios
```
GET /api/users.php
```

#### Obtener usuarios por segmento
```
GET /api/users.php?segment=colaborador
```

#### Buscar usuarios
```
GET /api/users.php?search=nombre
```

#### Obtener conteo de usuarios
```
GET /api/users.php?action=counts
```

### Endpoints de Módulos

#### Obtener todos los módulos
```
GET /api/modules.php
```

#### Obtener módulos por segmento
```
GET /api/modules.php?segment=colaborador
```

#### Obtener jerarquía de módulos
```
GET /api/modules.php?action=hierarchy&segment=colaborador
```

#### Crear módulo
```
POST /api/modules.php
Content-Type: application/json

{
    "name": "Dashboard",
    "description": "Panel principal",
    "url": "/dashboard.php",
    "icon": "fa-tachometer",
    "segment": "colaborador",
    "parent_id": null,
    "order_position": 1,
    "is_active": 1
}
```

#### Actualizar módulo
```
PUT /api/modules.php?module_id=1
Content-Type: application/json

{
    "name": "Dashboard Actualizado",
    "is_active": 1
}
```

#### Eliminar módulo
```
DELETE /api/modules.php?module_id=1
```

### Endpoints de Permisos

#### Obtener menú del usuario
```
GET /api/permissions.php?action=menu&user_id=123&user_type=colaborador
```

#### Obtener módulos con permisos del usuario
```
GET /api/permissions.php?action=modules&user_id=123&user_type=colaborador
```

#### Verificar permiso específico
```
GET /api/permissions.php?action=check&user_id=123&module_id=5&user_type=colaborador
```

#### Asignar permiso individual
```
POST /api/permissions.php
Content-Type: application/json

{
    "user_id": "123",
    "module_id": 5,
    "user_type": "colaborador",
    "can_access": true
}
```

#### Asignar permisos múltiples
```
POST /api/permissions.php?action=bulk
Content-Type: application/json

{
    "user_id": "123",
    "user_type": "colaborador",
    "permissions": {
        "1": 1,
        "2": 1,
        "3": 0,
        "4": 1
    }
}
```

#### Copiar permisos
```
POST /api/permissions.php?action=copy
Content-Type: application/json

{
    "source_user_id": "123",
    "target_user_id": "456",
    "user_type": "colaborador"
}
```

#### Resetear permisos
```
DELETE /api/permissions.php?user_id=123&user_type=colaborador&action=reset
```

#### Registrar acceso a módulo
```
POST /api/permissions.php?action=log
Content-Type: application/json

{
    "user_id": "123",
    "user_type": "colaborador",
    "module_id": 5
}
```

## Integración con Otros Sistemas

### Obtener menú de un usuario en PHP

```php
<?php
require_once 'config/config.php';
require_once 'includes/classes/Permissions.php';

$db = get_database_connection();
$permissions = new Permissions($db);

// Obtener menú del usuario
$user_id = '123';
$user_type = 'colaborador';
$menu = $permissions->getUserMenu($user_id, $user_type);

// Renderizar menú
foreach ($menu as $item) {
    echo '<li>';
    echo '<a href="' . $item['url'] . '">';
    if ($item['icon']) {
        echo '<i class="fas ' . $item['icon'] . '"></i> ';
    }
    echo $item['name'];
    echo '</a>';

    // Submenús
    if (isset($item['children']) && count($item['children']) > 0) {
        echo '<ul>';
        foreach ($item['children'] as $child) {
            echo '<li><a href="' . $child['url'] . '">' . $child['name'] . '</a></li>';
        }
        echo '</ul>';
    }

    echo '</li>';
}
?>
```

### Obtener menú vía JavaScript

```javascript
async function getUserMenu(userId, userType) {
    const response = await fetch(
        `api/permissions.php?action=menu&user_id=${userId}&user_type=${userType}`
    );
    const data = await response.json();

    if (data.success) {
        return data.data;
    }

    throw new Error(data.message);
}

// Uso
getUserMenu('123', 'colaborador')
    .then(menu => {
        console.log('Menú del usuario:', menu);
        // Renderizar menú...
    })
    .catch(error => {
        console.error('Error:', error);
    });
```

## Segmentos de Usuarios

### Colaboradores
- Fuente: Tabla `vtiger_users`
- Condición: `status = 'Active'`
- Campos: id, user_name, first_name, last_name, email1

### Clientes
- Fuente: Tabla `vtiger_contactdetails`
- Join: `vtiger_account` (para obtener nombre de cuenta)
- Campos: contactid, accountid, firstname, lastname, email, accountname

### Partners
- Fuente: Tablas `vtiger_contactdetails` + `vtiger_users`
- Condición: Email del contacto debe existir en usuarios activos
- Join: `vtiger_account` (para obtener nombre de cuenta)
- Campos: contactid, accountid, firstname, lastname, email, accountname, user_id, user_name

## Base de Datos

### Tablas Principales

#### menu_modules
Almacena los módulos del menú.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único del módulo |
| name | VARCHAR(100) | Nombre del módulo |
| description | TEXT | Descripción |
| icon | VARCHAR(50) | Clase del icono |
| url | VARCHAR(255) | URL del módulo |
| parent_id | INT | ID del módulo padre |
| segment | VARCHAR(20) | Segmento (colaborador/cliente/partner) |
| order_position | INT | Orden de visualización |
| is_active | TINYINT | 1=Activo, 0=Inactivo |

#### menu_user_permissions
Almacena los permisos de usuarios a módulos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único del permiso |
| user_id | VARCHAR(50) | ID del usuario |
| module_id | INT | ID del módulo |
| user_type | VARCHAR(20) | Tipo de usuario |
| can_access | TINYINT | 1=Puede acceder, 0=No puede |

#### menu_access_log
Registra los accesos a módulos (auditoría).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único del log |
| user_id | VARCHAR(50) | ID del usuario |
| user_type | VARCHAR(20) | Tipo de usuario |
| module_id | INT | ID del módulo |
| access_time | TIMESTAMP | Fecha/hora del acceso |
| ip_address | VARCHAR(45) | Dirección IP |
| user_agent | TEXT | User agent del navegador |

### Procedimientos Almacenados

#### sp_get_user_menu
Obtiene el menú completo de un usuario con sus permisos.

```sql
CALL sp_get_user_menu('123', 'colaborador');
```

#### sp_assign_permission
Asigna o actualiza un permiso de usuario.

```sql
CALL sp_assign_permission('123', 5, 'colaborador', 1);
```

#### sp_log_access
Registra un acceso a módulo.

```sql
CALL sp_log_access('123', 'colaborador', 5, '192.168.1.1', 'Mozilla/5.0...');
```

## Iconos (Font Awesome)

El sistema usa Font Awesome 6.4.0 para los iconos. Algunos ejemplos:

- `fa-tachometer-alt` - Dashboard
- `fa-user` - Usuario
- `fa-users` - Usuarios
- `fa-briefcase` - Servicios
- `fa-file-invoice` - Facturas
- `fa-chart-bar` - Reportes
- `fa-cog` - Configuración
- `fa-folder` - Documentos
- `fa-tasks` - Tareas

Ver más iconos en: https://fontawesome.com/icons

## Solución de Problemas

### Error de conexión a la base de datos

Verificar:
1. Credenciales en `config/config.php`
2. Que el servidor MySQL esté corriendo
3. Que el usuario tenga permisos en la base de datos

### Los módulos no se cargan

1. Verificar que se ejecutó el script SQL correctamente
2. Revisar los logs en `logs/portal_errors.log`
3. Verificar permisos de la carpeta logs

### Errores de JavaScript

1. Abrir la consola del navegador (F12)
2. Verificar la ruta de las APIs en `assets/js/admin.js`
3. Verificar CORS si estás en dominios diferentes

## Seguridad

- Todas las consultas SQL usan `real_escape_string()` para prevenir SQL Injection
- Las APIs validan los datos de entrada
- Los logs registran todos los accesos para auditoría
- Se recomienda implementar autenticación antes de usar en producción

## Próximas Mejoras

- [ ] Sistema de autenticación integrado
- [ ] Exportación de configuración de menús
- [ ] Importación masiva de permisos
- [ ] Búsqueda avanzada de módulos
- [ ] Caché de menús para mejor rendimiento
- [ ] Notificaciones de cambios en permisos
- [ ] Historial de cambios

## Soporte

Para reportar bugs o solicitar nuevas funcionalidades, crear un issue en el repositorio.

## Licencia

Este proyecto es de uso interno.

---

Desarrollado con PHP, MySQL, HTML5, CSS3 y JavaScript vanilla.
