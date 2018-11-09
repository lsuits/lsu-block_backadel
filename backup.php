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

require_once('../../config.php');

require_once('lib.php');

require_login();

if (!is_siteadmin($USER->id)) {
    print_error('need_permission', 'block_backadel');
}

// Page Setup.
$blockname = get_string('pluginname', 'block_backadel');
$header = get_string('job_sent', 'block_backadel');

// Context Setup.
$context = context_system::instance();

// Set the page context.
$PAGE->set_context($context);

// Add the navigationbar element.
$PAGE->navbar->add($header);

// Set the block name.
$PAGE->set_title($blockname);

// Set the page heading.
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);

// Set the page url.
$PAGE->set_url('/blocks/backadel/backup.php');

// Output hte header and heading.
echo $OUTPUT->header();
echo $OUTPUT->heading($header);

// Setup some basic needs for backing up courses.
$backupids = required_param_array('backup', PARAM_INT);
$currentids = $DB->get_fieldset_select('block_backadel_statuses', 'coursesid', '');
$dupes = array_intersect($currentids, $backupids);
$dupes = !$dupes ? array() : $dupes;
$newbackupids = array_diff($backupids, $dupes);

// Create records of the courses needed to be backed up.
foreach ($newbackupids as $id) {
    $status = new StdClass;
    $status->coursesid = $id;
    $status->status = 'BACKUP';
    $DB->insert_record('block_backadel_statuses', $status);
}

echo '<br />' . get_string('job_sent_body', 'block_backadel');

// Inform the user if they're trying to backup courses that
// have already been backed up or are scheduled for backup.
if ($dupes) {
    echo '<div style = "text-align:center" class = "error">';

    $select = 'coursesid IN(' . implode(', ', $dupes) . ')';

    $statuses = $DB->get_records_select('block_backadel_statuses', $select);

    $statusmap = array(
        'SUCCESS' => get_string('already_successful', 'block_backadel'),
        'BACKUP' => get_sting('already_scheduled', 'block_backadel'),
        'FAIL' => get_string('already_failed', 'block_backadel')
    );

    foreach ($statuses as $s) {
        $params = array('id' => $s->coursesid);
        $shortname = $DB->get_field('course', 'shortname', $params);
        echo $shortname . ' ' . $statusmap[$s->status] . '<br />';
    }
    echo '</div>';
}

// Output the footer of the page.
echo $OUTPUT->footer();
