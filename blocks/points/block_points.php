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
 * Points block.
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

        // Check capability.
        $context = context_system::instance();
        if (!has_capability('local/points:viewown', $context)) {
            return $this->content;
        }

        // Get user's global points.
        $userpoints = $DB->get_field('local_points_user', 'points', [
            'userid' => $USER->id,
            'courseid' => null
        ]);
        $userpoints = $userpoints ? $userpoints : 0;

        // Build content.
        $html = '';

        // Points display.
        $html .= html_writer::start_div('points-summary text-center mb-3');
        $html .= html_writer::tag('div', get_string('yourpoints', 'block_points'), ['class' => 'small text-muted']);
        $html .= html_writer::tag('div', $userpoints, ['class' => 'h1 text-primary font-weight-bold mb-0']);
        $html .= html_writer::tag('div', get_string('points', 'block_points'), ['class' => 'small text-muted']);
        $html .= html_writer::end_div();

        // Featured rewards.
        $now = time();
        $sql = "SELECT * FROM {local_points_rewards}
                WHERE enabled = 1
                AND (quantity IS NULL OR quantity > 0)
                AND (availablefrom IS NULL OR availablefrom = 0 OR availablefrom <= :now1)
                AND (availableuntil IS NULL OR availableuntil = 0 OR availableuntil >= :now2)
                ORDER BY cost ASC
                LIMIT 3";

        $rewards = $DB->get_records_sql($sql, ['now1' => $now, 'now2' => $now]);

        if (!empty($rewards)) {
            $html .= html_writer::tag('div', get_string('featuredrewards', 'block_points'), [
                'class' => 'font-weight-bold mb-2 border-top pt-2'
            ]);

            foreach ($rewards as $reward) {
                $html .= html_writer::start_div('reward-item d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded');

                // Reward info.
                $html .= html_writer::start_div('reward-info');
                $html .= html_writer::tag('div', format_string($reward->name), ['class' => 'font-weight-bold small']);
                $costclass = $userpoints >= $reward->cost ? 'text-success' : 'text-danger';
                $html .= html_writer::tag('div', $reward->cost . ' pts', ['class' => 'small ' . $costclass]);
                $html .= html_writer::end_div();

                // View button.
                if ($userpoints >= $reward->cost) {
                    $html .= html_writer::link(
                        new moodle_url('/local/points/store/detail.php', ['id' => $reward->id]),
                        get_string('view', 'block_points'),
                        ['class' => 'btn btn-sm btn-outline-primary']
                    );
                }

                $html .= html_writer::end_div();
            }
        }

        // Action buttons.
        $html .= html_writer::start_div('mt-3 text-center');
        $html .= html_writer::link(
            new moodle_url('/local/points/store/index.php'),
            get_string('gotostore', 'block_points'),
            ['class' => 'btn btn-primary btn-sm btn-block mb-2']
        );
        $html .= html_writer::link(
            new moodle_url('/local/points/view.php'),
            get_string('viewhistory', 'block_points'),
            ['class' => 'btn btn-outline-secondary btn-sm btn-block']
        );
        $html .= html_writer::end_div();

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
