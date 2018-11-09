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
 * Delete courses after backup.
 *
 * @package    block_backadel
 * @copyright  2008 onwards - Louisiana State University, David Elliott, Robert Russo, Chad Mazilly <delliott@lsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once('lib.php');
require_login();

// Check to make sure the user is the site admin.
if (!is_siteadmin($USER->id)) {
    print_error('need_permission', 'block_backadel');
}

// Page Setup.
$blockname = get_string('pluginname', 'block_backadel');
$header = get_string('delete_header', 'block_backadel');

// Set the page context.
$context = context_system::instance();
$PAGE->set_context($context);

// Set up the page itself and Moodle requirements.
$PAGE->navbar->add($header);
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);
$PAGE->set_url('/blocks/backadel/delete.php');
$PAGE->requires->js('/blocks/backadel/js/jquery.js');
$PAGE->requires->js('/blocks/backadel/js/toggle.js');

// Output the header.
echo $OUTPUT->header();
echo $OUTPUT->heading($header);

// Get the ids for courses to be deleted.
if (isset($_POST['delete'])) {
    $deleteids = $_POST['delete'];
} else {
    $deleteids = null;
}

// Delete the courses.
if ($deleteids) {
    $todelete = array();

    foreach ($deleteids as $id) {
        $id = clean_param($id, PARAM_INT);
        $fullname = $DB->get_field('course', 'fullname', array('id' => $id));

        // If the deletion is successful or fails, log it.
        if (backadel_delete_course($id)) {
            mtrace(get_string('deleted', 'block_backadel', $fullname));
            $todelete[] = $id;
        } else {
            mtrace(get_string('delete_error', 'block_backadel'));
        }
        mtrace('<br />');
    }

    $where = 'coursesid IN (' . implode(', ', $todelete) . ')';

    // Remove the "to be deleted" course from the backadel tables.
    $DB->delete_records_select('block_backadel_statuses', $where);

    // Output the page footer.
    $OUTPUT->footer();

    // Why am I doing this?
    die();
}

// List completed backups.
$completedids = $DB->get_fieldset_select('block_backadel_statuses',
    'coursesid', 'status = "SUCCESS"');

// If there are no completed backups, let us know.
if (!$completedids) {
    echo '<div>' . get_string('none_completed', 'block_backadel') . '</div>';

    // Output the page footer.
    $OUTPUT->footer();

    // Why am I doing this?
    die();
}

$where = 'id IN (' . implode(', ', $completedids) . ')';
$courses = $DB->get_records_select('course', $where);

// Build the table for course statuses.
$table = new html_table();
$table->head = array(get_string('shortname'), get_string('fullname'), get_string('delete', 'block_backadel'));
$table->data = array();

// Populate the table.
foreach ($courses as $c) {
    $url = new moodle_url('/course/view.php?id=' . $c->id);
    $link = html_writer::link($url, $c->shortname);
    $checkbox = html_writer::checkbox('delete[]', $c->id);
    $table->data[] = array($link, $c->fullname, $checkbox);
}

// Build the form to schedule deletions of courses.
echo '<form action = "delete.php" method = "POST">';
echo html_writer::table($table);
echo html_writer::link('#', get_string('toggle_all', 'block_backadel'), array('class' => 'toggle_link'));
echo '    <input type = "submit" value = "' . get_string('delete_button', 'block_backadel') . '"/>';
echo '</form>';

// Output the page footer.
echo $OUTPUT->footer();
