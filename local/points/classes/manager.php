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
 * Points manager class.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_points;

defined('MOODLE_INTERNAL') || die();

/**
 * Main manager class for handling points operations.
 */
class manager {

    /**
     * Award points to a user.
     *
     * @param int $userid User ID
     * @param int $points Points to award (can be negative)
     * @param string $reason Reason for awarding
     * @param int|null $courseid Course ID (null for global)
     * @param int|null $ruleid Rule ID that triggered this
     * @param int|null $contextid Related context ID
     * @param int|null $awardedby User who awarded (null for system)
     * @return bool Success
     */
    public static function award_points($userid, $points, $reason, $courseid = null, $ruleid = null, $contextid = null, $awardedby = null) {
        global $DB;

        $now = time();

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        try {
            // Get or create user points record.
            $record = $DB->get_record('local_points_user', [
                'userid' => $userid,
                'courseid' => $courseid,
            ]);

            if ($record) {
                $record->points += $points;
                $record->timemodified = $now;
                $DB->update_record('local_points_user', $record);
            } else {
                $record = new \stdClass();
                $record->userid = $userid;
                $record->courseid = $courseid;
                $record->points = $points;
                $record->timecreated = $now;
                $record->timemodified = $now;
                $DB->insert_record('local_points_user', $record);
            }

            // Record history.
            $history = new \stdClass();
            $history->userid = $userid;
            $history->courseid = $courseid;
            $history->points = $points;
            $history->reason = $reason;
            $history->ruleid = $ruleid;
            $history->contextid = $contextid;
            $history->timecreated = $now;
            $history->awardedby = $awardedby;
            $DB->insert_record('local_points_history', $history);

            // Update rule usage if applicable.
            if ($ruleid) {
                self::increment_rule_usage($ruleid, $userid);
            }

            $transaction->allow_commit();

            // Trigger event.
            $event = \local_points\event\points_awarded::create([
                'context' => $courseid ? \context_course::instance($courseid) : \context_system::instance(),
                'userid' => $awardedby ?? 0,
                'relateduserid' => $userid,
                'other' => [
                    'points' => $points,
                    'reason' => $reason,
                    'courseid' => $courseid,
                ],
            ]);
            $event->trigger();

            return true;
        } catch (\Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }

    /**
     * Get user's total points.
     *
     * @param int $userid User ID
     * @param int|null $courseid Course ID (null for global)
     * @return int Total points
     */
    public static function get_points($userid, $courseid = null) {
        global $DB;

        $record = $DB->get_record('local_points_user', [
            'userid' => $userid,
            'courseid' => $courseid,
        ]);

        return $record ? (int)$record->points : 0;
    }

    /**
     * Get user's total points across all courses.
     *
     * @param int $userid User ID
     * @return int Total points
     */
    public static function get_total_points($userid) {
        global $DB;

        $sql = "SELECT COALESCE(SUM(points), 0) as total FROM {local_points_user} WHERE userid = ?";
        return (int)$DB->get_field_sql($sql, [$userid]);
    }

    /**
     * Get points history for a user.
     *
     * @param int $userid User ID
     * @param int|null $courseid Course ID (null for all)
     * @param int $limit Number of records
     * @param int $offset Offset
     * @return array History records
     */
    public static function get_history($userid, $courseid = null, $limit = 50, $offset = 0) {
        global $DB;

        $params = ['userid' => $userid];
        $where = 'userid = :userid';

        if ($courseid !== null) {
            $where .= ' AND courseid = :courseid';
            $params['courseid'] = $courseid;
        }

        return $DB->get_records_select(
            'local_points_history',
            $where,
            $params,
            'timecreated DESC',
            '*',
            $offset,
            $limit
        );
    }

    /**
     * Get leaderboard.
     *
     * @param int|null $courseid Course ID (null for global)
     * @param int $limit Number of users
     * @return array Leaderboard data
     */
    public static function get_leaderboard($courseid = null, $limit = 10) {
        global $DB;

        $params = [];
        $where = '';

        if ($courseid !== null) {
            $where = 'WHERE p.courseid = :courseid';
            $params['courseid'] = $courseid;
        } else {
            $where = 'WHERE p.courseid IS NULL';
        }

        $sql = "SELECT p.userid, u.firstname, u.lastname, u.email, p.points
                FROM {local_points_user} p
                JOIN {user} u ON u.id = p.userid
                $where
                ORDER BY p.points DESC";

        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    /**
     * Create a new points rule.
     *
     * @param \stdClass $data Rule data
     * @return int Rule ID
     */
    public static function create_rule($data) {
        global $DB, $USER;

        $now = time();

        $record = new \stdClass();
        $record->name = $data->name;
        $record->description = $data->description ?? '';
        $record->eventname = $data->eventname;
        $record->points = $data->points;
        $record->courseid = $data->courseid ?? null;
        $record->conditions = isset($data->conditions) ? json_encode($data->conditions) : null;
        $record->enabled = $data->enabled ?? 1;
        $record->maxawards = $data->maxawards ?? null;
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->createdby = $USER->id;

        return $DB->insert_record('local_points_rules', $record);
    }

    /**
     * Update a points rule.
     *
     * @param int $ruleid Rule ID
     * @param \stdClass $data Rule data
     * @return bool Success
     */
    public static function update_rule($ruleid, $data) {
        global $DB;

        $record = $DB->get_record('local_points_rules', ['id' => $ruleid], '*', MUST_EXIST);

        $record->name = $data->name ?? $record->name;
        $record->description = $data->description ?? $record->description;
        $record->eventname = $data->eventname ?? $record->eventname;
        $record->points = $data->points ?? $record->points;
        $record->courseid = array_key_exists('courseid', (array)$data) ? $data->courseid : $record->courseid;
        $record->conditions = isset($data->conditions) ? json_encode($data->conditions) : $record->conditions;
        $record->enabled = $data->enabled ?? $record->enabled;
        $record->maxawards = array_key_exists('maxawards', (array)$data) ? $data->maxawards : $record->maxawards;
        $record->timemodified = time();

        return $DB->update_record('local_points_rules', $record);
    }

    /**
     * Delete a points rule.
     *
     * @param int $ruleid Rule ID
     * @return bool Success
     */
    public static function delete_rule($ruleid) {
        global $DB;

        $DB->delete_records('local_points_rule_usage', ['ruleid' => $ruleid]);
        return $DB->delete_records('local_points_rules', ['id' => $ruleid]);
    }

    /**
     * Get all rules.
     *
     * @param int|null $courseid Filter by course (null for all)
     * @param bool $enabledonly Only enabled rules
     * @return array Rules
     */
    public static function get_rules($courseid = null, $enabledonly = false) {
        global $DB;

        $params = [];
        $where = [];

        if ($courseid !== null) {
            $where[] = '(courseid = :courseid OR courseid IS NULL)';
            $params['courseid'] = $courseid;
        }

        if ($enabledonly) {
            $where[] = 'enabled = 1';
        }

        $wheresql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        return $DB->get_records_sql(
            "SELECT * FROM {local_points_rules} $wheresql ORDER BY name",
            $params
        );
    }

    /**
     * Get rules for a specific event.
     *
     * @param string $eventname Event name
     * @param int|null $courseid Course ID
     * @return array Matching rules
     */
    public static function get_rules_for_event($eventname, $courseid = null) {
        global $DB;

        $params = ['eventname' => $eventname, 'enabled' => 1];

        if ($courseid !== null) {
            $sql = "SELECT * FROM {local_points_rules}
                    WHERE eventname = :eventname
                    AND enabled = :enabled
                    AND (courseid = :courseid OR courseid IS NULL)";
            $params['courseid'] = $courseid;
        } else {
            $sql = "SELECT * FROM {local_points_rules}
                    WHERE eventname = :eventname
                    AND enabled = :enabled
                    AND courseid IS NULL";
        }

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Check if rule can be applied to user.
     *
     * @param int $ruleid Rule ID
     * @param int $userid User ID
     * @return bool Can apply
     */
    public static function can_apply_rule($ruleid, $userid) {
        global $DB;

        $rule = $DB->get_record('local_points_rules', ['id' => $ruleid], '*', MUST_EXIST);

        // Check if rule has max awards limit.
        if ($rule->maxawards === null) {
            return true;
        }

        $usage = $DB->get_record('local_points_rule_usage', [
            'ruleid' => $ruleid,
            'userid' => $userid,
        ]);

        if (!$usage) {
            return true;
        }

        return $usage->count < $rule->maxawards;
    }

    /**
     * Increment rule usage for a user.
     *
     * @param int $ruleid Rule ID
     * @param int $userid User ID
     */
    private static function increment_rule_usage($ruleid, $userid) {
        global $DB;

        $now = time();

        $usage = $DB->get_record('local_points_rule_usage', [
            'ruleid' => $ruleid,
            'userid' => $userid,
        ]);

        if ($usage) {
            $usage->count++;
            $usage->timemodified = $now;
            $DB->update_record('local_points_rule_usage', $usage);
        } else {
            $usage = new \stdClass();
            $usage->ruleid = $ruleid;
            $usage->userid = $userid;
            $usage->count = 1;
            $usage->timemodified = $now;
            $DB->insert_record('local_points_rule_usage', $usage);
        }
    }

    /**
     * Get available events for rules.
     *
     * @return array Event options
     */
    public static function get_available_events() {
        return [
            '\core\event\course_module_completion_updated' => get_string('event_activity_completed', 'local_points'),
            '\core\event\course_completed' => get_string('event_course_completed', 'local_points'),
            '\core\event\user_graded' => get_string('event_user_graded', 'local_points'),
            '\mod_forum\event\discussion_created' => get_string('event_forum_discussion', 'local_points'),
            '\mod_forum\event\post_created' => get_string('event_forum_post', 'local_points'),
            '\mod_quiz\event\attempt_submitted' => get_string('event_quiz_submitted', 'local_points'),
            '\mod_assign\event\assessable_submitted' => get_string('event_assignment_submitted', 'local_points'),
            '\core\event\user_enrolment_created' => get_string('event_user_enrolled', 'local_points'),
            '\core\event\user_loggedin' => get_string('event_user_login', 'local_points'),
        ];
    }

    /**
     * Check conditions for a rule.
     *
     * @param \stdClass $rule The rule object
     * @param \core\event\base $event The event
     * @return bool Whether conditions are met
     */
    public static function check_conditions($rule, $event) {
        if (empty($rule->conditions)) {
            return true;
        }

        $conditions = json_decode($rule->conditions, true);
        if (empty($conditions)) {
            return true;
        }

        // Check minimum grade condition.
        if (isset($conditions['min_grade']) && method_exists($event, 'get_grade')) {
            $grade = $event->get_grade();
            if ($grade && $grade->finalgrade < $conditions['min_grade']) {
                return false;
            }
        }

        // Check completion state.
        if (isset($conditions['completion_state'])) {
            $data = $event->get_data();
            if (isset($data['other']['completionstate'])) {
                if ($data['other']['completionstate'] != $conditions['completion_state']) {
                    return false;
                }
            }
        }

        // Check specific activity type.
        if (isset($conditions['activity_type'])) {
            $data = $event->get_data();
            if (isset($data['other']['modulename'])) {
                if ($data['other']['modulename'] != $conditions['activity_type']) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get course leaderboard with user details.
     *
     * @param int $courseid Course ID
     * @param int $limit Limit
     * @return array Users with points
     */
    public static function get_course_leaderboard($courseid, $limit = 10) {
        global $DB;

        $sql = "SELECT p.userid, u.firstname, u.lastname, u.email,
                       u.picture, u.imagealt, u.firstnamephonetic,
                       u.lastnamephonetic, u.middlename, u.alternatename,
                       p.points
                FROM {local_points_user} p
                JOIN {user} u ON u.id = p.userid
                WHERE p.courseid = :courseid
                ORDER BY p.points DESC";

        return $DB->get_records_sql($sql, ['courseid' => $courseid], 0, $limit);
    }

    /**
     * Reset points for a user.
     *
     * @param int $userid User ID
     * @param int|null $courseid Course ID (null for all)
     * @return bool Success
     */
    public static function reset_points($userid, $courseid = null) {
        global $DB;

        $params = ['userid' => $userid];

        if ($courseid !== null) {
            $params['courseid'] = $courseid;
        }

        return $DB->delete_records('local_points_user', $params);
    }
}
