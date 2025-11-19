<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for local_points (Spanish).
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Sistema de Puntos';
$string['points'] = 'Puntos';
$string['point'] = 'Punto';
$string['mypoints'] = 'Mis Puntos';
$string['coursepoints'] = 'Puntos del Curso';
$string['globalpoints'] = 'Puntos Globales';
$string['totalpoints'] = 'Puntos Totales';
$string['yourpoints'] = 'Tus Puntos';

// Capabilities.
$string['points:viewown'] = 'Ver puntos propios';
$string['points:viewall'] = 'Ver puntos de todos los usuarios';
$string['points:award'] = 'Otorgar puntos a usuarios';
$string['points:managerules'] = 'Administrar reglas de puntos';
$string['points:configure'] = 'Configurar plugin de puntos';
$string['points:viewcourse'] = 'Ver puntos del curso';
$string['points:awardincourse'] = 'Otorgar puntos en el curso';
$string['points:managerulesincourse'] = 'Administrar reglas en el curso';

// Settings.
$string['enabled'] = 'Habilitar Sistema de Puntos';
$string['enabled_desc'] = 'Habilitar o deshabilitar todo el sistema de puntos';
$string['showinnav'] = 'Mostrar en navegación';
$string['showinnav_desc'] = 'Mostrar enlace de puntos en la navegación del usuario';
$string['leaderboardsize'] = 'Tamaño del ranking';
$string['leaderboardsize_desc'] = 'Número de usuarios a mostrar en el ranking';
$string['allownegative'] = 'Permitir puntos negativos';
$string['allownegative_desc'] = 'Permitir que los usuarios tengan saldos de puntos negativos';
$string['pointsname'] = 'Nombre de los puntos';
$string['pointsname_desc'] = 'Nombre personalizado para los puntos (ej: "monedas", "estrellas", "XP")';
$string['defaultpoints'] = 'Valores de Puntos por Defecto';
$string['defaultpoints_desc'] = 'Valores de puntos por defecto para acciones comunes al crear nuevas reglas';
$string['defaultactivitycompletion'] = 'Completar actividad';
$string['defaultcoursecompletion'] = 'Completar curso';
$string['defaultforumpost'] = 'Publicación en foro';
$string['defaultquizsubmission'] = 'Envío de cuestionario';
$string['defaultassignmentsubmission'] = 'Envío de tarea';

// Rules.
$string['rules'] = 'Reglas';
$string['managerules'] = 'Administrar Reglas';
$string['createrule'] = 'Crear Regla';
$string['editrule'] = 'Editar Regla';
$string['deleterule'] = 'Eliminar Regla';
$string['rulename'] = 'Nombre de la Regla';
$string['ruledescription'] = 'Descripción';
$string['ruleevent'] = 'Evento Disparador';
$string['rulepoints'] = 'Puntos a Otorgar';
$string['rulecourse'] = 'Curso';
$string['ruleconditions'] = 'Condiciones';
$string['ruleenabled'] = 'Habilitada';
$string['rulemaxawards'] = 'Máximo de Otorgamientos';
$string['rulemaxawards_help'] = 'Número máximo de veces que esta regla puede otorgar puntos a un usuario. Dejar vacío para ilimitado.';
$string['globalrule'] = 'Global (todos los cursos)';
$string['specificcourse'] = 'Curso específico';
$string['rulesaved'] = 'Regla guardada exitosamente';
$string['ruledeleted'] = 'Regla eliminada exitosamente';
$string['confirmdeleterule'] = '¿Está seguro de que desea eliminar esta regla?';
$string['norules'] = 'No hay reglas definidas aún';

// Events.
$string['event_activity_completed'] = 'Actividad completada';
$string['event_course_completed'] = 'Curso completado';
$string['event_user_graded'] = 'Usuario calificado';
$string['event_forum_discussion'] = 'Discusión en foro creada';
$string['event_forum_post'] = 'Publicación en foro creada';
$string['event_quiz_submitted'] = 'Cuestionario enviado';
$string['event_assignment_submitted'] = 'Tarea enviada';
$string['event_user_enrolled'] = 'Usuario matriculado';
$string['event_user_login'] = 'Usuario inició sesión';
$string['event_points_awarded'] = 'Puntos otorgados';
$string['event_program_completed'] = 'Programa completado';

