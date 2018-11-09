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
 * @package    block_backadel
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/backadel/lib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');


class block_backadel extends block_list {

    public function init() {
        $this->title = get_string('pluginname', 'block_backadel');
    }

    public function applicable_formats() {
        return array('site' => true, 'my' => false, 'course' => false);
    }

    public function has_config() {
        return true;
    }

    public function get_content() {
        global $DB, $CFG, $USER, $OUTPUT;

        if (!is_siteadmin($USER->id)) {
            return $this->content;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $table = 'block_backadel_statuses';

        $numpending = $DB->count_records_select($table, "status='SUCCESS'");
        $numfailed = $DB->count_records_select($table, "status='FAIL'");

        $running = get_config('block_backadel', 'running');

        if (!$running) {
            $statustext = get_string('status_not_running', 'block_backadel');
        } else {
            $minutesrun = round((time() - $running) / 60);
            $statustext = get_string('status_running', 'block_backadel', $minutesrun);
        }

        $icons = array();
        $items = array();

        $params = array('class' => 'icon');

        $icons[] = $OUTPUT->pix_icon('i/backup', '', 'moodle', $params);
        $icons[] = $OUTPUT->pix_icon('i/delete', '', 'moodle', $params);
        $icons[] = $OUTPUT->pix_icon('i/risk_xss', '', 'moodle', $params);
        $icons[] = $OUTPUT->pix_icon('i/calendareventtime', '', 'moodle', $params);

        $items[] = $this->build_link('index');
        $items[] = $this->build_link('delete') . "($numpending)";
        $items[] = $this->build_link('failed') . "($numfailed)";
        $items[] = $statustext;

        $this->content = new stdClass;

        $this->content->icons = $icons;
        $this->content->items = $items;
        $this->content->footer = '';

        return $this->content;
    }

    public function build_link($page) {
        $url = new moodle_url("/blocks/backadel/$page.php");

        return html_writer::link($url, get_string("block_$page", "block_backadel"));
    }
}
