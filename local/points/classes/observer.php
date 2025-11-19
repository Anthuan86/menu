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
 * Event observer for local_points.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_points;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class to handle Moodle events.
 */
class observer {

    /**
     * Process an event and award points based on matching rules.
     *
     * @param \core\event\base $event The event
     * @param string $eventname Full event name
     */
    private static function process_event($event, $eventname) {
        $data = $event->get_data();
        $userid = $data['userid'] ?? $data['relateduserid'] ?? null;
        $courseid = $data['courseid'] ?? null;

        if (!$userid) {
            return;
        }

        // Don't process events for guest users.
        if (isguestuser($userid)) {
            return;
        }

        // Get matching rules.
        $rules = manager::get_rules_for_event($eventname, $courseid);

        foreach ($rules as $rule) {
            // Check if rule can be applied.
            if (!manager::can_apply_rule($rule->id, $userid)) {
                continue;
            }

            // Check conditions.
            if (!manager::check_conditions($rule, $event)) {
                continue;
            }

            // Determine if points should be global or course-specific.
            $pointscourseid = $rule->courseid ?? $courseid;

            // Award points.
            manager::award_points(
                $userid,
                $rule->points,
                $rule->name,
                $pointscourseid,
                $rule->id,
                $data['contextid'] ?? null
            );
        }
    }

    /**
     * Handle course module completion.
     *
     * @param \core\event\course_module_completion_updated $event
     */
    public static function course_module_completion_updated(\core\event\course_module_completion_updated $event) {
        $data = $event->get_data();

        // Only award points for completion (not for un-completion).
        if (isset($data['other']['completionstate']) && $data['other']['completionstate'] == COMPLETION_COMPLETE) {
            self::process_event($event, '\core\event\course_module_completion_updated');
        }
    }

    /**
     * Handle course completion.
     *
     * @param \core\event\course_completed $event
     */
    public static function course_completed(\core\event\course_completed $event) {
        self::process_event($event, '\core\event\course_completed');
    }

    /**
     * Handle user graded.
     *
     * @param \core\event\user_graded $event
     */
    public static function user_graded(\core\event\user_graded $event) {
        self::process_event($event, '\core\event\user_graded');
    }

    /**
     * Handle forum discussion created.
     *
     * @param \mod_forum\event\discussion_created $event
     */
    public static function forum_discussion_created(\mod_forum\event\discussion_created $event) {
        self::process_event($event, '\mod_forum\event\discussion_created');
    }

    /**
     * Handle forum post created.
     *
     * @param \mod_forum\event\post_created $event
     */
    public static function forum_post_created(\mod_forum\event\post_created $event) {
        self::process_event($event, '\mod_forum\event\post_created');
    }

    /**
     * Handle quiz attempt submitted.
     *
     * @param \mod_quiz\event\attempt_submitted $event
     */
    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        self::process_event($event, '\mod_quiz\event\attempt_submitted');
    }

    /**
     * Handle assignment submitted.
     *
     * @param \mod_assign\event\assessable_submitted $event
     */
    public static function assignment_submitted(\mod_assign\event\assessable_submitted $event) {
        self::process_event($event, '\mod_assign\event\assessable_submitted');
    }

    /**
     * Handle user enrolled.
     *
     * @param \core\event\user_enrolment_created $event
     */
    public static function user_enrolled(\core\event\user_enrolment_created $event) {
        self::process_event($event, '\core\event\user_enrolment_created');
    }

    /**
     * Handle user login.
     *
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {
        self::process_event($event, '\core\event\user_loggedin');
    }
}
