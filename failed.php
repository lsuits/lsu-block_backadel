<?php

require_once('../../config.php');

require_once('lib.php');

$_s = function($key, $a=null) { return get_string($key, 'block_backadel', $a); };
$_m = function($key) { return get_string($key); };

require_login();

if (!is_siteadmin($USER->id)) {
    print_error('need_permission', 'block_backadel');
}

// Page Setup
$blockname = $_s('pluginname');
$header = $_s('failed_header');

//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
$PAGE->set_context($context);

$PAGE->navbar->add($header);
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);
$PAGE->set_url('/blocks/backadel/failed.php');

$PAGE->requires->js('/blocks/backadel/js/jquery.js');
$PAGE->requires->js('/blocks/backadel/js/toggle.js');

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

$clean_data = array();

if ($data = data_submitted()) {
    foreach ($data as $key => $value) {
        $clean_data[$key] = clean_param($value, PARAM_CLEAN);
    }

    foreach ($clean_data['failed'] as $id) {
        $status = $DB->get_record('block_backadel_statuses',
            array('coursesid' => $id));

        $status->status = 'BACKUP';

        $DB->update_record('block_backadel_statuses', $status);

        mtrace('<br />');
    }

    echo '<div>' . $_s('statuses_updated') . '</div>';
}

// List failed backups

$failed_ids = $DB->get_fieldset_select('block_backadel_statuses',
    'coursesid', 'status = "FAIL"');

if (!$failed_ids) {
    echo '<div>' . $_s('none_failed') . '</div>';

    $OUTPUT->footer();
    die();
}

$where = 'id IN (' . implode(', ', $failed_ids) . ')';

$courses = $DB->get_records_select('course', $where);

$table = new html_table();

$table->head = array($_m('shortname'), $_m('fullname'), $_s('failed'));
$table->data = array();

foreach ($courses as $c) {
    $url = new moodle_url('/course/view.php?id=' . $c->id);
    $link = html_writer::link($url, $c->shortname);

    $checkbox = html_writer::checkbox('failed[]', $c->id);

    $table->data[] = array($link, $c->fullname, $checkbox);
}

echo '<form action = "failed.php" method = "POST">';
echo html_writer::table($table);
echo html_writer::link('#', $_s('toggle_all'), array('class' => 'toggle_link'));
echo '    <input type = "submit" value = "' . $_s('failed_button') . '"/>';
echo '</form>';

echo $OUTPUT->footer();
