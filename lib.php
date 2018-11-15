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

/**
 * Build the SQL query from the search params
 *
 * @return SQL
 */
function build_sql_from_search($query, $constraints) {
    $sql = "SELECT co.id, co.fullname, co.shortname, co.idnumber, cat.name
        AS category FROM {course} co, {course_categories} cat WHERE
        co.category = cat.id AND (";

    // Set up the SQL constraints.
    $constraintsqls = array();

    // Loop through the provided constraints and build the SQL contraints.
    foreach ($constraints as $c) {
        if (in_array($c->operator, array('LIKE', 'NOT LIKE'))) {
            $parts = array();

            foreach (explode('|', $c->search_terms) as $s) {
                $parts[] = "$c->criteria $c->operator '%{$s}%'";
            }

            $constraintsqls[] = '(' . implode(' OR ', $parts) . ')';
        } else {
            $instr = str_replace('|', "', '", $c->search_terms);

            $constraintsqls[] = "($c->criteria $c->operator ('$instr'))";
        }
    }

    // Return the appropriate SQL.
    return $sql . implode(" $query->type ", $constraintsqls) . ');';
}

/**
 * Delete courses based on supplied courseids
 *
 * @return bool
 */
function backadel_delete_course($courseid) {
    global $DB;
    // Get the course object based on the supplied courseid.
    $course = $DB->get_record('course', array('id' => $courseid));

    // Delete the course.
    if (delete_course($course, false)) {
        fix_course_sortorder();
        return true;
    } else {
        return false;
    }
}

/**
 * Generates the last bit of the backup .zip's filename based on the
 * pattern and roles that the admin chose in config.
 *
 * @return $suffix
 */
function generate_suffix($courseid) {
    $suffix = '';

    // Grab the allowed suffixes.
    $field = get_config('block_backadel', 'suffix');

    // Grab the administratively selected roles.
    $roleids = explode(',', get_config('block_backadel', 'roles'));

    // Grab the course context.
    $context = context_course::instance($courseid);

    // When NOT using fullname (which we might want to avoid anyway).
    if ($field != 'fullname') {
        // Loop through all the administratively selected roles.
        foreach ($roleids as $r) {
            // If the role has any users in the course, return them.
            if ($users = get_role_users($r, $context, false)) {
                // Loop through the users and grab the appropriate suffix.
                foreach ($users as $k => $v) {
                    $suffix .= '_' . $v->$field;
                }
            }
        }
    } else {
        // Loop through all the administratively selected roles.
        foreach ($roleids as $r) {
            // If the role has any users in the course, return them.
            if ($users = get_role_users($r, $context, false)) {
                // Loop through the users and grab the appropriate suffix.
                foreach ($users as $k => $v) {
                    $suffix .= '_' . $v->firstname . $v->lastname;
                }
            }
        }
    }
    return $suffix;
}

/**
 * Instantiate the moodle backup subsystem
 * and backup the course.
 *
 * @return true
 */
function backadel_backup_course($course) {
    global $CFG;

    // Required files for the backups.
    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/controller/backup_controller.class.php');

    // Setup the backup controller.
    $bc = new backup_controller(backup::TYPE_1COURSE, $course->id,
        backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_AUTOMATED, 2);
    $outcome = $bc->execute_plan();
    $results = $bc->get_results();
    $file = $results['backup_destination'];
    $suffix = generate_suffix($course->id);
    $matchers = array('/\s/', '/\//');

    // Ensure the shortname is safe.
    $safeshort = preg_replace($matchers, '-', $course->shortname);

    // Name the file.
    $backadelfile = "backadel-{$safeshort}{$suffix}.zip";

    // Build the path.
    $backadelpath = get_config('block_backadel', 'path');

    // Copy the file to the destination.
    $file->copy_content_to($CFG->dataroot . $backadelpath . $backadelfile);

    // Kill the backup controller.
    $bc->destroy();
    unset($bc);

    return true;
}

/**
 * Email the admins
 *
 */
function backadel_email_admins($errors) {
    $dellink = new moodle_url('/blocks/backadel/delete.php');

    $subject = get_string('email_subject', 'block_backadel');
    $from = get_string('email_from', 'block_backadel');
    $messagetext = $errors . "\n\n" . get_string('email_body', 'block_backadel') . $dellink;

    foreach (get_admins() as $admin) {
        email_to_user($admin, $from, $subject, $messagetext);
    }
}
