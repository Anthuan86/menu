# Ejemplos de Implementación

Este documento contiene ejemplos prácticos de cómo implementar y usar el sistema de administración de menús.

## Ejemplo 1: Renderizar Menú en una Página PHP

```php
<?php
// menu-display.php
require_once 'config/config.php';
require_once 'includes/classes/Permissions.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtener datos del usuario de la sesión
$user_id = $_SESSION['user_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? null;

if (!$user_id || !$user_type) {
    die('Usuario no autenticado');
}

// Conectar a la base de datos
$db = get_database_connection();
$permissions = new Permissions($db);

// Obtener menú del usuario
$menu = $permissions->getUserMenu($user_id, $user_type);

// Función recursiva para renderizar menú
function renderMenu($items, $class = 'main-menu') {
    if (empty($items)) return '';

    echo "<ul class='$class'>";
    foreach ($items as $item) {
        echo "<li>";
        echo "<a href='{$item['url']}'>";

        // Icono si existe
        if (!empty($item['icon'])) {
            echo "<i class='fas {$item['icon']}'></i> ";
        }

        echo $item['name'];
        echo "</a>";

        // Renderizar hijos si existen
        if (!empty($item['children'])) {
            renderMenu($item['children'], 'submenu');
        }

        echo "</li>";
    }
    echo "</ul>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Sistema</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            background: #2c3e50;
        }

        .main-menu > li > a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }

        .main-menu > li > a:hover {
            background: #34495e;
        }

        .submenu {
            list-style: none;
            padding: 0;
            background: #34495e;
        }

        .submenu li a {
            display: block;
            padding: 10px 20px 10px 40px;
            color: #ecf0f1;
            text-decoration: none;
        }

        .submenu li a:hover {
            background: #2c3e50;
        }
    </style>
</head>
<body>
    <nav>
        <?php renderMenu($menu); ?>
    </nav>
</body>
</html>
```

## Ejemplo 2: Verificar Permiso Antes de Mostrar Contenido

```php
<?php
// protected-page.php
require_once 'config/config.php';
require_once 'includes/classes/Permissions.php';

session_start();

$user_id = $_SESSION['user_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? null;
$module_id = 5; // ID del módulo que se quiere acceder

if (!$user_id || !$user_type) {
    header('Location: login.php');
    exit;
}

$db = get_database_connection();
$permissions = new Permissions($db);

// Verificar si tiene permiso
if (!$permissions->hasPermission($user_id, $module_id, $user_type)) {
    die('<h1>Acceso Denegado</h1><p>No tienes permisos para ver esta página.</p>');
}

// Registrar el acceso
$permissions->logAccess(
    $user_id,
    $user_type,
    $module_id,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

// Contenido de la página...
?>
<!DOCTYPE html>
<html>
<head>
    <title>Página Protegida</title>
</head>
<body>
    <h1>Bienvenido a la página protegida</h1>
    <p>Solo usuarios con permisos pueden ver este contenido.</p>
</body>
</html>
```

## Ejemplo 3: Crear Módulos Programáticamente

```php
<?php
// create-modules.php
require_once 'config/config.php';
require_once 'includes/classes/Modules.php';

$db = get_database_connection();
$modules = new Modules($db);

// Crear módulo padre
$dashboard_id = $modules->createModule([
    'name' => 'Dashboard',
    'description' => 'Panel principal del sistema',
    'icon' => 'fa-tachometer-alt',
    'url' => '/dashboard.php',
    'segment' => 'colaborador',
    'order_position' => 1,
    'is_active' => 1
]);

echo "Módulo Dashboard creado con ID: $dashboard_id<br>";

// Crear submódulo
$reports_id = $modules->createModule([
    'name' => 'Reportes',
    'description' => 'Sección de reportes',
    'icon' => 'fa-chart-bar',
    'url' => '/reportes.php',
    'parent_id' => $dashboard_id, // Hijo de Dashboard
    'segment' => 'colaborador',
    'order_position' => 1,
    'is_active' => 1
]);

echo "Módulo Reportes creado con ID: $reports_id<br>";

// Obtener y mostrar todos los módulos
$all_modules = $modules->getModules('colaborador');
echo "<pre>";
print_r($all_modules);
echo "</pre>";

$db->close();
?>
```

## Ejemplo 4: Asignar Permisos a un Usuario

