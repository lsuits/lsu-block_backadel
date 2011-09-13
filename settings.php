<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $_s = function($key, $a=null) { return get_string($key, 'block_backadel', $a); };

    $suffix_choices = array(
        'username' => 'username',
        'idnumber' => 'idnumber',
        'fullname' => 'fullname'
    );

    $sched_url = new moodle_url('/admin/settings.php?section=automated');
    $schedule_link = html_writer::link($sched_url, $_s('sched_config'));

    $settings->add(new admin_setting_configselect('block_backadel_suffix', $_s('config_pattern'), $_s('config_pattern_desc'), 0, $suffix_choices));
    $settings->add(new admin_setting_configtext('block_backadel_size_limit', $_s('config_size_limit'), $_s('config_size_limit_desc'), ''));
    $settings->add(new admin_setting_pickroles('block_backadel_roles', $_s('config_roles'), $_s('config_roles_desc'), array()));
    $settings->add(new admin_setting_heading('block_backadel_sched_options', '', $schedule_link));
}
