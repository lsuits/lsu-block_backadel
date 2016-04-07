<?php

require_once($CFG->dirroot . '/blocks/backadel/lib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');


class block_backadel extends block_list {

    function init() {
        $this->title = get_string('pluginname', 'block_backadel');
    }

    function applicable_formats() {
        return array('site' => true, 'my' => false, 'course' => false);
    }
    
    function has_config(){
        return true;
    }

    function get_content() {
        global $DB, $CFG, $USER, $OUTPUT;

        $_s = function($key, $a=NULL) {
            return get_string($key, 'block_backadel', $a);
        };

        if (!is_siteadmin($USER->id)) {
            return $this->content;
        }

        if ($this->content !== NULL) {
            return $this->content;
        }

        $table = 'block_backadel_statuses';

        $num_pending = $DB->count_records_select($table, "status='SUCCESS'");
        $num_failed = $DB->count_records_select($table, "status='FAIL'");

        $running = get_config('block_backadel', 'running');

        if (!$running) {
            $status_text = $_s('status_not_running');
        } else {
            $minutes_run = round((time() - $running) / 60);
            $status_text = $_s('status_running', $minutes_run);
        }

        $icons = array();
        $items = array();

        $params = array('class' => 'icon');

        $icons[] = $OUTPUT->pix_icon('i/backup', '', 'moodle', $params);
        $icons[] = $OUTPUT->pix_icon('i/cross_red_big', '', 'moodle', $params);
        $icons[] = $OUTPUT->pix_icon('i/risk_xss', '', 'moodle', $params);
        $icons[] = $OUTPUT->pix_icon('i/admin', '', 'moodle', $params);

        $items[] = $this->build_link('index');
        $items[] = $this->build_link('delete') . "($num_pending)";
        $items[] = $this->build_link('failed') . "($num_failed)";
        $items[] = $status_text;

        $this->content = new stdClass;

        $this->content->icons = $icons;
        $this->content->items = $items;
        $this->content->footer = '';

        return $this->content;
    }

    function build_link($page) {
        $_s = function($key) { return get_string($key, 'block_backadel'); };

        $url = new moodle_url("/blocks/backadel/$page.php");

        return html_writer::link($url, $_s("block_$page"));
    }
}
