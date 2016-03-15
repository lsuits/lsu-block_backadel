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
 * A scheduled task for Backadel.
 *
 * @package    block_backadel
 * @copyright  2016 Louisiana State University, David Elliott, Robert Russo, Chad Mazilly <delliott@lsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_backadel\task;

//use block_backadel;

/**
 * A scheduled task class for Backing up courses using the LSU Backadel Block.
 */
require_once 'block_backadel.php';


class backup_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('backuptask', 'block_backadel');
    }

    /**
     * Run backups
     */
    public function execute() {
        global $CFG;
        // DWE - MAYBE PUT CRON FUNCTION CALL HERE?
        $block_backadel = new block_backadel();
        $block_backadel->cron();
        
        
        echo "I am executing the backup task";
        
        
        // This code is from CAS plugin 
        //if (is_enabled_auth('cas')) {
        //    $auth = get_auth_plugin('cas');
        //    $auth->sync_users(true);
        //}
    }

}
