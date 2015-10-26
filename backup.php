<?php

require_once('../../config.php');

require_once('lib.php');

$_s = function($key) { return get_string($key, 'block_backadel'); };

require_login();

if (!is_siteadmin($USER->id)) {
    print_error('need_permission', 'block_backadel');
}

// Page Setup
$blockname = $_s('pluginname');
$header = $_s('job_sent');

//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
$PAGE->set_context($context);

$PAGE->navbar->add($header);
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);
$PAGE->set_url('/blocks/backadel/backup.php');

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

$backup_ids = required_param_array('backup', PARAM_INT);

$current_ids = $DB->get_fieldset_select('block_backadel_statuses', 'coursesid', '');

$dupes = array_intersect($current_ids, $backup_ids);
$dupes = !$dupes ? array() : $dupes;

$new_backup_ids = array_diff($backup_ids, $dupes);

foreach ($new_backup_ids as $id) {
    $status = new StdClass;
    $status->coursesid = $id;
    $status->status = 'BACKUP';

    $DB->insert_record('block_backadel_statuses', $status);
}

echo '<br />' . $_s('job_sent_body');

if ($dupes) {
    echo '<div style = "text-align:center" class = "error">';

    $select = 'coursesid IN(' . implode(', ', $dupes) . ')';

    $statuses = $DB->get_records_select('block_backadel_statuses', $select);

    $status_map = array(
        'SUCCESS' => $_s('already_successful'),
        'BACKUP' => $_s('already_scheduled'),
        'FAIL' => $_s('already_failed')
    );

    foreach($statuses as $s) {
        $params = array('id' => $s->coursesid);
        $shortname = $DB->get_field('course', 'shortname', $params);

        echo $shortname . ' ' . $status_map[$s->status] . '<br />';
    }

    echo '</div>';
}

echo $OUTPUT->footer();
