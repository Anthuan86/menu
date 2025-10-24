# Guía de Instalación Rápida

## Paso 1: Configurar Base de Datos

1. Editar `config/config.php` con tus credenciales:

```php
$db_config = array(
    'db_server' => 'tu-servidor',      // Ej: localhost, mysql-docker
    'db_port' => 3306,
    'db_username' => 'tu-usuario',
    'db_password' => 'tu-contraseña',
    'db_name' => 'bd_vtigertrascend2023'
);
```

2. Ejecutar el script SQL:

```bash
mysql -u tu-usuario -p bd_vtigertrascend2023 < config/database.sql
```

O desde MySQL Workbench / phpMyAdmin:
- Abrir el archivo `config/database.sql`
- Ejecutar todas las queries

## Paso 2: Configurar Permisos

```bash
chmod 777 logs/
```

## Paso 3: Verificar Instalación

Abrir en el navegador:
```
http://tu-dominio/menu/
```

Deberías ver el panel de administración.

## Paso 4: Verificar Datos

1. Ir al tab "Dashboard"
2. Verificar que se carguen las estadísticas de usuarios
3. Si ves "0" usuarios, verifica:
   - Conexión a la base de datos
   - Que existan registros en las tablas vtiger_users, vtiger_contactdetails

## Paso 5: Crear Módulos de Prueba

Los módulos de ejemplo ya están creados en el script SQL. Puedes verlos en el tab "Módulos".

## Paso 6: Probar Permisos

1. Ir al tab "Permisos"
2. Seleccionar un segmento
3. Seleccionar un usuario
4. Asignar permisos
5. Guardar

## Troubleshooting

### Error: "Error de conexión a la base de datos"

**Solución:**
- Verificar credenciales en `config/config.php`
- Verificar que MySQL esté corriendo
- Verificar que el usuario tenga permisos

### Error: "No hay usuarios disponibles"

**Solución:**
- Verificar que existan registros en vtiger_users con status = 'Active'
- Verificar que existan registros en vtiger_contactdetails con email

### Los módulos no se cargan

**Solución:**
- Verificar que se ejecutó correctamente el script SQL
- Revisar logs en `logs/portal_errors.log`
- Verificar tabla menu_modules en la base de datos

### Error 404 en las APIs

**Solución:**
- Verificar la ruta en `assets/js/admin.js`
- Cambiar `API_BASE_URL` si es necesario
- Verificar permisos de archivos PHP

### JavaScript no funciona

**Solución:**
- Abrir consola del navegador (F12)
- Ver errores en la consola
- Verificar que se carguen correctamente los archivos CSS y JS

## Configuración Avanzada

### Cambiar modo debug

En `config/config.php`:

```php
'debug_mode' => false, // true para desarrollo, false para producción
```

### Cambiar timeout de sesión

En `config/config.php`:

```php
'session_timeout' => 3600 // En segundos (1 hora por defecto)
```

### Configurar zona horaria

En `config/config.php`:

```php
date_default_timezone_set('America/Bogota'); // Cambiar según ubicación
```

## Verificación Final

1. Dashboard muestra estadísticas ✓
2. Se pueden crear módulos ✓
3. Se pueden asignar permisos ✓
4. El visualizador de menús funciona ✓
5. Las APIs responden correctamente ✓

## Siguiente Paso

Leer la documentación completa en `README.md` y los ejemplos en `EXAMPLES.md`.

## Soporte

Para más ayuda, revisar:
- README.md - Documentación completa
- EXAMPLES.md - Ejemplos de implementación
- logs/portal_errors.log - Logs de errores
