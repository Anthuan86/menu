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

// Custom CSS for better visualization.
echo '<style>
.points-store-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    color: white;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}
.points-store-header .points-value {
    font-size: 4rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}
.reward-card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
.reward-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}
.reward-card .card-img-top {
    height: 220px;
    object-fit: cover;
}
.reward-card .placeholder-img {
    height: 220px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}
.reward-card .card-body {
    padding: 20px;
}
.reward-card .card-title {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 10px;
}
.reward-card .cost-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 1.1rem;
}
.cost-affordable {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}
.cost-expensive {
    background: #f8f9fa;
    color: #6c757d;
}
.reward-card .card-footer {
    background: transparent;
    border-top: 1px solid rgba(0,0,0,0.05);
    padding: 15px 20px;
}
.reward-card .btn-redeem {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 25px;
    padding: 10px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}
.reward-card .btn-redeem:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}
.stock-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 5px 10px;
    border-radius: 10px;
    font-size: 0.75rem;
}
.category-pills .nav-link {
    border-radius: 25px;
    padding: 8px 20px;
    margin: 0 5px 10px 0;
    font-weight: 500;
    color: #495057;
    background: #f8f9fa;
    border: none;
}
.category-pills .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
</style>';

// User's points display - improved header.
echo html_writer::start_div('points-store-header text-center');
echo html_writer::tag('div', get_string('yourpoints', 'local_points'), ['class' => 'mb-2 opacity-75']);
echo html_writer::tag('div', number_format($userpoints), ['class' => 'points-value']);
echo html_writer::tag('div', get_string('points', 'local_points'), ['class' => 'mb-3 opacity-75']);
echo html_writer::link(
    new moodle_url('/local/points/store/history.php'),
    get_string('myredemptions', 'local_points'),
    ['class' => 'btn btn-light btn-sm']
);
echo html_writer::end_div();

// Categories pills.
$categories = $DB->get_records('local_points_reward_categories', ['visible' => 1], 'sortorder ASC');

if (!empty($categories)) {
    echo html_writer::start_tag('ul', ['class' => 'nav category-pills mb-4 justify-content-center flex-wrap']);

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

$rewards = $DB->get_records_select('local_points_rewards', $where, $params, 'cost ASC, name ASC');

if (empty($rewards)) {
    echo html_writer::start_div('text-center py-5');
    echo html_writer::tag('i', '', ['class' => 'fa fa-gift fa-4x text-muted mb-3']);
    echo html_writer::tag('h4', get_string('norewardsavailable', 'local_points'), ['class' => 'text-muted']);
    echo html_writer::end_div();
} else {
    echo html_writer::start_div('row');

    foreach ($rewards as $reward) {
        echo html_writer::start_div('col-lg-4 col-md-6 mb-4');
        echo html_writer::start_div('card reward-card h-100');

        // Card image container.
        echo html_writer::start_div('position-relative');

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
                'class' => 'card-img-top'
            ]);
        } else {
            // Placeholder image.
            echo html_writer::start_div('placeholder-img');
            echo html_writer::tag('i', '', ['class' => 'fa fa-gift fa-4x text-muted']);
            echo html_writer::end_div();
        }

        // Stock badge.
        if ($reward->quantity !== null) {
            echo html_writer::tag('span',
                get_string('stockremaining', 'local_points', $reward->quantity),
                ['class' => 'stock-badge']
            );
        }

        echo html_writer::end_div(); // position-relative

        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', format_string($reward->name), ['class' => 'card-title']);

        if (!empty($reward->description)) {
            $shortdesc = shorten_text(strip_tags($reward->description), 80);
            echo html_writer::tag('p', $shortdesc, ['class' => 'card-text text-muted small mb-3']);
        }

        // Cost badge.
        $costclass = $userpoints >= $reward->cost ? 'cost-affordable' : 'cost-expensive';
        echo html_writer::tag('span', number_format($reward->cost) . ' pts', ['class' => 'cost-badge ' . $costclass]);

        echo html_writer::end_div();

        // Card footer with action button.
        echo html_writer::start_div('card-footer');

        if ($userpoints >= $reward->cost) {
            echo html_writer::link(
                new moodle_url('/local/points/store/detail.php', ['id' => $reward->id]),
                get_string('viewdetails', 'local_points'),
                ['class' => 'btn btn-redeem btn-block text-white']
            );
        } else {
            $pointsneeded = $reward->cost - $userpoints;
            echo html_writer::tag('div',
                get_string('needmorepoints', 'local_points', number_format($pointsneeded)),
                ['class' => 'text-muted text-center small']
            );
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    echo html_writer::end_div();
}

// Navigation links.
echo html_writer::start_div('mt-4 text-center');
echo html_writer::link(
    new moodle_url('/local/points/view.php'),
    get_string('backtooverview', 'local_points'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo $OUTPUT->footer();