```php
<?php
// assign-permissions.php
require_once 'config/config.php';
require_once 'includes/classes/Permissions.php';

$db = get_database_connection();
$permissions = new Permissions($db);

$user_id = '123'; // ID del usuario
$user_type = 'colaborador';

// Asignar permisos individuales
$permissions->setPermission($user_id, 1, $user_type, true);  // Módulo 1: permitido
$permissions->setPermission($user_id, 2, $user_type, true);  // Módulo 2: permitido
$permissions->setPermission($user_id, 3, $user_type, false); // Módulo 3: denegado

echo "Permisos individuales asignados correctamente<br>";

// O asignar múltiples permisos de una vez
$permisos_multiples = [
    1 => 1, // Permitir módulo 1
    2 => 1, // Permitir módulo 2
    3 => 0, // Denegar módulo 3
    4 => 1, // Permitir módulo 4
    5 => 1  // Permitir módulo 5
];

$permissions->setUserPermissions($user_id, $user_type, $permisos_multiples);

echo "Permisos múltiples asignados correctamente<br>";

// Obtener menú del usuario
$menu = $permissions->getUserMenu($user_id, $user_type);

echo "<h3>Menú del usuario:</h3>";
echo "<pre>";
print_r($menu);
echo "</pre>";

$db->close();
?>
```

## Ejemplo 5: Copiar Permisos Entre Usuarios

```php
<?php
// copy-permissions.php
require_once 'config/config.php';
require_once 'includes/classes/Permissions.php';

$db = get_database_connection();
$permissions = new Permissions($db);

$source_user = '123'; // Usuario origen
$target_user = '456'; // Usuario destino
$user_type = 'colaborador';

// Copiar todos los permisos del usuario origen al destino
if ($permissions->copyPermissions($source_user, $target_user, $user_type)) {
    echo "Permisos copiados correctamente de usuario $source_user a $target_user";
} else {
    echo "Error al copiar permisos";
}

$db->close();
?>
```

## Ejemplo 6: Menú con AJAX/JavaScript

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Dinámico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        #menu-container {
            background: #2c3e50;
            padding: 10px;
        }

        .menu-item {
            padding: 10px 15px;
            color: white;
            cursor: pointer;
            transition: background 0.3s;
        }

        .menu-item:hover {
            background: #34495e;
        }

        .menu-item i {
            margin-right: 10px;
        }

        .submenu {
            padding-left: 30px;
        }
    </style>
</head>
<body>
    <div id="menu-container">
        <div id="menu-loading">Cargando menú...</div>
    </div>

    <script>
        // ID y tipo de usuario (normalmente vendrían de la sesión)
        const userId = '123';
        const userType = 'colaborador';

        // Cargar menú
        async function loadMenu() {
            try {
                const response = await fetch(
                    `api/permissions.php?action=menu&user_id=${userId}&user_type=${userType}`
                );
                const data = await response.json();

                if (data.success) {
                    renderMenu(data.data);
                } else {
                    document.getElementById('menu-loading').textContent = 'Error al cargar menú';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('menu-loading').textContent = 'Error al cargar menú';
            }
        }

        function renderMenu(items) {
            const container = document.getElementById('menu-container');
            container.innerHTML = '';

            items.forEach(item => {
                container.appendChild(createMenuItem(item));
            });
        }

        function createMenuItem(item, isChild = false) {
            const div = document.createElement('div');
            div.className = 'menu-item' + (isChild ? ' submenu' : '');

            const link = document.createElement('a');
            link.href = item.url;
            link.style.color = 'white';
            link.style.textDecoration = 'none';

            if (item.icon) {
                const icon = document.createElement('i');
                icon.className = 'fas ' + item.icon;
                link.appendChild(icon);
            }

            link.appendChild(document.createTextNode(item.name));
            div.appendChild(link);

            const parentDiv = document.createElement('div');
            parentDiv.appendChild(div);

            // Renderizar hijos
            if (item.children && item.children.length > 0) {
                item.children.forEach(child => {
                    parentDiv.appendChild(createMenuItem(child, true));
                });
            }

            return parentDiv;
        }

        // Cargar menú al cargar la página
        document.addEventListener('DOMContentLoaded', loadMenu);
    </script>
</body>
</html>
```

## Ejemplo 7: Búsqueda de Usuarios

```php
<?php
// search-users.php
require_once 'config/config.php';
require_once 'includes/classes/Users.php';

$db = get_database_connection();
$users = new Users($db);

// Buscar usuarios
$search_term = $_GET['q'] ?? '';

if (empty($search_term)) {
    echo json_encode(['success' => false, 'message' => 'Término de búsqueda vacío']);
    exit;
}

// Buscar en todos los segmentos
$results = $users->searchUsers($search_term);

// O buscar solo en un segmento específico
// $results = $users->searchUsers($search_term, 'colaborador');

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $results,
    'count' => count($results)
]);

$db->close();
?>
```

## Ejemplo 8: Widget de Menú Reutilizable

```php
<?php
// includes/widgets/menu-widget.php
class MenuWidget {
    private $permissions;
    private $user_id;
    private $user_type;

    public function __construct($db, $user_id, $user_type) {
        require_once __DIR__ . '/../classes/Permissions.php';
        $this->permissions = new Permissions($db);
        $this->user_id = $user_id;
        $this->user_type = $user_type;
    }

