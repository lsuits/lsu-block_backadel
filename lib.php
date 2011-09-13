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
    global $CFG;

    $suffix = '';
    $field = $CFG->block_backadel_suffix;
    $roleids = explode(',', $CFG->block_backadel_roles);
    $context = get_context_instance(CONTEXT_COURSE, $courseid);

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
    global $CFG, $DB;

    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/controller/backup_controller.class.php');
    require_once($CFG->dirroot . '/backup/util/helper/backup_cron_helper.class.php');

    $time = time();

    if (!backup_cron_automated_helper::launch_automated_backup($course, $time, 2)) {
        return false;
    }

    $params = array('component' => 'backup', 'filearea' => 'automated');

    $old_file = reset($DB->get_records('files', $params, 'id DESC', '*', 0, 1));
    $old_fileid = $old_file->id;

    $suffix = generate_suffix($course->id);
    $filename = "backadel-$time-$course->shortname-$suffix.mbz";

    $new_file = new stdClass();
    $new_file->component = 'backadel';
    $new_file->filearea = 'backups';
    $new_file->contextid = get_context_instance(CONTEXT_SYSTEM)->id;
    $new_file->filename = $filename;

    $fs = get_file_storage();

    if (!$fs->create_file_from_storedfile($new_file, $old_fileid)) {
        die();
    }

    $stored_file = $fs->get_file_instance($old_file);
    $stored_file->delete();

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
