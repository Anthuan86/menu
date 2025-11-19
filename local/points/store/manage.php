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
 * Manage rewards.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/points:managerules', $context);

// Setup page.
$PAGE->set_context($context);
$url = new moodle_url('/local/points/store/manage.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('managerewards', 'local_points'));
$PAGE->set_heading(get_string('managerewards', 'local_points'));
$PAGE->set_pagelayout('admin');

// Handle delete action.
if ($action === 'delete' && $id && confirm_sesskey()) {
    $reward = $DB->get_record('local_points_rewards', ['id' => $id], '*', MUST_EXIST);

    // Check if reward has been redeemed.
    $redemptions = $DB->count_records('local_points_redemptions', ['rewardid' => $id]);
    if ($redemptions > 0) {
        // Just disable instead of delete.
        $reward->enabled = 0;
        $reward->timemodified = time();
        $DB->update_record('local_points_rewards', $reward);
        redirect($url, get_string('rewarddisabled', 'local_points'), null, \core\output\notification::NOTIFY_WARNING);
    } else {
        $DB->delete_records('local_points_rewards', ['id' => $id]);
        redirect($url, get_string('rewarddeleted', 'local_points'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Handle toggle enabled.
if ($action === 'toggle' && $id && confirm_sesskey()) {
    $reward = $DB->get_record('local_points_rewards', ['id' => $id], '*', MUST_EXIST);
    $reward->enabled = $reward->enabled ? 0 : 1;
    $reward->timemodified = time();
    $DB->update_record('local_points_rewards', $reward);
    redirect($url);
}

echo $OUTPUT->header();

// Action buttons.
echo html_writer::start_div('mb-4');
echo html_writer::link(
    new moodle_url('/local/points/store/edit_reward.php'),
    get_string('addreward', 'local_points'),
    ['class' => 'btn btn-primary mr-2']
);
echo html_writer::link(
    new moodle_url('/local/points/store/manage_categories.php'),
    get_string('managecategories', 'local_points'),
    ['class' => 'btn btn-secondary mr-2']
);
echo html_writer::link(
    new moodle_url('/local/points/store/manage_redemptions.php'),
    get_string('manageredemptions', 'local_points'),
    ['class' => 'btn btn-info']
);
echo html_writer::end_div();

// Get all rewards.
$sql = "SELECT r.*, c.name as categoryname,
               (SELECT COUNT(*) FROM {local_points_redemptions} WHERE rewardid = r.id) as redemptioncount
        FROM {local_points_rewards} r
        LEFT JOIN {local_points_reward_categories} c ON c.id = r.categoryid
        ORDER BY r.name ASC";

$rewards = $DB->get_records_sql($sql);

if (empty($rewards)) {
    echo $OUTPUT->notification(get_string('norewards', 'local_points'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('name'),
        get_string('category', 'local_points'),
        get_string('cost', 'local_points'),
        get_string('stock', 'local_points'),
        get_string('redemptions', 'local_points'),
        get_string('status', 'local_points'),
        get_string('actions', 'local_points'),
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($rewards as $reward) {
        $row = [];

        // Name.
        $row[] = format_string($reward->name);

        // Category.
        $row[] = $reward->categoryname ? format_string($reward->categoryname) : '-';

        // Cost.
        $row[] = $reward->cost;

        // Stock.
        $row[] = $reward->quantity !== null ? $reward->quantity : get_string('unlimited', 'local_points');

        // Redemptions.
        $row[] = $reward->redemptioncount;

        // Status.
        if ($reward->enabled) {
            $status = html_writer::tag('span', get_string('active', 'local_points'), ['class' => 'badge badge-success']);
        } else {
            $status = html_writer::tag('span', get_string('inactive', 'local_points'), ['class' => 'badge badge-secondary']);
        }
        $row[] = $status;

        // Actions.
        $actions = [];

        // Edit.
        $actions[] = html_writer::link(
            new moodle_url('/local/points/store/edit_reward.php', ['id' => $reward->id]),
            $OUTPUT->pix_icon('t/edit', get_string('edit'))
        );

        // Toggle.
        $toggleicon = $reward->enabled ? 't/hide' : 't/show';
        $togglestr = $reward->enabled ? get_string('disable') : get_string('enable');
        $actions[] = html_writer::link(
            new moodle_url($url, ['action' => 'toggle', 'id' => $reward->id, 'sesskey' => sesskey()]),
            $OUTPUT->pix_icon($toggleicon, $togglestr)
        );

        // Delete.
        $actions[] = html_writer::link(
            new moodle_url($url, ['action' => 'delete', 'id' => $reward->id, 'sesskey' => sesskey()]),
            $OUTPUT->pix_icon('t/delete', get_string('delete')),
            ['onclick' => 'return confirm("' . get_string('confirmdelete', 'local_points') . '");']
        );

        $row[] = implode(' ', $actions);

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

// Back link.
echo html_writer::start_div('mt-4');
echo html_writer::link(
    new moodle_url('/local/points/manage.php'),
    get_string('backtooverview', 'local_points'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo $OUTPUT->footer();
