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
 * Definition of the grade_forecast_report class is defined
 *
 * @package    block_backadel
 * @copyright  2016 Louisiana State University, Chad Mazilly, Robert Russo, Dave Elliott
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains the event handler class for block_backadel.
 *
 * @package    block_backadel
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
        $backadelpath = get_config('block_backadel', 'path');

        if (empty($backadelpath)) {
            return array();
        }

        $backadelpath = "$CFG->dataroot$backadelpath";
        $bysearch = function ($file) use ($search) {
            return preg_match("/{$search}/i", $file);
        };

        $tobackup = function ($file) use ($backadelpath) {
            $backadel = new stdClass;
            $backadel->id = $file;
            $backadel->filename = $file;
            $backadel->filesize = filesize($backadelpath . $file);
            $backadel->timemodified = filemtime($backadelpath . $file);

            return $backadel;
        };

        $potentials = array_filter(scandir($backadelpath), $bysearch);
        return array_map($tobackup, $potentials);
    }

    public static function selected_backadel($data) {
        global $CFG;

        $backadelpath = get_config('block_backadel', 'path');

        $realpath = $CFG->dataroot . $backadelpath . $data->fileid;

        if (!file_exists($realpath)) {
            return true;
        }

        copy($realpath, $data->to_path);
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
