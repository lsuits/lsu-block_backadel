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
 * @copyright  2008 onwards - Louisiana State University, David Elliott, Robert Russo, Chad Mazilly <delliott@lsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_backadel\task;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/backadel/block_backadel.php');

// A scheduled task class for Backing up courses using the LSU Backadel Block.
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

/**
 * Run the backup task itself.
 */
function begin_backup_task() {
    global $DB, $CFG;
    mtrace('Begin cron for Backup and Delete.');
    $running = get_config('block_backadel', 'running');

    // Check if the task is running, return the runtime, and exit.
    if ($running) {
        $minutesrun = round((time() - $running) / 60);
        echo "\n" . get_string('cron_already_running', 'block_backadel', $minutesrun) . "\n";
            return;
    }

    // Only get the courses scheduled for backup!
    $params = array('status' => 'BACKUP');
    if (!$backups = $DB->get_records('block_backadel_statuses', $params)) {
        return true;
    }

    $error = false;
    $errorlog = '';

    // Mark the task as running - this may no longer be necessary now that it's a task.
    set_config('running', time(), 'block_backadel');

    // Do the deed for each course and log the status.
    foreach ($backups as $b) {
        $course = $DB->get_record('course', array('id' => $b->coursesid));

        echo "\n" . get_string('backing_up', 'block_backadel') . ' ' . $course->shortname . "\n";

        if (!backadel_backup_course($course)) {
            $error = true;
            $errorlog .= get_string('cron_backup_error', 'block_backadel', $course->shortname) . "\n";
        }

        $b->status = $error ? 'FAIL' : 'SUCCESS';
        $DB->update_record('block_backadel_statuses', $b);
    }

    // Clear the task run-status - this may no longer be necessary now that it's a task.
    set_config('running', '', 'block_backadel');

    // Email administrators the status of the task.
    backadel_email_admins($errorlog);

    return true;
}
