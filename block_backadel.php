<?php

require_once($CFG->dirroot . '/blocks/backadel/lib.php');

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

    function cron() {
        global $DB, $CFG;
        mtrace('begin cron for BACKADEL!!!!!!!!!!!!!!!!!!!1');
        $_s = function($key, $a=NULL) {
            return get_string($key, 'block_backadel', $a);
        };

        $running = get_config('block_backadel', 'running');

        if ($running) {
            $minutes_run = round((time() - $running) / 60);
            echo "\n" . $_s('cron_already_running', $minutes_run) . "\n";
            return;
        }

        $params = array('status' => 'BACKUP');
        if (!$backups = $DB->get_records('block_backadel_statuses', $params)) {
            return true;
        }

        $error = false;
        $error_log = '';

        set_config('running', time(), 'block_backadel');

        foreach ($backups as $b) {
            $course = $DB->get_record('course', array('id' => $b->coursesid));

            echo "\n" . $_s('backing_up') . ' ' . $course->shortname . "\n";

            if (!backadel_backup_course($course)) {
                $error = true;
                $error_log .= $_s('cron_backup_error', $course->shortname) . "\n";
            }

            $b->status = $error ? 'FAIL' : 'SUCCESS';
            $DB->update_record('block_backadel_statuses', $b);
        }

        set_config('running', '', 'block_backadel');

        backadel_email_admins($error_log);

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
