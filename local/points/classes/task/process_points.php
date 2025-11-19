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
 * Scheduled task to process points.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_points\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to process pending points assignments.
 */
class process_points extends \core\task\scheduled_task {

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_process_points', 'local_points');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        mtrace('Starting points processing task...');

        // Get all enabled rules.
        $rules = $DB->get_records('local_points_rules', ['enabled' => 1]);

        if (empty($rules)) {
            mtrace('No active rules found.');
            return;
        }

        $processedcount = 0;
        $skippedcount = 0;

        foreach ($rules as $rule) {
            mtrace("Processing rule: {$rule->name} (ID: {$rule->id})");

            switch ($rule->eventname) {
                case '\core\event\course_module_completion_updated':
                    $result = $this->process_activity_completions($rule);
                    break;

                case '\core\event\course_completed':
                    $result = $this->process_course_completions($rule);
                    break;

                case '\core\event\user_graded':
                    $result = $this->process_grades($rule);
                    break;

                case '\mod_quiz\event\attempt_submitted':
                    $result = $this->process_quiz_attempts($rule);
                    break;

                case '\mod_assign\event\assessable_submitted':
                    $result = $this->process_assignment_submissions($rule);
                    break;

                case '\mod_forum\event\post_created':
                case '\mod_forum\event\discussion_created':
                    $result = $this->process_forum_posts($rule);
                    break;

                case 'local_points_program_completed':
                    $result = $this->process_program_completions($rule);
                    break;

                default:
                    $result = ['processed' => 0, 'skipped' => 0];
                    break;
            }

            $processedcount += $result['processed'];
            $skippedcount += $result['skipped'];

            mtrace("  - Processed: {$result['processed']}, Skipped: {$result['skipped']}");
        }

