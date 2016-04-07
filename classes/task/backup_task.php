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
 * A scheduled task for Backadel.
 *
 * @package    block_backadel
 * @copyright  2016 Louisiana State University, David Elliott, Robert Russo, Chad Mazilly <delliott@lsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_backadel\task;

//use block_backadel;

/**
 * A scheduled task class for Backing up courses using the LSU Backadel Block.
 */
//require_once 'block_backadel.php';
require_once($CFG->dirroot . '/blocks/backadel/block_backadel.php');


class backup_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('backuptask', 'block_backadel');
    }

    /**
     * Run backups
     */
    public function execute() {
        global $CFG;
        begin_backup_task();
    }

}

    function begin_backup_task() {
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
