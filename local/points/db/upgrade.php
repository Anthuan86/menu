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
 * Upgrade steps for local_points.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_points plugin.
 *
 * @param int $oldversion The old version of the plugin
 * @return bool
 */
function xmldb_local_points_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Add cmid and programid fields to rules table.
    if ($oldversion < 2024111902) {
        $table = new xmldb_table('local_points_rules');

        // Add cmid field.
        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add programid field.
        $field = new xmldb_field('programid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'cmid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index for programid.
        $index = new xmldb_index('programid', XMLDB_INDEX_NOTUNIQUE, ['programid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add key for cmid.
        $key = new xmldb_key('cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);
        $dbman->add_key($table, $key);

        upgrade_plugin_savepoint(true, 2024111902, 'local', 'points');
    }

    return true;
}
