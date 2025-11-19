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
 * Privacy provider for local_points.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_points\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation for local_points.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns metadata about this plugin's data storage.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_points_user',
            [
                'userid' => 'privacy:metadata:local_points_user:userid',
                'points' => 'privacy:metadata:local_points_user:points',
            ],
            'privacy:metadata:local_points_user'
        );

        $collection->add_database_table(
            'local_points_history',
            [
                'userid' => 'privacy:metadata:local_points_history:userid',
                'points' => 'privacy:metadata:local_points_history:points',
                'reason' => 'privacy:metadata:local_points_history:reason',
            ],
            'privacy:metadata:local_points_history'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user data.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // System context for global points.
        $sql = "SELECT c.id
                FROM {context} c
                WHERE c.contextlevel = :contextlevel
                AND EXISTS (
                    SELECT 1 FROM {local_points_user} p WHERE p.userid = :userid
                )";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_SYSTEM,
            'userid' => $userid,
        ]);

        // Course contexts.
        $sql = "SELECT c.id
                FROM {context} c
                JOIN {local_points_user} p ON p.courseid = c.instanceid
                WHERE c.contextlevel = :contextlevel
                AND p.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $sql = "SELECT userid FROM {local_points_user}";
            $userlist->add_from_sql('userid', $sql, []);
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            $sql = "SELECT userid FROM {local_points_user} WHERE courseid = :courseid";
            $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Export all user data for the specified contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                // Export global points.
                $points = $DB->get_records('local_points_user', ['userid' => $userid, 'courseid' => null]);
                $history = $DB->get_records('local_points_history', ['userid' => $userid, 'courseid' => null]);

                $data = (object)[
                    'points' => array_values($points),
                    'history' => array_values($history),
                ];

                writer::with_context($context)->export_data(['local_points'], $data);

            } else if ($context->contextlevel == CONTEXT_COURSE) {
                // Export course points.
                $courseid = $context->instanceid;
                $points = $DB->get_records('local_points_user', ['userid' => $userid, 'courseid' => $courseid]);
                $history = $DB->get_records('local_points_history', ['userid' => $userid, 'courseid' => $courseid]);

                $data = (object)[
                    'points' => array_values($points),
                    'history' => array_values($history),
                ];

                writer::with_context($context)->export_data(['local_points'], $data);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records('local_points_user', ['courseid' => null]);
            $DB->delete_records('local_points_history', ['courseid' => null]);
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            $courseid = $context->instanceid;
            $DB->delete_records('local_points_user', ['courseid' => $courseid]);
            $DB->delete_records('local_points_history', ['courseid' => $courseid]);
        }
    }

    /**
     * Delete all user data for the specified user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                $DB->delete_records('local_points_user', ['userid' => $userid, 'courseid' => null]);
                $DB->delete_records('local_points_history', ['userid' => $userid, 'courseid' => null]);
                $DB->delete_records('local_points_rule_usage', ['userid' => $userid]);
            } else if ($context->contextlevel == CONTEXT_COURSE) {
                $courseid = $context->instanceid;
                $DB->delete_records('local_points_user', ['userid' => $userid, 'courseid' => $courseid]);
                $DB->delete_records('local_points_history', ['userid' => $userid, 'courseid' => $courseid]);
            }
        }
    }

    /**
     * Delete data for multiple users within a single context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records_select('local_points_user',
                "userid $usersql AND courseid IS NULL", $userparams);
            $DB->delete_records_select('local_points_history',
                "userid $usersql AND courseid IS NULL", $userparams);
            $DB->delete_records_select('local_points_rule_usage',
                "userid $usersql", $userparams);
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            $courseid = $context->instanceid;
            $params = array_merge($userparams, ['courseid' => $courseid]);
            $DB->delete_records_select('local_points_user',
                "userid $usersql AND courseid = :courseid", $params);
            $DB->delete_records_select('local_points_history',
                "userid $usersql AND courseid = :courseid", $params);
        }
    }
}
