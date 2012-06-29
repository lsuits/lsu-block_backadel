<?php

abstract class backadel_event_handler {
    public static function backadel_shortname($shortname) {
        if (preg_match('/\s/', $shortname)) {
            $matchers = array('/\s/', '/\//');

            return preg_replace($matchers, '-', $shortname);
        }

        return $shortname;
    }

    public static function backadel_criterion($course) {
        global $USER;

        $crit = get_config('block_backadel', 'suffix');

        if (empty($crit)) {
            return "";
        }

        $search = $crit == 'username' ? '_' . $USER->username : $course->{$crit};
        return "{$search}[_\.]";
    }

    public static function backadel_backups($search) {
        global $CFG;

        $backadel_path = get_config('block_backadel', 'path');

        if (empty($backadel_path)) {
            return array();
        }

        $backadel_path = "$CFG->dataroot$backadel_path";

        $by_search = function ($file) use ($search) {
            return preg_match("/{$search}/i", $file);
        };

        $to_backup = function ($file) use ($backadel_path) {
            $backadel = new stdClass;
            $backadel->id = $file;
            $backadel->filename = $file;
            $backadel->filesize = filesize($backadel_path . $file);
            $backadel->timemodified = filemtime($backadel_path . $file);

            return $backadel;
        };

        $potentials = array_filter(scandir($backadel_path), $by_search);

        return array_map($to_backup, $potentials);
    }

    public static function selected_backadel($data) {
        global $CFG;

        $backadel_path = get_config('block_backadel', 'path');

        $real_path = $CFG->dataroot . $backadel_path . $data->fileid;

        if (!file_exists($real_path)) {
            return true;
        }

        copy($real_path, $data->to_path);

        $data->filename = $data->fileid;

        return true;
    }

    public static function backup_list($data) {
        global $DB, $OUTPUT;

        if (isset($data->shortname)) {
            $search = self::backadel_shortname($data->shortname);
        } else {
            $course = $DB->get_record('course', array('id' => $data->courseid));
            $search = self::backadel_criterion($course);
        }

        $list = new stdClass;
        $list->header = get_string('semester_backups', 'block_backadel');
        $list->backups = self::backadel_backups($search);
        $list->order = 10;
        $list->html = '';

        if (!empty($list->backups)) {
            $list->html = $OUTPUT->heading($list->header);
            $list->html .= simple_restore_utils::build_table(
                $list->backups,
                'backadel',
                $data->courseid,
                $data->restore_to
            );
        }

        $data->lists[] = $list;

        return true;
    }
}
