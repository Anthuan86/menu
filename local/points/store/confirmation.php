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
 * Redemption confirmation page.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/points:viewown', $context);

// Get redemption.
$redemption = $DB->get_record('local_points_redemptions', ['id' => $id, 'userid' => $USER->id], '*', MUST_EXIST);
$reward = $DB->get_record('local_points_rewards', ['id' => $redemption->rewardid], '*', MUST_EXIST);

// Setup page.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/points/store/confirmation.php', ['id' => $id]));
$PAGE->set_title(get_string('redemptionconfirmed', 'local_points'));
$PAGE->set_heading(get_string('redemptionconfirmed', 'local_points'));
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

// Success message.
echo html_writer::start_div('text-center');

echo html_writer::tag('div',
    html_writer::tag('i', '', ['class' => 'fa fa-check-circle fa-5x text-success']),
    ['class' => 'mb-4']
);

echo html_writer::tag('h3', get_string('redemptionsuccess', 'local_points'), ['class' => 'mb-3']);

echo html_writer::start_div('card mx-auto', ['style' => 'max-width: 500px;']);
echo html_writer::start_div('card-body');

echo html_writer::start_tag('dl', ['class' => 'row mb-0']);

echo html_writer::tag('dt', get_string('reward', 'local_points'), ['class' => 'col-sm-5']);
echo html_writer::tag('dd', format_string($reward->name), ['class' => 'col-sm-7']);

echo html_writer::tag('dt', get_string('pointsspent', 'local_points'), ['class' => 'col-sm-5']);
echo html_writer::tag('dd', $redemption->points, ['class' => 'col-sm-7']);

echo html_writer::tag('dt', get_string('status', 'local_points'), ['class' => 'col-sm-5']);
$statusbadge = html_writer::tag('span',
    get_string('status_' . $redemption->status, 'local_points'),
    ['class' => 'badge badge-warning']
);
echo html_writer::tag('dd', $statusbadge, ['class' => 'col-sm-7']);

echo html_writer::tag('dt', get_string('date', 'local_points'), ['class' => 'col-sm-5']);
echo html_writer::tag('dd',
    userdate($redemption->timecreated, get_string('strftimedatetime', 'langconfig')),
    ['class' => 'col-sm-7']
);

echo html_writer::end_tag('dl');

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::tag('p',
    get_string('redemptionpendingmessage', 'local_points'),
    ['class' => 'mt-4 text-muted']
);

// Navigation links.
echo html_writer::start_div('mt-4');
echo html_writer::link(
    new moodle_url('/local/points/store/history.php'),
    get_string('myredemptions', 'local_points'),
    ['class' => 'btn btn-primary mr-2']
);
echo html_writer::link(
    new moodle_url('/local/points/store/index.php'),
    get_string('backtostore', 'local_points'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo html_writer::end_div();

echo $OUTPUT->footer();
