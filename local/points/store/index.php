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
 * Points store - Main rewards catalog.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$categoryid = optional_param('category', 0, PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/points:viewown', $context);

// Setup page.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/points/store/index.php', ['category' => $categoryid]));
$PAGE->set_title(get_string('store', 'local_points'));
$PAGE->set_heading(get_string('store', 'local_points'));
$PAGE->set_pagelayout('standard');

// Get user's points.
$userpoints = $DB->get_field('local_points_user', 'points', [
    'userid' => $USER->id,
    'courseid' => null
]);
$userpoints = $userpoints ? $userpoints : 0;

echo $OUTPUT->header();

// User's points display.
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body text-center');
echo html_writer::tag('h5', get_string('yourpoints', 'local_points'), ['class' => 'card-title']);
echo html_writer::tag('p', $userpoints, ['class' => 'h1 text-primary']);
echo html_writer::link(
    new moodle_url('/local/points/store/history.php'),
    get_string('myredemptions', 'local_points'),
    ['class' => 'btn btn-outline-primary btn-sm']
);
echo html_writer::end_div();
echo html_writer::end_div();

// Categories tabs.
$categories = $DB->get_records('local_points_reward_categories', ['visible' => 1], 'sortorder ASC');

if (!empty($categories)) {
    echo html_writer::start_tag('ul', ['class' => 'nav nav-tabs mb-4']);

    // All categories tab.
    $activeclass = $categoryid == 0 ? ' active' : '';
    echo html_writer::start_tag('li', ['class' => 'nav-item']);
    echo html_writer::link(
        new moodle_url('/local/points/store/index.php'),
        get_string('allcategories', 'local_points'),
        ['class' => 'nav-link' . $activeclass]
    );
    echo html_writer::end_tag('li');

    foreach ($categories as $category) {
        $activeclass = $categoryid == $category->id ? ' active' : '';
        echo html_writer::start_tag('li', ['class' => 'nav-item']);
        echo html_writer::link(
            new moodle_url('/local/points/store/index.php', ['category' => $category->id]),
            format_string($category->name),
            ['class' => 'nav-link' . $activeclass]
        );
        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');
}

// Get available rewards.
$now = time();
$params = ['enabled' => 1];
$where = 'enabled = :enabled';

// Filter by availability dates.
$where .= ' AND (availablefrom IS NULL OR availablefrom = 0 OR availablefrom <= :now1)';
$where .= ' AND (availableuntil IS NULL OR availableuntil = 0 OR availableuntil >= :now2)';
$params['now1'] = $now;
$params['now2'] = $now;

// Filter by category.
if ($categoryid > 0) {
    $where .= ' AND categoryid = :categoryid';
    $params['categoryid'] = $categoryid;
}

// Filter by stock.
$where .= ' AND (quantity IS NULL OR quantity > 0)';

$rewards = $DB->get_records_select('local_points_rewards', $where, $params, 'name ASC');

if (empty($rewards)) {
    echo $OUTPUT->notification(get_string('norewardsavailable', 'local_points'), 'info');
} else {
    echo html_writer::start_div('row');

    foreach ($rewards as $reward) {
        echo html_writer::start_div('col-md-4 mb-4');
        echo html_writer::start_div('card h-100');

        // Reward image.
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
                'class' => 'card-img-top',
                'style' => 'height: 200px; object-fit: cover;'
            ]);
        } else {
            // Placeholder image.
            echo html_writer::start_div('card-img-top bg-light d-flex align-items-center justify-content-center', [
                'style' => 'height: 200px;'
            ]);
            echo html_writer::tag('i', '', ['class' => 'fa fa-gift fa-3x text-muted']);
            echo html_writer::end_div();
        }

        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', format_string($reward->name), ['class' => 'card-title']);

        if (!empty($reward->description)) {
            $shortdesc = shorten_text(strip_tags($reward->description), 100);
            echo html_writer::tag('p', $shortdesc, ['class' => 'card-text text-muted']);
        }

        // Cost.
        $costclass = $userpoints >= $reward->cost ? 'text-success' : 'text-danger';
        echo html_writer::tag('p',
            html_writer::tag('strong', $reward->cost . ' ' . get_string('points', 'local_points')),
            ['class' => $costclass . ' mb-2']
        );

        // Stock info.
        if ($reward->quantity !== null) {
            echo html_writer::tag('small',
                get_string('stockremaining', 'local_points', $reward->quantity),
                ['class' => 'text-muted d-block mb-2']
            );
        }

        echo html_writer::end_div();

        // Card footer with action button.
        echo html_writer::start_div('card-footer bg-transparent');

        if ($userpoints >= $reward->cost) {
            echo html_writer::link(
                new moodle_url('/local/points/store/detail.php', ['id' => $reward->id]),
                get_string('viewdetails', 'local_points'),
                ['class' => 'btn btn-primary btn-block']
            );
        } else {
            $pointsneeded = $reward->cost - $userpoints;
            echo html_writer::tag('span',
                get_string('needmorepoints', 'local_points', $pointsneeded),
                ['class' => 'text-muted small']
            );
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    echo html_writer::end_div();
}

// Navigation links.
echo html_writer::start_div('mt-4');
echo html_writer::link(
    new moodle_url('/local/points/view.php'),
    get_string('backtooverview', 'local_points'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo $OUTPUT->footer();