    public function render($template = 'vertical') {
        $menu = $this->permissions->getUserMenu($this->user_id, $this->user_type);

        switch ($template) {
            case 'horizontal':
                return $this->renderHorizontal($menu);
            case 'vertical':
                return $this->renderVertical($menu);
            case 'sidebar':
                return $this->renderSidebar($menu);
            default:
                return $this->renderVertical($menu);
        }
    }

    private function renderVertical($items, $level = 0) {
        if (empty($items)) return '';

        $class = $level === 0 ? 'main-menu' : 'submenu';
        $html = "<ul class='$class level-$level'>";

        foreach ($items as $item) {
            $html .= "<li>";
            $html .= "<a href='{$item['url']}'>";

            if (!empty($item['icon'])) {
                $html .= "<i class='fas {$item['icon']}'></i> ";
            }

            $html .= $item['name'];
            $html .= "</a>";

            if (!empty($item['children'])) {
                $html .= $this->renderVertical($item['children'], $level + 1);
            }

            $html .= "</li>";
        }

        $html .= "</ul>";
        return $html;
    }

    private function renderHorizontal($items) {
        if (empty($items)) return '';

        $html = "<nav class='horizontal-menu'><ul>";

        foreach ($items as $item) {
            $html .= "<li>";
            $html .= "<a href='{$item['url']}'>";

            if (!empty($item['icon'])) {
                $html .= "<i class='fas {$item['icon']}'></i> ";
            }

            $html .= $item['name'];
            $html .= "</a>";

            if (!empty($item['children'])) {
                $html .= "<div class='dropdown'>";
                $html .= $this->renderVertical($item['children']);
                $html .= "</div>";
            }

            $html .= "</li>";
        }

        $html .= "</ul></nav>";
        return $html;
    }

    private function renderSidebar($items) {
        if (empty($items)) return '';

        $html = "<aside class='sidebar-menu'>";
        $html .= $this->renderVertical($items);
        $html .= "</aside>";
        return $html;
    }
}

// Uso del widget:
// session_start();
// $db = get_database_connection();
// $widget = new MenuWidget($db, $_SESSION['user_id'], $_SESSION['user_type']);
// echo $widget->render('vertical');
?>
```

## Ejemplo 9: Middleware de Verificación de Permisos

```php
<?php
// includes/middleware/auth-middleware.php
class AuthMiddleware {
    private $permissions;

    public function __construct($db) {
        require_once __DIR__ . '/../classes/Permissions.php';
        $this->permissions = new Permissions($db);
    }

    public function checkAccess($module_id) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $user_id = $_SESSION['user_id'] ?? null;
        $user_type = $_SESSION['user_type'] ?? null;

        if (!$user_id || !$user_type) {
            $this->redirectToLogin();
        }

        if (!$this->permissions->hasPermission($user_id, $module_id, $user_type)) {
            $this->accessDenied();
        }

        // Registrar acceso
        $this->permissions->logAccess(
            $user_id,
            $user_type,
            $module_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );

        return true;
    }

    private function redirectToLogin() {
        header('Location: /login.php');
        exit;
    }

    private function accessDenied() {
        http_response_code(403);
        include __DIR__ . '/../../views/403.html';
        exit;
    }
}

// Uso en una página protegida:
// require_once 'config/config.php';
// require_once 'includes/middleware/auth-middleware.php';
//
// $db = get_database_connection();
// $auth = new AuthMiddleware($db);
// $auth->checkAccess(5); // ID del módulo
//
// // Contenido de la página...
?>
```

## Ejemplo 10: Estadísticas de Uso

```php
<?php
// statistics.php
require_once 'config/config.php';
require_once 'includes/classes/Permissions.php';

$db = get_database_connection();
$permissions = new Permissions($db);

// Estadísticas generales
$stats = $permissions->getAccessStats();
echo "<h2>Estadísticas Generales</h2>";
echo "Total de accesos: {$stats['total_accesses']}<br>";
echo "Usuarios únicos: {$stats['unique_users']}<br>";
echo "Módulos accedidos: {$stats['modules_accessed']}<br>";

// Estadísticas por usuario
$user_stats = $permissions->getAccessStats([
    'user_id' => '123',
    'user_type' => 'colaborador'
]);
echo "<h2>Estadísticas del Usuario 123</h2>";
echo "Total de accesos: {$user_stats['total_accesses']}<br>";

// Estadísticas por fecha
$date_stats = $permissions->getAccessStats([
    'date_from' => '2024-01-01',
    'date_to' => '2024-12-31'
]);
echo "<h2>Estadísticas del Año 2024</h2>";
echo "Total de accesos: {$date_stats['total_accesses']}<br>";

$db->close();
?>
```

---

Estos ejemplos cubren los casos de uso más comunes del sistema de administración de menús. Puedes adaptarlos según tus necesidades específicas.
