<?php

require_once('../../config.php');

$_s = function($key, $a=NULL) { return get_string($key, 'block_backadel', $a); };

require_login();

if (!is_siteadmin($USER->id)) {
    print_error('need_permission', 'block_backadel');
}

// Page Setup
$blockname = $_s('pluginname');
$header = $_s('build_search');

//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
$PAGE->set_context($context);

$PAGE->navbar->add($header);
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);
$PAGE->set_url('/blocks/backadel/index.php');

$PAGE->requires->js('/blocks/backadel/js/jquery.js');
$PAGE->requires->js('/blocks/backadel/js/index.js');

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

echo html_writer::tag('div', '', array(
    'id' => 'results_error', 'class' => 'backadel_error'
));

$options = array('ALL', 'ANY');

$controls = html_writer::tag('div',
    $_s('match') .
    html_writer::select(array_combine($options, $options), 'type', 'ALL', null) .
    $_s('of_these_constraints') .
    html_writer::empty_tag('img',
        array('src' => 'images/delete.png', 'class' => 'delete_constraint')) .
    html_writer::empty_tag('img',
        array('src' => 'images/add.png', 'class' => 'add_constraint')),
    array('id' => 'anyall_row'));

$options = array(
    get_string('shortname'), get_string('fullname'),
    $_s('course_id'), get_string('category')
);
$options = array_combine($options, $options);

$crit = html_writer::select($options, 'c0_criteria', '', null);

$options = array(
    $_s('is'), $_s('is_not'), $_s('contains'), $_s('does_not_contain'));
$options = array_combine($options, $options);

$op = html_writer::select($options, 'c0_operator', '', null);

$span = html_writer::tag('span',
    html_writer::empty_tag('input',
    array('name' => 'c0_search_term_0', 'type' => 'text')) .
    html_writer::empty_tag('img',
    array('src' => 'images/add.png', 'class' => 'add_search_term')) .
    html_writer::empty_tag('input',
    array('id' => 'c0_st_num', 'value' => 1, 'type' => 'hidden')),
    array('id' => 'c0_search_term_0'));

$group = html_writer::tag('div',
    html_writer::tag('div', $crit . $op . $span,
    array('class' => 'constraint', 'id' => 'c0_constraint')),
    array('id' => 'group_constraints'));

$button = html_writer::tag('div',
    html_writer::empty_tag('input', array(
        'type' => 'submit', 'value' => $_s('build_search_button')
    )), array('id' => 'button')
);

$form_container = html_writer::tag('div', $controls . $group . $button, array(
    'id' => 'form_container'
));

echo html_writer::tag('form', $form_container, array(
    'id' => 'query', 'action' => 'results.php', 'method' => 'POST'
));

echo $OUTPUT->footer();