        mtrace("Task completed. Total processed: {$processedcount}, Total skipped: {$skippedcount}");
    }

    /**
     * Process activity completions.
     *
     * @param \stdClass $rule The rule
     * @return array Results
     */
    private function process_activity_completions($rule) {
        global $DB;

        $processed = 0;
        $skipped = 0;

        // Get conditions.
        $conditions = !empty($rule->conditions) ? json_decode($rule->conditions, true) : [];

        // Build query for completed activities.
        $params = ['completionstate' => COMPLETION_COMPLETE];
        $coursewhere = '';

        if ($rule->courseid) {
            $coursewhere = 'AND cm.course = :courseid';
            $params['courseid'] = $rule->courseid;
        }

        // Get last processed time for this rule.
        $lastrun = get_config('local_points', 'lastrun_rule_' . $rule->id);
        $lastrun = $lastrun ? $lastrun : 0;

        $sql = "SELECT cmc.id, cmc.userid, cmc.coursemoduleid, cmc.timemodified, cm.course as courseid, m.name as modulename
                FROM {course_modules_completion} cmc
                JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                JOIN {modules} m ON m.id = cm.module
                WHERE cmc.completionstate = :completionstate
                AND cmc.timemodified > :lastrun
                $coursewhere
                ORDER BY cmc.timemodified ASC";

        $params['lastrun'] = $lastrun;

        $completions = $DB->get_records_sql($sql, $params);

        foreach ($completions as $completion) {
            // Check activity type condition.
            if (!empty($conditions['activity_type']) && $completion->modulename != $conditions['activity_type']) {
                $skipped++;
                continue;
            }

            // Check if already awarded for this specific completion.
            if ($this->already_awarded($rule->id, $completion->userid, $completion->coursemoduleid)) {
                $skipped++;
                continue;
            }

            // Check max awards limit.
            if (!$this->can_award($rule, $completion->userid)) {
                $skipped++;
                continue;
            }

            // Determine course for points.
            $pointscourseid = $rule->courseid ?? $completion->courseid;

            // Award points.
            $success = \local_points\manager::award_points(
                $completion->userid,
                $rule->points,
                $rule->name,
                $pointscourseid,
                $rule->id,
                $completion->coursemoduleid
            );

            if ($success) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        // Update last run time.
        set_config('lastrun_rule_' . $rule->id, time(), 'local_points');

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    /**
     * Process course completions.
     *
     * @param \stdClass $rule The rule
     * @return array Results
     */
    private function process_course_completions($rule) {
        global $DB;

        $processed = 0;
        $skipped = 0;

        $params = [];
        $coursewhere = '';

        if ($rule->courseid) {
            $coursewhere = 'AND cc.course = :courseid';
            $params['courseid'] = $rule->courseid;
        }

        $lastrun = get_config('local_points', 'lastrun_rule_' . $rule->id);
        $lastrun = $lastrun ? $lastrun : 0;

        $sql = "SELECT cc.id, cc.userid, cc.course as courseid, cc.timecompleted
                FROM {course_completions} cc
                WHERE cc.timecompleted IS NOT NULL
                AND cc.timecompleted > :lastrun
                $coursewhere
                ORDER BY cc.timecompleted ASC";

        $params['lastrun'] = $lastrun;

        $completions = $DB->get_records_sql($sql, $params);

        foreach ($completions as $completion) {
            // Check if already awarded.
            if ($this->already_awarded($rule->id, $completion->userid, $completion->courseid)) {
                $skipped++;
                continue;
            }

            // Check max awards.
            if (!$this->can_award($rule, $completion->userid)) {
                $skipped++;
                continue;
            }

            $pointscourseid = $rule->courseid ?? $completion->courseid;

            $success = \local_points\manager::award_points(
                $completion->userid,
                $rule->points,
                $rule->name,
                $pointscourseid,
                $rule->id,
                $completion->courseid
            );

            if ($success) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        set_config('lastrun_rule_' . $rule->id, time(), 'local_points');

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    /**
     * Process grades.
     *
     * @param \stdClass $rule The rule
     * @return array Results
     */
    private function process_grades($rule) {
        global $DB;

        $processed = 0;
        $skipped = 0;

        $conditions = !empty($rule->conditions) ? json_decode($rule->conditions, true) : [];
        $mingrade = $conditions['min_grade'] ?? 0;

        $params = ['mingrade' => $mingrade];
        $coursewhere = '';

        if ($rule->courseid) {
            $coursewhere = 'AND gi.courseid = :courseid';
            $params['courseid'] = $rule->courseid;
        }

        $lastrun = get_config('local_points', 'lastrun_rule_' . $rule->id);
        $lastrun = $lastrun ? $lastrun : 0;

        $sql = "SELECT gg.id, gg.userid, gg.finalgrade, gg.timemodified, gi.courseid, gi.iteminstance
                FROM {grade_grades} gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                WHERE gg.finalgrade >= :mingrade
                AND gg.finalgrade IS NOT NULL
                AND gg.timemodified > :lastrun
                $coursewhere
                ORDER BY gg.timemodified ASC";

        $params['lastrun'] = $lastrun;

        $grades = $DB->get_records_sql($sql, $params);

        foreach ($grades as $grade) {
            // Check if already awarded for this grade item.
            $contextid = $grade->id; // Use grade ID as context.
            if ($this->already_awarded($rule->id, $grade->userid, $contextid)) {
                $skipped++;
                continue;
            }

            if (!$this->can_award($rule, $grade->userid)) {
                $skipped++;
                continue;
            }

            $pointscourseid = $rule->courseid ?? $grade->courseid;

            $success = \local_points\manager::award_points(
                $grade->userid,
                $rule->points,
                $rule->name,
                $pointscourseid,
                $rule->id,
                $contextid
            );

            if ($success) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        set_config('lastrun_rule_' . $rule->id, time(), 'local_points');

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    /**
     * Process quiz attempts.
     *
     * @param \stdClass $rule The rule
     * @return array Results
     */
    private function process_quiz_attempts($rule) {
        global $DB;

        $processed = 0;
        $skipped = 0;

        $params = ['state' => 'finished'];
        $coursewhere = '';

        if ($rule->courseid) {
            $coursewhere = 'AND q.course = :courseid';
            $params['courseid'] = $rule->courseid;
        }

        $lastrun = get_config('local_points', 'lastrun_rule_' . $rule->id);
        $lastrun = $lastrun ? $lastrun : 0;

        $sql = "SELECT qa.id, qa.userid, qa.quiz, qa.timefinish, q.course as courseid
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON q.id = qa.quiz
                WHERE qa.state = :state
                AND qa.timefinish > :lastrun
                $coursewhere
                ORDER BY qa.timefinish ASC";

        $params['lastrun'] = $lastrun;

        $attempts = $DB->get_records_sql($sql, $params);

        foreach ($attempts as $attempt) {
            if ($this->already_awarded($rule->id, $attempt->userid, $attempt->id)) {
                $skipped++;
                continue;
            }

            if (!$this->can_award($rule, $attempt->userid)) {
                $skipped++;
                continue;
            }

            $pointscourseid = $rule->courseid ?? $attempt->courseid;

            $success = \local_points\manager::award_points(
                $attempt->userid,
                $rule->points,
                $rule->name,
                $pointscourseid,
                $rule->id,
                $attempt->id
            );

            if ($success) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        set_config('lastrun_rule_' . $rule->id, time(), 'local_points');

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    /**
     * Process assignment submissions.
     *
     * @param \stdClass $rule The rule
     * @return array Results
     */
    private function process_assignment_submissions($rule) {
        global $DB;

        $processed = 0;
        $skipped = 0;

        $params = ['status' => 'submitted'];
        $coursewhere = '';

        if ($rule->courseid) {
            $coursewhere = 'AND a.course = :courseid';
            $params['courseid'] = $rule->courseid;
        }

        $lastrun = get_config('local_points', 'lastrun_rule_' . $rule->id);
        $lastrun = $lastrun ? $lastrun : 0;

        $sql = "SELECT asub.id, asub.userid, asub.assignment, asub.timemodified, a.course as courseid
                FROM {assign_submission} asub
                JOIN {assign} a ON a.id = asub.assignment
                WHERE asub.status = :status
                AND asub.timemodified > :lastrun
                $coursewhere
                ORDER BY asub.timemodified ASC";

        $params['lastrun'] = $lastrun;

        $submissions = $DB->get_records_sql($sql, $params);

        foreach ($submissions as $submission) {
            if ($this->already_awarded($rule->id, $submission->userid, $submission->id)) {
                $skipped++;
                continue;
            }

            if (!$this->can_award($rule, $submission->userid)) {
                $skipped++;
                continue;
            }

            $pointscourseid = $rule->courseid ?? $submission->courseid;

            $success = \local_points\manager::award_points(
                $submission->userid,
                $rule->points,
                $rule->name,
                $pointscourseid,
                $rule->id,
                $submission->id
            );

            if ($success) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        set_config('lastrun_rule_' . $rule->id, time(), 'local_points');

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    /**
     * Process forum posts.
     *
     * @param \stdClass $rule The rule
     * @return array Results
     */
    private function process_forum_posts($rule) {
        global $DB;

        $processed = 0;
        $skipped = 0;

        $params = [];
        $coursewhere = '';

        if ($rule->courseid) {
            $coursewhere = 'AND f.course = :courseid';
            $params['courseid'] = $rule->courseid;
        }

        $lastrun = get_config('local_points', 'lastrun_rule_' . $rule->id);
        $lastrun = $lastrun ? $lastrun : 0;

        $sql = "SELECT fp.id, fp.userid, fp.created, f.course as courseid
                FROM {forum_posts} fp
                JOIN {forum_discussions} fd ON fd.id = fp.discussion
                JOIN {forum} f ON f.id = fd.forum
                WHERE fp.created > :lastrun
                $coursewhere
                ORDER BY fp.created ASC";

        $params['lastrun'] = $lastrun;

        $posts = $DB->get_records_sql($sql, $params);

        foreach ($posts as $post) {
            if ($this->already_awarded($rule->id, $post->userid, $post->id)) {
                $skipped++;
                continue;
            }

            if (!$this->can_award($rule, $post->userid)) {
                $skipped++;
                continue;
            }

            $pointscourseid = $rule->courseid ?? $post->courseid;

            $success = \local_points\manager::award_points(
                $post->userid,
                $rule->points,
                $rule->name,
                $pointscourseid,
                $rule->id,
                $post->id
            );

            if ($success) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        set_config('lastrun_rule_' . $rule->id, time(), 'local_points');

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    /**
     * Process program completions.
     *
     * @param \stdClass $rule The rule
     * @return array Results
     */
    private function process_program_completions($rule) {
        global $DB;

        $processed = 0;
        $skipped = 0;

        // Check if local_programas table exists.
        if (!$DB->get_manager()->table_exists('local_programas_usuarios')) {
            mtrace('  - Table local_programas_usuarios does not exist, skipping.');
            return ['processed' => $processed, 'skipped' => $skipped];
        }

        $params = ['completado' => 1];
        $programwhere = '';

        // Filter by specific program if set in rule.
        if (!empty($rule->programid)) {
            $programwhere = 'AND pu.programaid = :programid';
            $params['programid'] = $rule->programid;
        }

        $lastrun = get_config('local_points', 'lastrun_rule_' . $rule->id);
        $lastrun = $lastrun ? $lastrun : 0;

        $sql = "SELECT pu.id, pu.userid, pu.programaid, pu.fechacompletado, p.nombre as programname
                FROM {local_programas_usuarios} pu
                JOIN {local_programas} p ON p.id = pu.programaid
                WHERE pu.completado = :completado
                AND pu.fechacompletado > :lastrun
                $programwhere
                ORDER BY pu.fechacompletado ASC";

        $params['lastrun'] = $lastrun;

        $completions = $DB->get_records_sql($sql, $params);

        foreach ($completions as $completion) {
            // Use programaid as contextid for duplicate checking.
            if ($this->already_awarded($rule->id, $completion->userid, $completion->programaid)) {
                $skipped++;
                continue;
            }

            if (!$this->can_award($rule, $completion->userid)) {
                $skipped++;
                continue;
            }

            // Award points (globally since programs span multiple courses).
            $success = \local_points\manager::award_points(
                $completion->userid,
                $rule->points,
                $rule->name . ' - ' . $completion->programname,
                null, // Global points for program completion.
                $rule->id,
                $completion->programaid
            );

            if ($success) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        set_config('lastrun_rule_' . $rule->id, time(), 'local_points');

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    /**
     * Check if points were already awarded for this context.
     *
     * @param int $ruleid Rule ID
     * @param int $userid User ID
     * @param int $contextid Context ID
     * @return bool
     */
    private function already_awarded($ruleid, $userid, $contextid) {
        global $DB;

        return $DB->record_exists('local_points_history', [
            'ruleid' => $ruleid,
            'userid' => $userid,
            'contextid' => $contextid,
        ]);
    }

    /**
     * Check if rule can award more points to user.
     *
     * @param \stdClass $rule The rule
     * @param int $userid User ID
     * @return bool
     */
    private function can_award($rule, $userid) {
        global $DB;

        if ($rule->maxawards === null) {
            return true;
        }

        $usage = $DB->get_record('local_points_rule_usage', [
            'ruleid' => $rule->id,
            'userid' => $userid,
        ]);

        if (!$usage) {
            return true;
        }

        return $usage->count < $rule->maxawards;
    }
}
