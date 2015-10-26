<?php

function build_sql_from_search($query, $constraints) {
    $sql = "SELECT co.id, co.fullname, co.shortname, co.idnumber, cat.name
        AS category FROM {course} co, {course_categories} cat WHERE
        co.category = cat.id AND (";

    $constraint_sqls = array();

    foreach ($constraints as $c) {
        if (in_array($c->operator, array('LIKE', 'NOT LIKE'))) {
            // (NOT) LIKE
            $parts = array();

            foreach (explode('|', $c->search_terms) as $s) {
                $parts[] = "$c->criteria $c->operator '%{$s}%'";
            }

            $constraint_sqls[] = '(' . implode(' OR ', $parts) . ')';
        } else {
            // (NOT) IN
            $in_str = str_replace('|', "', '", $c->search_terms);

            $constraint_sqls[] = "($c->criteria $c->operator ('$in_str'))";
        }
    }

    return $sql . implode(" $query->type ", $constraint_sqls) . ');';
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
    //$context = get_context_instance(CONTEXT_COURSE, $courseid);
    $context = context_course::instance($courseid);
    if ($field != 'fullname') {
        foreach ($roleids as $r) {
            if ($users = get_role_users($r, $context, false, 'u.' . $field)) {
                foreach ($users as $k => $v) {
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

    $safe_short = preg_replace($matchers, '-', $course->shortname);

    $backadel_file = "backadel-{$safe_short}{$suffix}.zip";

    $backadel_path = get_config('block_backadel', 'path');

    $file->copy_content_to($CFG->dataroot . $backadel_path . $backadel_file);

    $bc->destroy();
    unset($bc);

    return true;
}

function backadel_email_admins($errors) {
    $_s = function($key) { return get_string($key, 'block_backadel'); };

    $del_link = new moodle_url('/blocks/backadel/delete.php');

    $subject = $_s('email_subject');
    $from = $_s('email_from');
    $messagetext = $errors . "\n\n" . $_s('email_body') . $del_link;

    foreach (get_admins() as $admin) {
        email_to_user($admin, $from, $subject, $messagetext);
    }
}
