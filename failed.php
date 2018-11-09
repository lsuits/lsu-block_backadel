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
 * Backadel failed backups.
 *
 * @package    block_backadel
 * @copyright  2008 onwards - Louisiana State University, David Elliott, Robert Russo, Chad Mazilly <delliott@lsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');
require_login();

// Check to make sure the site admin is the person accessing the page.
if (!is_siteadmin($USER->id)) {
    print_error('need_permission', 'block_backadel');
}

// Page Setup.
$blockname = get_string('pluginname', 'block_backadel');
$header = get_string('failed_header', 'block_backadel');

// Set up the page context.
$context = context_system::instance();
$PAGE->set_context($context);

// Build the page header and Moodle requirements.
$PAGE->navbar->add($header);
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);
$PAGE->set_url('/blocks/backadel/failed.php');
$PAGE->requires->js('/blocks/backadel/js/jquery.js');
$PAGE->requires->js('/blocks/backadel/js/toggle.js');

// Output the page header.
echo $OUTPUT->header();
echo $OUTPUT->heading($header);

$cleandata = array();

// Get the list of failed courses.
if ($data = data_submitted()) {
    foreach ($data as $key => $value) {
        $cleandata[$key] = clean_param($value, PARAM_CLEAN);
    }

    // Mark the failed courses as failed.
    foreach ($cleandata['failed'] as $id) {
        $status = $DB->get_record('block_backadel_statuses',
            array('coursesid' => $id));
        $status->status = 'BACKUP';
        $DB->update_record('block_backadel_statuses', $status);
        mtrace('<br />');
    }

    echo '<div>' . get_string('statuses_updated', 'block_backadel') . '</div>';
}

// List failed backups.
$failedids = $DB->get_fieldset_select('block_backadel_statuses',
    'coursesid', 'status = "FAIL"');

if (!$failedids) {
    echo '<div>' . get_string('none_failed', 'block_backadel') . '</div>';
    $OUTPUT->footer();
    die();
}

$where = 'id IN (' . implode(', ', $failedids) . ')';
$courses = $DB->get_records_select('course', $where);

// Set up the table.
$table = new html_table();

// Build the tablle.
$table->head = array(get_string('shortname'), get_string('fullname'), get_string('failed', 'block_backadel'));
$table->data = array();

// Populate the table.
foreach ($courses as $c) {
    $url = new moodle_url('/course/view.php?id=' . $c->id);
    $link = html_writer::link($url, $c->shortname);
    $checkbox = html_writer::checkbox('failed[]', $c->id);
    $table->data[] = array($link, $c->fullname, $checkbox);
}

// Build the HTML form.
echo '<form action = "failed.php" method = "POST">';
echo html_writer::table($table);
echo html_writer::link('#', get_string('toggle_all', 'block_backadel'), array('class' => 'toggle_link'));
echo '    <input type = "submit" value = "' . get_string('failed_button', 'block_backadel') . '"/>';
echo '</form>';

// Output the page footer.
echo $OUTPUT->footer();
