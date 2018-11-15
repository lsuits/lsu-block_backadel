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

$handlers = array(
    'simple_restore_backup_list' => array(
        'handlerfile' => '/blocks/backadel/events.php',
        'handlerfunction' => array('backadel_event_handler', 'backup_list'),
        'schedule' => 'instant'
    ),
    'simple_restore_selected_backadel' => array(
        'handlerfile' => '/blocks/backadel/events.php',
        'handlerfunction' => array('backadel_event_handler', 'selected_backadel'),
        'schedule' => 'instant'
    )
);

$observers = array(
    array(
        'eventname' => '\block_simple_restore\event\simple_restore_backup_list',
        'callback'  => 'block_backadel_observer::simple_restore_backup_list',
    ),
    array(
        'eventname' => '\block_simple_restore\event\simple_restore_selected_backadel',
        'callback'  => 'block_simple_restore\event\simple_restore_backup_list',
    )
);
