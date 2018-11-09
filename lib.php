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

function build_sql_from_search($query, $constraints) {
    $sql = "SELECT co.id, co.fullname, co.shortname, co.idnumber, cat.name
        AS category FROM {course} co, {course_categories} cat WHERE
        co.category = cat.id AND (";

    $constraintsqls = array();

    foreach ($constraints as $c) {
        if (in_array($c->operator, array('LIKE', 'NOT LIKE'))) {
            // Like or not like.
            $parts = array();

            foreach (explode('|', $c->search_terms) as $s) {
                $parts[] = "$c->criteria $c->operator '%{$s}%'";
            }

            $constraintsqls[] = '(' . implode(' OR ', $parts) . ')';
        } else {
            // In or not in.
            $instr = str_replace('|', "', '", $c->search_terms);

            $constraintsqls[] = "($c->criteria $c->operator ('$instr'))";
        }
    }

    return $sql . implode(" $query->type ", $constraintsqls) . ');';
}

function backadel_delete_course($courseid) {
    global $DB;

    $course = $DB->get_record('course', array('id' => $courseid));

    if (delete_course($course, false)) {
        fix_course_sortorder();
        events_trigger('course_deleted', $course);

        return true;
    } else {
        return false;
    }
}

// Generates the last bit of the backup .zip's filename based on the
// pattern and roles that the admin chose in config.
function generate_suffix($courseid) {
    $suffix = '';
    $field = get_config('block_backadel', 'suffix');
    $roleids = explode(',', get_config('block_backadel', 'roles'));
    $context = context_course::instance($courseid);
    if ($field != 'fullname') {
        foreach ($roleids as $r) {
            if ($users = get_role_users($r, $context, false, '')) {
                foreach ($users as $k => $v) {
                    echo '$k: ' . $k . '<br />';
		    echo '$v: ' . $v->$field . '<br />';
                    die();
                    $suffix .= '_' . $k;
                }
            }
        }
    } else {
        foreach ($roleids as $r) {
            if ($users = get_role_users($r, $context, false, 'u.firstname, u.lastname')) {
                foreach ($users as $k => $v) {
                    $suffix .= '_' . $v->firstname . $v->lastname;
                }
            }
        }
    }

    return $suffix;
}

function backadel_backup_course($course) {
    global $CFG;

    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/controller/backup_controller.class.php');

    $bc = new backup_controller(backup::TYPE_1COURSE, $course->id,
        backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_AUTOMATED, 2);

    $outcome = $bc->execute_plan();

    $results = $bc->get_results();

    $file = $results['backup_destination'];

    $suffix = generate_suffix($course->id);

    $matchers = array('/\s/', '/\//');

    $safeshort = preg_replace($matchers, '-', $course->shortname);

    $backadelfile = "backadel-{$safeshort}{$suffix}.zip";

    $backadelpath = get_config('block_backadel', 'path');

    $file->copy_content_to($CFG->dataroot . $backadelpath . $backadelfile);

    $bc->destroy();
    unset($bc);

    return true;
}

function backadel_email_admins($errors) {
    $dellink = new moodle_url('/blocks/backadel/delete.php');

    $subject = get_string('email_subject', 'block_backadel');
    $from = get_string('email_from', 'block_backadel');
    $messagetext = $errors . "\n\n" . get_string('email_body', 'block_backadel') . $dellink;

    foreach (get_admins() as $admin) {
        email_to_user($admin, $from, $subject, $messagetext);
    }
}
