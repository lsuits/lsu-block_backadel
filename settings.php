<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot. '/blocks/backadel/settingslib.php');

    $_s = function($key, $a=null) { return get_string($key, 'block_backadel', $a); };

    $suffix_choices = array(
        'username' => 'username',
        'idnumber' => 'idnumber',
        'fullname' => 'fullname'
    );

    $sched_url = new moodle_url('/admin/settings.php?section=automated');
    $schedule_link = html_writer::link($sched_url, $_s('sched_config'));

    $settings->add(new backadel_path_setting('block_backadel/path', $_s('config_path'), $_s('config_path_desc', $CFG->dataroot), ''));
    $settings->add(new admin_setting_configselect('block_backadel/suffix', $_s('config_pattern'), $_s('config_pattern_desc'), 0, $suffix_choices));
    $settings->add(new admin_setting_configtext('block_backadel/size_limit', $_s('config_size_limit'), $_s('config_size_limit_desc'), ''));
    $settings->add(new admin_setting_pickroles('block_backadel/roles', $_s('config_roles'), $_s('config_roles_desc'), array()));
    $settings->add(new admin_setting_heading('block_backadel/sched_options', '', $schedule_link));
}
