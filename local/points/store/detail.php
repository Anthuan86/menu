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
 * Reward detail page.
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

// Get reward.
$reward = $DB->get_record('local_points_rewards', ['id' => $id], '*', MUST_EXIST);

// Check if available.
$now = time();
$available = $reward->enabled;

if ($reward->availablefrom && $reward->availablefrom > $now) {
    $available = false;
}
if ($reward->availableuntil && $reward->availableuntil < $now) {
    $available = false;
}
if ($reward->quantity !== null && $reward->quantity <= 0) {
    $available = false;
}

// Setup page.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/points/store/detail.php', ['id' => $id]));
$PAGE->set_title(format_string($reward->name));
$PAGE->set_heading(format_string($reward->name));
$PAGE->set_pagelayout('standard');

// Get user's points.
$userpoints = $DB->get_field('local_points_user', 'points', [
    'userid' => $USER->id,
    'courseid' => null
]);
$userpoints = $userpoints ? $userpoints : 0;

echo $OUTPUT->header();

echo html_writer::start_div('row');

// Image column.
echo html_writer::start_div('col-md-5 mb-4');
if (!empty($reward->image)) {
    $imageurl = moodle_url::make_pluginfile_url(
        $context->id,
        'local_points',
        'rewardimage',
        $reward->id,
        '/',
        $reward->image
    );
    echo html_writer::img($imageurl, format_string($reward->name), [
        'class' => 'img-fluid rounded',
        'style' => 'max-height: 400px; width: 100%; object-fit: cover;'
    ]);
} else {
    echo html_writer::start_div('bg-light d-flex align-items-center justify-content-center rounded', [
        'style' => 'height: 300px;'
    ]);
    echo html_writer::tag('i', '', ['class' => 'fa fa-gift fa-5x text-muted']);
    echo html_writer::end_div();
}
echo html_writer::end_div();

// Details column.
echo html_writer::start_div('col-md-7');

// Category.
if ($reward->categoryid) {
    $category = $DB->get_record('local_points_reward_categories', ['id' => $reward->categoryid]);
    if ($category) {
        echo html_writer::tag('span', format_string($category->name), ['class' => 'badge badge-secondary mb-2']);
    }
}

// Cost.
echo html_writer::start_div('mb-3');
echo html_writer::tag('span', $reward->cost, ['class' => 'h2 text-primary font-weight-bold']);
echo html_writer::tag('span', ' ' . get_string('points', 'local_points'), ['class' => 'h5']);
echo html_writer::end_div();

// Description.
if (!empty($reward->description)) {
    echo html_writer::start_div('mb-3');
    echo format_text($reward->description, FORMAT_HTML);
    echo html_writer::end_div();
}

// Stock info.
if ($reward->quantity !== null) {
    echo html_writer::tag('p',
        get_string('stockremaining', 'local_points', $reward->quantity),
        ['class' => 'text-muted']
    );
}

// Availability dates.
if ($reward->availablefrom || $reward->availableuntil) {
    echo html_writer::start_div('alert alert-info');
    if ($reward->availablefrom) {
        echo html_writer::tag('p',
            get_string('availablefrom', 'local_points') . ': ' .
            userdate($reward->availablefrom, get_string('strftimedatetime', 'langconfig')),
            ['class' => 'mb-1']
        );
    }
    if ($reward->availableuntil) {
        echo html_writer::tag('p',
            get_string('availableuntil', 'local_points') . ': ' .
            userdate($reward->availableuntil, get_string('strftimedatetime', 'langconfig')),
            ['class' => 'mb-0']
        );
    }
    echo html_writer::end_div();
}

// Action button.
echo html_writer::start_div('mt-4');

if (!$available) {
    echo html_writer::tag('button',
        get_string('notavailable', 'local_points'),
        ['class' => 'btn btn-secondary btn-lg', 'disabled' => 'disabled']
    );
} else if ($userpoints < $reward->cost) {
    $pointsneeded = $reward->cost - $userpoints;
    echo html_writer::tag('div',
        get_string('needmorepoints', 'local_points', $pointsneeded),
        ['class' => 'alert alert-warning']
    );
    echo html_writer::tag('p',
        get_string('yourpoints', 'local_points') . ': ' . $userpoints,
        ['class' => 'text-muted']
    );
} else {
    echo html_writer::link(
        new moodle_url('/local/points/store/redeem.php', ['id' => $reward->id]),
        get_string('redeemnow', 'local_points'),
        ['class' => 'btn btn-success btn-lg']
    );
    echo html_writer::tag('p',
        get_string('yourpoints', 'local_points') . ': ' . $userpoints,
        ['class' => 'text-muted mt-2']
    );
}

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Back link.
echo html_writer::start_div('mt-4');
echo html_writer::link(
    new moodle_url('/local/points/store/index.php'),
    get_string('backtostore', 'local_points'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo $OUTPUT->footer();
