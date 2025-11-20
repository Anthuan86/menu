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
 * Points block with carousel.
 *
 * @package    block_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_points extends block_base {

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_points');
    }

    /**
     * Get block content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        // Get user's total points (sum of all courses and global).
        $userpoints = $DB->get_field_sql(
            'SELECT COALESCE(SUM(points), 0) FROM {local_points_user} WHERE userid = :userid',
            ['userid' => $USER->id]
        );
        $userpoints = $userpoints ? $userpoints : 0;

        // Generate unique ID for this block instance.
        $uniqueid = 'points-carousel-' . uniqid();

        // Build content with CSS.
        $html = '<style>
        .points-block-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .points-block-header {
            background: linear-gradient(135deg, #d20a11 0%, #8b0000 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
            text-align: center;
            margin-bottom: 15px;
        }
        .points-block-header .points-value {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }
        .points-block-header .points-label {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 5px;
        }

        /* Carousel Container */
        .rewards-carousel-container {
            position: relative;
            margin-bottom: 15px;
        }
        .rewards-carousel {
            overflow: hidden;
            border-radius: 12px;
        }
        .rewards-carousel-track {
            display: flex;
            transition: transform 0.5s ease;
        }

        /* Carousel Item */
        .carousel-item-reward {
            min-width: 100%;
            padding: 5px;
            box-sizing: border-box;
        }
        .reward-card-mini {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .reward-card-mini .reward-image {
            height: 120px;
            background: #f5f5f5;
            position: relative;
            overflow: hidden;
        }
        .reward-card-mini .reward-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .reward-card-mini .reward-image .placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
        }
        .reward-card-mini .reward-image .placeholder i {
            font-size: 2rem;
            color: #cbd5e0;
        }
        .reward-card-mini .reward-info {
            padding: 12px;
        }
        .reward-card-mini .reward-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #2d3748;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .reward-card-mini .reward-cost {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .reward-card-mini .cost-value {
            font-weight: 700;
            font-size: 1.1rem;
            color: #d20a11;
        }
        .reward-card-mini .cost-value small {
            font-size: 0.7rem;
            font-weight: 500;
        }
        .reward-card-mini .btn-view {
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 15px;
            background: #d20a11;
            color: white;
            text-decoration: none;
            font-weight: 600;
        }
        .reward-card-mini .btn-view:hover {
            background: #8b0000;
            color: white;
        }

        /* Carousel Navigation */
        .carousel-nav {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }
        .carousel-nav button {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .carousel-nav button:hover {
            border-color: #d20a11;
            color: #d20a11;
        }
        .carousel-nav button i {
            font-size: 0.8rem;
        }

        /* Carousel Dots */
        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 10px;
        }
        .carousel-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #e2e8f0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .carousel-dot.active {
            background: #d20a11;
            width: 20px;
            border-radius: 4px;
        }

        /* Action Buttons */
        .points-block-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .points-block-actions .btn {
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 10px;
            text-align: center;
            text-decoration: none;
        }
        .points-block-actions .btn-store {
            background: linear-gradient(135deg, #d20a11 0%, #8b0000 100%);
            color: white;
        }
        .points-block-actions .btn-store:hover {
            opacity: 0.9;
            color: white;
        }
        .points-block-actions .btn-history {
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #e2e8f0;
        }
        .points-block-actions .btn-history:hover {
            background: #e2e8f0;
        }
        </style>';

        $html .= '<div class="points-block-container">';

        // Points display.
        $html .= '<div class="points-block-header">';
        $html .= '<div class="points-value">' . number_format($userpoints) . '</div>';
        $html .= '<div class="points-label">' . get_string('points', 'block_points') . '</div>';
        $html .= '</div>';

        // Get featured rewards for carousel.
        $now = time();
        $sql = "SELECT * FROM {local_points_rewards}
                WHERE enabled = 1
                AND (quantity IS NULL OR quantity > 0)
                AND (availablefrom IS NULL OR availablefrom = 0 OR availablefrom <= :now1)
                AND (availableuntil IS NULL OR availableuntil = 0 OR availableuntil >= :now2)
                ORDER BY cost ASC";

        $rewards = $DB->get_records_sql($sql, ['now1' => $now, 'now2' => $now], 0, 5);

        if (!empty($rewards)) {
            $html .= '<div class="rewards-carousel-container">';
            $html .= '<div class="rewards-carousel">';
            $html .= '<div class="rewards-carousel-track" id="' . $uniqueid . '-track">';

            $index = 0;
            foreach ($rewards as $reward) {
                $html .= '<div class="carousel-item-reward">';
                $html .= '<div class="reward-card-mini">';

                // Image.
                $html .= '<div class="reward-image">';
                if (!empty($reward->image)) {
                    $imageurl = moodle_url::make_pluginfile_url(
                        $context->id,
                        'local_points',
                        'rewardimage',
                        $reward->id,
                        '/',
                        $reward->image
                    );
                    $html .= html_writer::img($imageurl, format_string($reward->name));
                } else {
                    $html .= '<div class="placeholder"><i class="fa fa-gift"></i></div>';
                }
                $html .= '</div>';

                // Info.
                $html .= '<div class="reward-info">';
                $html .= '<div class="reward-name">' . format_string($reward->name) . '</div>';
                $html .= '<div class="reward-cost">';
                $html .= '<span class="cost-value">' . number_format($reward->cost) . ' <small>pts</small></span>';
                $html .= html_writer::link(
                    new moodle_url('/local/points/store/detail.php', ['id' => $reward->id]),
                    get_string('view', 'block_points'),
                    ['class' => 'btn-view']
                );
                $html .= '</div>';
                $html .= '</div>';

                $html .= '</div>';
                $html .= '</div>';
                $index++;
            }

            $html .= '</div>'; // track
            $html .= '</div>'; // carousel

            // Navigation arrows.
            if (count($rewards) > 1) {
                $html .= '<div class="carousel-nav">';
                $html .= '<button onclick="moveCarousel(\'' . $uniqueid . '\', -1)"><i class="fa fa-chevron-left"></i></button>';
                $html .= '<button onclick="moveCarousel(\'' . $uniqueid . '\', 1)"><i class="fa fa-chevron-right"></i></button>';
                $html .= '</div>';

                // Dots.
                $html .= '<div class="carousel-dots" id="' . $uniqueid . '-dots">';
                for ($i = 0; $i < count($rewards); $i++) {
                    $activeclass = $i === 0 ? ' active' : '';
                    $html .= '<div class="carousel-dot' . $activeclass . '" onclick="goToSlide(\'' . $uniqueid . '\', ' . $i . ')"></div>';
                }
                $html .= '</div>';
            }

            $html .= '</div>'; // container
        }

        // Action buttons.
        $html .= '<div class="points-block-actions">';
        $html .= html_writer::link(
            new moodle_url('/local/points/store/index.php'),
            '<i class="fa fa-shopping-bag"></i> ' . get_string('gotostore', 'block_points'),
            ['class' => 'btn btn-store']
        );
        $html .= html_writer::link(
            new moodle_url('/local/points/view.php'),
            '<i class="fa fa-history"></i> ' . get_string('viewhistory', 'block_points'),
            ['class' => 'btn btn-history']
        );
        $html .= '</div>';

        $html .= '</div>'; // container

        // JavaScript for carousel.
        $html .= '<script>
        var carouselState = {};

        function initCarousel(id) {
            if (!carouselState[id]) {
                carouselState[id] = { currentSlide: 0 };
            }
        }

        function moveCarousel(id, direction) {
            initCarousel(id);
            var track = document.getElementById(id + "-track");
            if (!track) return;

            var items = track.children.length;
            var current = carouselState[id].currentSlide;
            var newSlide = current + direction;

            if (newSlide < 0) newSlide = items - 1;
            if (newSlide >= items) newSlide = 0;

            goToSlide(id, newSlide);
        }

        function goToSlide(id, slideIndex) {
            initCarousel(id);
            var track = document.getElementById(id + "-track");
            var dots = document.getElementById(id + "-dots");
            if (!track) return;

            carouselState[id].currentSlide = slideIndex;
            track.style.transform = "translateX(-" + (slideIndex * 100) + "%)";

            if (dots) {
                var dotElements = dots.children;
                for (var i = 0; i < dotElements.length; i++) {
                    dotElements[i].classList.remove("active");
                }
                if (dotElements[slideIndex]) {
                    dotElements[slideIndex].classList.add("active");
                }
            }
        }

        // Auto-advance carousel every 5 seconds.
        setInterval(function() {
            for (var id in carouselState) {
                moveCarousel(id, 1);
            }
        }, 5000);
        </script>';

        $this->content->text = $html;

        return $this->content;
    }

    /**
     * Allow multiple instances.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Applicable formats.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'all' => true,
            'my' => true,
            'site-index' => true
        ];
    }

    /**
     * Has config.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }
}
