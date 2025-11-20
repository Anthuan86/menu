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
$sort = optional_param('sort', 'popular', PARAM_ALPHA);

require_login();

$context = context_system::instance();

// Setup page.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/points/store/index.php', ['category' => $categoryid]));
$PAGE->set_title(get_string('store', 'local_points'));
$PAGE->set_heading(get_string('store', 'local_points'));
$PAGE->set_pagelayout('standard');

// Get user's total points (sum of all courses and global).
$userpoints = $DB->get_field_sql(
    'SELECT COALESCE(SUM(points), 0) FROM {local_points_user} WHERE userid = :userid',
    ['userid' => $USER->id]
);
$userpoints = $userpoints ? $userpoints : 0;

echo $OUTPUT->header();

// Professional e-commerce CSS.
echo '<style>
/* Store Container */
.points-store {
    max-width: 1400px;
    margin: 0 auto;
}

/* Hero Banner */
.store-hero {
    background: linear-gradient(135deg, #d20a11 0%, #8b0000 50%, #d20a11 100%);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 30px;
    color: white;
    position: relative;
    overflow: hidden;
}
.store-hero::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
}
.store-hero .points-display {
    position: relative;
    z-index: 1;
}
.store-hero .points-amount {
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1;
}
.store-hero .points-label {
    font-size: 1.2rem;
    opacity: 0.9;
}
.store-hero .hero-actions {
    margin-top: 20px;
}
.store-hero .hero-actions .btn {
    margin-right: 10px;
    border-radius: 25px;
    padding: 10px 25px;
    font-weight: 600;
}

/* Store Navigation */
.store-nav {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
}
.store-nav .nav-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}
.store-nav .category-btn {
    padding: 8px 20px;
    border-radius: 20px;
    border: 2px solid #e9ecef;
    background: white;
    color: #495057;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
}
.store-nav .category-btn:hover {
    border-color: #d20a11;
    color: #d20a11;
}
.store-nav .category-btn.active {
    background: #d20a11;
    border-color: #d20a11;
    color: white;
}
.store-nav .sort-options {
    display: flex;
    align-items: center;
    gap: 10px;
}
.store-nav .sort-options select {
    border-radius: 10px;
    padding: 8px 15px;
    border: 2px solid #e9ecef;
}

/* Product Grid */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

/* Product Card */
.product-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.4s ease;
    position: relative;
}
.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

/* Product Image */
.product-image {
    position: relative;
    height: 250px;
    overflow: hidden;
    background: #f5f5f5;
}
.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.product-card:hover .product-image img {
    transform: scale(1.1);
}
.product-image .placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
}
.product-image .placeholder i {
    font-size: 4rem;
    color: #cbd5e0;
}

