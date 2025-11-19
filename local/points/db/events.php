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
 * Event observers for local_points.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Course module completion.
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => 'local_points\observer::course_module_completion_updated',
    ],
    // Course completed.
    [
        'eventname' => '\core\event\course_completed',
        'callback' => 'local_points\observer::course_completed',
    ],
    // User graded.
    [
        'eventname' => '\core\event\user_graded',
        'callback' => 'local_points\observer::user_graded',
    ],
    // Discussion created in forum.
    [
        'eventname' => '\mod_forum\event\discussion_created',
        'callback' => 'local_points\observer::forum_discussion_created',
    ],
    // Post created in forum.
    [
        'eventname' => '\mod_forum\event\post_created',
        'callback' => 'local_points\observer::forum_post_created',
    ],
    // Quiz attempt submitted.
    [
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback' => 'local_points\observer::quiz_attempt_submitted',
    ],
    // Assignment submitted.
    [
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback' => 'local_points\observer::assignment_submitted',
    ],
    // User enrolled.
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => 'local_points\observer::user_enrolled',
    ],
    // User logged in.
    [
        'eventname' => '\core\event\user_loggedin',
        'callback' => 'local_points\observer::user_loggedin',
    ],
];