// Activity and Program selectors.
$string['ruleactivity'] = 'Actividad Específica';
$string['ruleactivity_help'] = 'Seleccione una actividad específica para esta regla, o deje vacío para aplicar a todas las actividades del curso.';
$string['allactivities'] = 'Todas las actividades';
$string['ruleprogram'] = 'Programa';
$string['ruleprogram_help'] = 'Seleccione el programa que debe completarse para activar esta regla.';
$string['selectprogram'] = 'Seleccionar un programa';

// Conditions.
$string['condition_min_grade'] = 'Calificación mínima';
$string['condition_completion_state'] = 'Estado de completado';
$string['condition_activity_type'] = 'Tipo de actividad';

// View pages.
$string['pointsoverview'] = 'Resumen de Puntos';
$string['viewdetails'] = 'Ver Detalles';
$string['viewhistory'] = 'Ver Historial';
$string['pointshistory'] = 'Historial de Puntos';
$string['leaderboard'] = 'Ranking';
$string['globalleaderboard'] = 'Ranking Global';
$string['courseleaderboard'] = 'Ranking del Curso';
$string['rank'] = 'Posición';
$string['user'] = 'Usuario';
$string['date'] = 'Fecha';
$string['reason'] = 'Razón';
$string['nohistory'] = 'Sin historial de puntos aún';
$string['nopointsyet'] = 'Sin puntos ganados aún';

// Award points.
$string['awardpoints'] = 'Otorgar Puntos';
$string['awardto'] = 'Otorgar a';
$string['pointstoaward'] = 'Puntos a otorgar';
$string['awardreason'] = 'Razón';
$string['pointsawarded'] = 'Puntos otorgados exitosamente';
$string['selectuser'] = 'Seleccionar usuario';
$string['manuallyawarded'] = 'Otorgado manualmente';

// Errors.
$string['error_invaliduser'] = 'Usuario inválido';
$string['error_invalidcourse'] = 'Curso inválido';
$string['error_invalidpoints'] = 'Valor de puntos inválido';
$string['error_nopermission'] = 'No tiene permiso para realizar esta acción';
$string['error_rulerequired'] = 'El nombre de la regla y el evento son requeridos';

// Privacy.
$string['privacy:metadata:local_points_user'] = 'Almacena totales de puntos de usuarios';
$string['privacy:metadata:local_points_user:userid'] = 'ID del usuario';
$string['privacy:metadata:local_points_user:points'] = 'Puntos totales';
$string['privacy:metadata:local_points_history'] = 'Historial de transacciones de puntos';
$string['privacy:metadata:local_points_history:userid'] = 'ID del usuario';
$string['privacy:metadata:local_points_history:points'] = 'Puntos otorgados';
$string['privacy:metadata:local_points_history:reason'] = 'Razón del otorgamiento';

// Misc.
$string['positive'] = 'Positivo';
$string['negative'] = 'Negativo';
$string['allcourses'] = 'Todos los cursos';
$string['selectcourse'] = 'Seleccionar curso';
$string['norulesforevent'] = 'No hay reglas para este evento';
$string['actions'] = 'Acciones';
$string['status'] = 'Estado';
$string['active'] = 'Activo';
$string['inactive'] = 'Inactivo';
$string['backtorules'] = 'Volver a reglas';
$string['backtooverview'] = 'Volver al resumen';

// Task.
$string['task_process_points'] = 'Procesar asignaciones de puntos';

// Report.
$string['pointsreport'] = 'Reporte de Puntos';
$string['filters'] = 'Filtros';
$string['filter'] = 'Filtrar';
$string['reset'] = 'Reiniciar';
$string['allrules'] = 'Todas las reglas';
$string['userid'] = 'ID de Usuario';
$string['awardedby'] = 'Otorgado por';
$string['system'] = 'Sistema';
$string['totalawards'] = 'Total de Otorgamientos';
$string['totalpointsawarded'] = 'Total de Puntos Otorgados';
$string['uniqueusers'] = 'Usuarios Únicos';
$string['viewreport'] = 'Ver Reporte';
$string['exportreport'] = 'Exportar Reporte';

// Course admin.
$string['courserules'] = 'Reglas del Curso';
$string['globalrules'] = 'Reglas Globales';
$string['ruleappliedtimes'] = 'Aplicada {$a} veces';
$string['lastaward'] = 'Último otorgamiento';
$string['rulestats'] = 'Estadísticas de Reglas';
$string['noawardsyet'] = 'Sin otorgamientos aún';
