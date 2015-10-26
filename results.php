<?php

require_once('../../config.php');
require_once('lib.php');

$_s = function($key) { return get_string($key, 'block_backadel'); };
$_m = function($key) { return get_string($key); };

require_login();

if (!is_siteadmin($USER->id)) {
    print_error('need_permission', 'block_backadel');
}

// Page Setup
$blockname = $_s('pluginname');
$header = $_s('search_results');

//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
$PAGE->set_context($context);

$PAGE->navbar->add($header);
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);
$PAGE->set_url('/blocks/backadel/results.php');

$PAGE->requires->js('/blocks/backadel/js/jquery.js');
$PAGE->requires->js('/blocks/backadel/js/toggle.js');

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

$clean_data = array();

if (!$data = data_submitted()) {
    redirect(new moodle_url('/blocks/backadel/index.php'));
}

foreach ($data as $key => $value) {
    $clean_data[$key] = clean_param($value, PARAM_CLEAN);
}

$query = new stdClass;
$query->userid = $USER->id;
$query->type = $clean_data['type'] == 'ALL' ? 'AND' : 'OR';
$query->created_at = time();

$constraint_data = array();

foreach ($clean_data as $key => $value) {
    if ($key[0] == 'c' && is_numeric($key[1])) {
        $i = $key[1];

        if (empty($constraint_data[$i]) || !is_array($constraint_data[$i])) {
            $constraint_data[$i] = array();
            $constraint_data[$i]['search_terms'] = '';
        }

        if (substr($key, 3, 11) == 'search_term') {
            $constraint_data[$i]['search_terms'] .= '|' . $value;
        } else {
            $constraint_data[$i][substr($key, 3)] = $value;
        }
    }
}

$criteria = array(
    get_string('shortname') => 'co.shortname',
    get_string('fullname') => 'co.fullname',
    $_s('course_id') => 'co.idnumber',
    get_string('category') => 'cat.name'
);

$operators = array(
    $_s('is') => 'IN',
    $_s('is_not') => 'NOT IN',
    $_s('contains') => 'LIKE',
    $_s('does_not_contain') => 'NOT LIKE'
);

$constraints = array();

foreach ($constraint_data as $c) {
    $c['criteria'] = $criteria[$c['criteria']];
    $c['operator'] = $operators[$c['operator']];
    $c['search_terms'] = substr($c['search_terms'], 1);

    $constraints[] = (object) $c;
}

$results = $DB->get_records_sql(build_sql_from_search($query, $constraints));

$table = new html_table();

$table->head = array(
    $_m('shortname'),
    $_m('fullname'),
    $_m('category'),
    $_m('backup')
);

$table->data = array();

foreach ($results as $r) {
    $url = new moodle_url('/course/view.php', array('id' => $r->id));
    $link = html_writer::link($url, $r->shortname);

    $backup_checkbox = html_writer::checkbox('backup[]', $r->id);

    $row_data = array($link, $r->fullname, $r->category, $backup_checkbox);

    $table->data[] = $row_data;
}

echo '<form action = "backup.php" method = "POST">';
echo html_writer::table($table);
echo html_writer::link('#', $_s('toggle_all'), array('class' => 'backadel toggle_link'));
echo '    <input type = "submit" value = "' . $_s('backup_button') . '"/>';
echo '</form>';

echo $OUTPUT->footer();
