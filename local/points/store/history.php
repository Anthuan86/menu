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
 * User's redemption history.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;

require_login();

$context = context_system::instance();
require_capability('local/points:viewown', $context);

// Setup page.
$PAGE->set_context($context);
$url = new moodle_url('/local/points/store/history.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('myredemptions', 'local_points'));
$PAGE->set_heading(get_string('myredemptions', 'local_points'));
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

// Get redemptions.
$total = $DB->count_records('local_points_redemptions', ['userid' => $USER->id]);

$sql = "SELECT r.*, rw.name as rewardname
        FROM {local_points_redemptions} r
        JOIN {local_points_rewards} rw ON rw.id = r.rewardid
        WHERE r.userid = :userid
        ORDER BY r.timecreated DESC";

$redemptions = $DB->get_records_sql($sql, ['userid' => $USER->id], $page * $perpage, $perpage);

if (empty($redemptions)) {
    echo $OUTPUT->notification(get_string('noredemptions', 'local_points'), 'info');
} else {
    // Table.
    $table = new html_table();
    $table->head = [
        get_string('date', 'local_points'),
        get_string('reward', 'local_points'),
        get_string('pointsspent', 'local_points'),
        get_string('status', 'local_points'),
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($redemptions as $redemption) {
        $row = [];

        // Date.
        $row[] = userdate($redemption->timecreated, get_string('strftimedatetime', 'langconfig'));

        // Reward name.
        $row[] = format_string($redemption->rewardname);

        // Points.
        $row[] = $redemption->points;

        // Status with badge.
        $statusclasses = [
            'pending' => 'badge-warning',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            'delivered' => 'badge-info'
        ];
        $badgeclass = isset($statusclasses[$redemption->status]) ? $statusclasses[$redemption->status] : 'badge-secondary';
        $statustext = get_string('status_' . $redemption->status, 'local_points');
        $row[] = html_writer::tag('span', $statustext, ['class' => 'badge ' . $badgeclass]);

        $table->data[] = $row;
    }

    echo html_writer::table($table);

    // Pagination.
    echo $OUTPUT->paging_bar($total, $page, $perpage, $url);
}

// Navigation links.
echo html_writer::start_div('mt-4');
echo html_writer::link(
    new moodle_url('/local/points/store/index.php'),
    get_string('backtostore', 'local_points'),
    ['class' => 'btn btn-primary']
);
echo ' ';
echo html_writer::link(
    new moodle_url('/local/points/view.php'),
    get_string('mypoints', 'local_points'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo $OUTPUT->footer();