/* Product Badges */
.product-badges {
    position: absolute;
    top: 15px;
    left: 15px;
    right: 15px;
    display: flex;
    justify-content: space-between;
}
.badge-stock {
    background: rgba(0,0,0,0.75);
    color: white;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-hot {
    background: #d20a11;
    color: white;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Quick View Overlay */
.quick-view {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(210, 10, 17, 0.95);
    padding: 15px;
    transform: translateY(100%);
    transition: transform 0.3s ease;
}
.product-card:hover .quick-view {
    transform: translateY(0);
}
.quick-view .btn {
    width: 100%;
    border-radius: 10px;
    font-weight: 600;
}

/* Product Info */
.product-info {
    padding: 20px;
}
.product-category {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #d20a11;
    font-weight: 600;
    letter-spacing: 1px;
    margin-bottom: 8px;
}
.product-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 10px;
    line-height: 1.3;
}
.product-description {
    font-size: 0.85rem;
    color: #718096;
    margin-bottom: 15px;
    line-height: 1.5;
}

/* Product Price */
.product-price {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}
.price-amount {
    font-size: 1.5rem;
    font-weight: 800;
    color: #d20a11;
}
.price-amount small {
    font-size: 0.8rem;
    font-weight: 500;
}
.price-status {
    font-size: 0.8rem;
    padding: 6px 12px;
    border-radius: 15px;
    font-weight: 600;
}
.price-status.affordable {
    background: #d4edda;
    color: #155724;
}
.price-status.expensive {
    background: #f8d7da;
    color: #721c24;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
}
.empty-state i {
    font-size: 5rem;
    color: #e2e8f0;
    margin-bottom: 20px;
}
.empty-state h3 {
    color: #4a5568;
    margin-bottom: 10px;
}
.empty-state p {
    color: #718096;
}

/* Footer Navigation */
.store-footer {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #f0f0f0;
    text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
    .store-hero {
        padding: 25px;
    }
    .store-hero .points-amount {
        font-size: 2.5rem;
    }
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
}
</style>';

echo '<div class="points-store">';

// Hero Banner.
echo '<div class="store-hero">';
echo '<div class="row align-items-center">';
echo '<div class="col-md-8">';
echo '<div class="points-display">';
echo '<div class="points-label">' . get_string('yourpoints', 'local_points') . '</div>';
echo '<div class="points-amount">' . number_format($userpoints) . ' <small>pts</small></div>';
echo '</div>';
echo '<div class="hero-actions">';
echo html_writer::link(
    new moodle_url('/local/points/store/history.php'),
    '<i class="fa fa-history"></i> ' . get_string('myredemptions', 'local_points'),
    ['class' => 'btn btn-light']
);
echo html_writer::link(
    new moodle_url('/local/points/view.php'),
    '<i class="fa fa-chart-line"></i> ' . get_string('viewhistory', 'local_points'),
    ['class' => 'btn btn-outline-light']
);
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Store Navigation.
echo '<div class="store-nav">';

// Categories.
$categories = $DB->get_records('local_points_reward_categories', ['visible' => 1], 'sortorder ASC');

echo '<div class="nav-categories">';
$activeclass = $categoryid == 0 ? ' active' : '';
echo html_writer::link(
    new moodle_url('/local/points/store/index.php'),
    get_string('allcategories', 'local_points'),
    ['class' => 'category-btn' . $activeclass]
);

foreach ($categories as $category) {
    $activeclass = $categoryid == $category->id ? ' active' : '';
    echo html_writer::link(
        new moodle_url('/local/points/store/index.php', ['category' => $category->id]),
        format_string($category->name),
        ['class' => 'category-btn' . $activeclass]
    );
}
echo '</div>';

// Sort options.
echo '<div class="sort-options">';
echo '<label>' . get_string('sortby', 'local_points') . ':</label>';
echo '<select onchange="window.location.href=this.value">';
$sorturl = new moodle_url('/local/points/store/index.php', ['category' => $categoryid, 'sort' => 'popular']);
echo '<option value="' . $sorturl . '"' . ($sort == 'popular' ? ' selected' : '') . '>' . get_string('popular', 'local_points') . '</option>';
$sorturl = new moodle_url('/local/points/store/index.php', ['category' => $categoryid, 'sort' => 'pricelow']);
echo '<option value="' . $sorturl . '"' . ($sort == 'pricelow' ? ' selected' : '') . '>' . get_string('pricelowtohigh', 'local_points') . '</option>';
$sorturl = new moodle_url('/local/points/store/index.php', ['category' => $categoryid, 'sort' => 'pricehigh']);
echo '<option value="' . $sorturl . '"' . ($sort == 'pricehigh' ? ' selected' : '') . '>' . get_string('pricehightolow', 'local_points') . '</option>';
$sorturl = new moodle_url('/local/points/store/index.php', ['category' => $categoryid, 'sort' => 'newest']);
echo '<option value="' . $sorturl . '"' . ($sort == 'newest' ? ' selected' : '') . '>' . get_string('newest', 'local_points') . '</option>';
echo '</select>';
echo '</div>';

echo '</div>';

// Get available rewards.
$now = time();
$params = ['enabled' => 1];
$where = 'enabled = :enabled';
$where .= ' AND (availablefrom IS NULL OR availablefrom = 0 OR availablefrom <= :now1)';
$where .= ' AND (availableuntil IS NULL OR availableuntil = 0 OR availableuntil >= :now2)';
$params['now1'] = $now;
$params['now2'] = $now;

if ($categoryid > 0) {
    $where .= ' AND categoryid = :categoryid';
    $params['categoryid'] = $categoryid;
}

$where .= ' AND (quantity IS NULL OR quantity > 0)';

// Sort order.
switch ($sort) {
    case 'pricelow':
        $orderby = 'cost ASC, name ASC';
        break;
    case 'pricehigh':
        $orderby = 'cost DESC, name ASC';
        break;
    case 'newest':
        $orderby = 'timecreated DESC, name ASC';
        break;
    default: // popular
        $orderby = 'cost ASC, name ASC';
}

$rewards = $DB->get_records_select('local_points_rewards', $where, $params, $orderby);

if (empty($rewards)) {
    echo '<div class="empty-state">';
    echo '<i class="fa fa-shopping-bag"></i>';
    echo '<h3>' . get_string('norewardsavailable', 'local_points') . '</h3>';
    echo '<p>' . get_string('checkbacklater', 'local_points') . '</p>';
    echo '</div>';
} else {
    echo '<div class="products-grid">';

    foreach ($rewards as $reward) {
        // Get category name.
        $catname = '';
        if ($reward->categoryid && isset($categories[$reward->categoryid])) {
            $catname = $categories[$reward->categoryid]->name;
        }

        // Get redemption count for popularity.
        $redemptions = $DB->count_records('local_points_redemptions', ['rewardid' => $reward->id]);

        echo '<div class="product-card">';

        // Product image.
        echo '<div class="product-image">';

        // Badges.
        echo '<div class="product-badges">';
        if ($reward->quantity !== null && $reward->quantity <= 5) {
            echo '<span class="badge-stock">' . get_string('only', 'local_points') . ' ' . $reward->quantity . ' ' . get_string('left', 'local_points') . '</span>';
        } else {
            echo '<span></span>';
        }
        if ($redemptions >= 5) {
            echo '<span class="badge-hot"><i class="fa fa-fire"></i> ' . get_string('popular', 'local_points') . '</span>';
        }
        echo '</div>';

        if (!empty($reward->image)) {
            $imageurl = moodle_url::make_pluginfile_url(
                $context->id,
                'local_points',
                'rewardimage',
                $reward->id,
                '/',
                $reward->image
            );
            echo html_writer::img($imageurl, format_string($reward->name));
        } else {
            echo '<div class="placeholder"><i class="fa fa-gift"></i></div>';
        }

        // Quick view overlay.
        echo '<div class="quick-view">';
        echo html_writer::link(
            new moodle_url('/local/points/store/detail.php', ['id' => $reward->id]),
            '<i class="fa fa-eye"></i> ' . get_string('viewdetails', 'local_points'),
            ['class' => 'btn btn-light']
        );
        echo '</div>';

        echo '</div>';

        // Product info.
        echo '<div class="product-info">';

        if ($catname) {
            echo '<div class="product-category">' . format_string($catname) . '</div>';
        }

        echo '<div class="product-name">' . format_string($reward->name) . '</div>';

        if (!empty($reward->description)) {
            $shortdesc = shorten_text(strip_tags($reward->description), 60);
            echo '<div class="product-description">' . $shortdesc . '</div>';
        }

        // Price.
        echo '<div class="product-price">';
        echo '<div class="price-amount">' . number_format($reward->cost) . ' <small>pts</small></div>';

        if ($userpoints >= $reward->cost) {
            echo '<span class="price-status affordable"><i class="fa fa-check"></i> ' . get_string('available', 'local_points') . '</span>';
        } else {
            $needed = $reward->cost - $userpoints;
            echo '<span class="price-status expensive">' . get_string('need', 'local_points') . ' ' . number_format($needed) . '</span>';
        }

        echo '</div>';
        echo '</div>';

        echo '</div>';
    }

    echo '</div>';
}

// Footer.
echo '<div class="store-footer">';
echo html_writer::link(
    new moodle_url('/local/points/view.php'),
    '<i class="fa fa-arrow-left"></i> ' . get_string('backtooverview', 'local_points'),
    ['class' => 'btn btn-outline-secondary']
);
echo '</div>';

echo '</div>'; // points-store

echo $OUTPUT->footer();
