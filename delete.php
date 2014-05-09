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
$header = $_s('delete_header');

//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
$PAGE->set_context($context);

$PAGE->navbar->add($header);
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);
$PAGE->set_url('/blocks/backadel/delete.php');

$PAGE->requires->js('/blocks/backadel/js/jquery.js');
$PAGE->requires->js('/blocks/backadel/js/toggle.js');

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

$delete_ids = optional_param('delete', null, PARAM_CLEAN);

if ($delete_ids) {
    $to_delete = array();

    foreach ($delete_ids as $id) {
        $fullname = $DB->get_field('course', 'fullname', array('id' => $id));

        if (backadel_delete_course($id)) {
            mtrace($_s('deleted', $fullname));
            $to_delete[] = $id;
        } else {
            mtrace($_s('delete_error'));
        }

        mtrace('<br />');
    }

    $where = 'coursesid IN (' . implode(', ', $to_delete) . ')';

    $DB->delete_records_select('block_backadel_statuses', $where);

    $OUTPUT->footer();
    die();
}

// List completed backups
$completed_ids = $DB->get_fieldset_select('block_backadel_statuses',
    'coursesid', 'status = "SUCCESS"');

if (!$completed_ids) {
    echo '<div>' . $_s('none_completed') . '</div>';

    $OUTPUT->footer();
    die();
}

$where = 'id IN (' . implode(', ', $completed_ids) . ')';

$courses = $DB->get_records_select('course', $where);

$table = new html_table();

$table->head = array($_m('shortname'), $_m('fullname'), $_s('delete'));
$table->data = array();

foreach ($courses as $c) {
    $url = new moodle_url('/course/view.php?id=' . $c->id);
    $link = html_writer::link($url, $c->shortname);

    $checkbox = html_writer::checkbox('delete[]', $c->id);

    $table->data[] = array($link, $c->fullname, $checkbox);
}

echo '<form action = "delete.php" method = "POST">';
echo html_writer::table($table);
echo html_writer::link('#', $_s('toggle_all'), array('class' => 'toggle_link'));
echo '    <input type = "submit" value = "' . $_s('delete_button') . '"/>';
echo '</form>';

echo $OUTPUT->footer();
