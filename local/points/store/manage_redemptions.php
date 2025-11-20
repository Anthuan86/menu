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
 * Manage redemptions.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$status = optional_param('status', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 50;

require_login();

$context = context_system::instance();
require_capability('local/points:managerules', $context);

// Setup page.
$PAGE->set_context($context);
$url = new moodle_url('/local/points/store/manage_redemptions.php', ['status' => $status]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('manageredemptions', 'local_points'));
$PAGE->set_heading(get_string('manageredemptions', 'local_points'));
$PAGE->set_pagelayout('admin');

// Handle status change.
if ($action && $id && confirm_sesskey()) {
    $redemption = $DB->get_record('local_points_redemptions', ['id' => $id], '*', MUST_EXIST);

    $validactions = ['approve', 'reject', 'deliver'];
    if (in_array($action, $validactions)) {
        $newstatus = '';
        switch ($action) {
            case 'approve':
                $newstatus = 'approved';
                break;
            case 'reject':
                $newstatus = 'rejected';
                // Refund points.
                $userpoints = $DB->get_record_sql(
                    'SELECT * FROM {local_points_user} WHERE userid = :userid AND courseid IS NULL',
                    ['userid' => $redemption->userid]
                );
                if ($userpoints) {
                    $userpoints->points += $redemption->points;
                    $userpoints->timemodified = time();
                    $DB->update_record('local_points_user', $userpoints);

                    // Add refund to history.
                    $history = new stdClass();
                    $history->userid = $redemption->userid;
                    $history->courseid = null;
                    $history->points = $redemption->points;
                    $history->reason = get_string('redemptionrejectedrefund', 'local_points');
                    $history->timecreated = time();
                    $history->awardedby = $USER->id;
                    $DB->insert_record('local_points_history', $history);
                }
                break;
            case 'deliver':
                $newstatus = 'delivered';
                break;
        }

        if ($newstatus) {
            $redemption->status = $newstatus;
            $redemption->timemodified = time();
            $redemption->approvedby = $USER->id;
            $redemption->timeapproved = time();
            $DB->update_record('local_points_redemptions', $redemption);
        }

        redirect($url, get_string('statusupdated', 'local_points'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();

// Status filter tabs.
$statuses = ['', 'pending', 'approved', 'rejected', 'delivered'];
echo html_writer::start_tag('ul', ['class' => 'nav nav-tabs mb-4']);

foreach ($statuses as $s) {
    $activeclass = $status === $s ? ' active' : '';
    $label = $s ? get_string('status_' . $s, 'local_points') : get_string('all');

    echo html_writer::start_tag('li', ['class' => 'nav-item']);
    echo html_writer::link(
        new moodle_url('/local/points/store/manage_redemptions.php', ['status' => $s]),
        $label,
        ['class' => 'nav-link' . $activeclass]
    );
    echo html_writer::end_tag('li');
}

echo html_writer::end_tag('ul');

// Get redemptions.
$where = '';
$params = [];

if ($status) {
    $where = 'r.status = :status';
    $params['status'] = $status;
}

$countsql = "SELECT COUNT(*) FROM {local_points_redemptions} r " . ($where ? "WHERE $where" : "");
$total = $DB->count_records_sql($countsql, $params);

$sql = "SELECT r.*, rw.name as rewardname, u.firstname, u.lastname, u.email
        FROM {local_points_redemptions} r
        JOIN {local_points_rewards} rw ON rw.id = r.rewardid
        JOIN {user} u ON u.id = r.userid
        " . ($where ? "WHERE $where" : "") . "
        ORDER BY r.timecreated DESC";

$redemptions = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

if (empty($redemptions)) {
    echo $OUTPUT->notification(get_string('noredemptions', 'local_points'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('date', 'local_points'),
        get_string('user', 'local_points'),
        get_string('reward', 'local_points'),
        get_string('points', 'local_points'),
        get_string('status', 'local_points'),
        get_string('actions', 'local_points'),
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($redemptions as $redemption) {
        $row = [];

        // Date.
        $row[] = userdate($redemption->timecreated, get_string('strftimedatetime', 'langconfig'));

        // User.
        $userurl = new moodle_url('/user/profile.php', ['id' => $redemption->userid]);
        $row[] = html_writer::link($userurl, fullname($redemption));

        // Reward.
        $row[] = format_string($redemption->rewardname);

        // Points.
        $row[] = $redemption->points;

        // Status badge.
        $statusclasses = [
            'pending' => 'badge-warning',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            'delivered' => 'badge-info'
        ];
        $badgeclass = isset($statusclasses[$redemption->status]) ? $statusclasses[$redemption->status] : 'badge-secondary';
        $row[] = html_writer::tag('span',
            get_string('status_' . $redemption->status, 'local_points'),
            ['class' => 'badge ' . $badgeclass]
        );

        // Actions.
        $actions = [];

        if ($redemption->status === 'pending') {
            $actions[] = html_writer::link(
                new moodle_url($url, ['action' => 'approve', 'id' => $redemption->id, 'sesskey' => sesskey()]),
                get_string('approve', 'local_points'),
                ['class' => 'btn btn-sm btn-success']
            );
            $actions[] = html_writer::link(
                new moodle_url($url, ['action' => 'reject', 'id' => $redemption->id, 'sesskey' => sesskey()]),
                get_string('reject', 'local_points'),
                ['class' => 'btn btn-sm btn-danger']
            );
        } else if ($redemption->status === 'approved') {
            $actions[] = html_writer::link(
                new moodle_url($url, ['action' => 'deliver', 'id' => $redemption->id, 'sesskey' => sesskey()]),
                get_string('markdelivered', 'local_points'),
                ['class' => 'btn btn-sm btn-info']
            );
        }

        $row[] = implode(' ', $actions);

        $table->data[] = $row;
    }

    echo html_writer::table($table);

    // Pagination.
    echo $OUTPUT->paging_bar($total, $page, $perpage, $url);
}

// Back link.
echo html_writer::start_div('mt-4');
echo html_writer::link(
    new moodle_url('/local/points/store/manage.php'),
    get_string('managerewards', 'local_points'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo $OUTPUT->footer();
