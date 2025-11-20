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
 * Process reward redemption.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

require_login();

$context = context_system::instance();

// Get reward.
$reward = $DB->get_record('local_points_rewards', ['id' => $id], '*', MUST_EXIST);

// Check availability.
$now = time();
$errors = [];

if (!$reward->enabled) {
    $errors[] = get_string('rewardnotenabled', 'local_points');
}
if ($reward->availablefrom && $reward->availablefrom > $now) {
    $errors[] = get_string('rewardnotavailableyet', 'local_points');
}
if ($reward->availableuntil && $reward->availableuntil < $now) {
    $errors[] = get_string('rewardexpired', 'local_points');
}
if ($reward->quantity !== null && $reward->quantity <= 0) {
    $errors[] = get_string('rewardoutofstock', 'local_points');
}

// Get user's points.
$userpointsrecord = $DB->get_record('local_points_user', [
    'userid' => $USER->id,
    'courseid' => null
]);
$userpoints = $userpointsrecord ? $userpointsrecord->points : 0;

if ($userpoints < $reward->cost) {
    $errors[] = get_string('insufficientpoints', 'local_points');
}

// Setup page.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/points/store/redeem.php', ['id' => $id]));
$PAGE->set_title(get_string('redeemreward', 'local_points'));
$PAGE->set_heading(get_string('redeemreward', 'local_points'));
$PAGE->set_pagelayout('standard');

// Process redemption.
if ($confirm && confirm_sesskey() && empty($errors)) {
    // Start transaction.
    $transaction = $DB->start_delegated_transaction();

    try {
        // Create redemption record.
        $redemption = new stdClass();
        $redemption->userid = $USER->id;
        $redemption->rewardid = $reward->id;
        $redemption->points = $reward->cost;
        $redemption->status = 'pending';
        $redemption->timecreated = time();
        $redemption->timemodified = time();
        $redemptionid = $DB->insert_record('local_points_redemptions', $redemption);

        // Deduct points from user.
        if ($userpointsrecord) {
            $userpointsrecord->points -= $reward->cost;
            $userpointsrecord->timemodified = time();
            $DB->update_record('local_points_user', $userpointsrecord);
        }

        // Add to points history (negative).
        $history = new stdClass();
        $history->userid = $USER->id;
        $history->courseid = null;
        $history->points = -$reward->cost;
        $history->reason = get_string('redeemedreward', 'local_points', format_string($reward->name));
        $history->contextid = $redemptionid;
        $history->timecreated = time();
        $DB->insert_record('local_points_history', $history);

        // Decrease stock if applicable.
        if ($reward->quantity !== null) {
            $reward->quantity--;
            $reward->timemodified = time();
            $DB->update_record('local_points_rewards', $reward);
        }

        $transaction->allow_commit();

        // Redirect to confirmation.
        redirect(new moodle_url('/local/points/store/confirmation.php', ['id' => $redemptionid]));

    } catch (Exception $e) {
        $transaction->rollback($e);
        $errors[] = get_string('redemptionerror', 'local_points');
    }
}

echo $OUTPUT->header();

// Show errors if any.
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo $OUTPUT->notification($error, 'error');
    }
    echo html_writer::link(
        new moodle_url('/local/points/store/index.php'),
        get_string('backtostore', 'local_points'),
        ['class' => 'btn btn-secondary mt-3']
    );
    echo $OUTPUT->footer();
    exit;
}

// Confirmation dialog.
echo html_writer::start_div('card');
echo html_writer::start_div('card-body');

echo html_writer::tag('h4', get_string('confirmredemption', 'local_points'), ['class' => 'card-title']);

echo html_writer::start_tag('dl', ['class' => 'row']);

echo html_writer::tag('dt', get_string('reward', 'local_points'), ['class' => 'col-sm-4']);
echo html_writer::tag('dd', format_string($reward->name), ['class' => 'col-sm-8']);

echo html_writer::tag('dt', get_string('cost', 'local_points'), ['class' => 'col-sm-4']);
echo html_writer::tag('dd', $reward->cost . ' ' . get_string('points', 'local_points'), ['class' => 'col-sm-8']);

echo html_writer::tag('dt', get_string('yourpoints', 'local_points'), ['class' => 'col-sm-4']);
echo html_writer::tag('dd', $userpoints, ['class' => 'col-sm-8']);

echo html_writer::tag('dt', get_string('remaining', 'local_points'), ['class' => 'col-sm-4']);
echo html_writer::tag('dd', ($userpoints - $reward->cost), ['class' => 'col-sm-8 font-weight-bold']);

echo html_writer::end_tag('dl');

echo html_writer::tag('div',
    get_string('confirmredemptionmessage', 'local_points'),
    ['class' => 'alert alert-warning']
);

// Confirm/Cancel buttons.
$confirmurl = new moodle_url('/local/points/store/redeem.php', [
    'id' => $id,
    'confirm' => 1,
    'sesskey' => sesskey()
]);
$cancelurl = new moodle_url('/local/points/store/detail.php', ['id' => $id]);

echo html_writer::start_div('mt-3');
echo html_writer::link($confirmurl, get_string('confirm'), ['class' => 'btn btn-success mr-2']);
echo html_writer::link($cancelurl, get_string('cancel'), ['class' => 'btn btn-secondary']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
