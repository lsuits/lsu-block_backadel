<?php

require_once('../../config.php');
require_once($CFG->libdir . '/quick_template.php');

$_s = function($key, $a=NULL) { return get_string($key, 'block_backadel', $a); };

require_login();

if (!is_siteadmin($USER->id)) {
    print_error('need_permission', 'block_backadel');
}

// Page Setup
$blockname = $_s('pluginname');
$header = $_s('build_search');

$context = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_context($context);

$PAGE->navbar->add($header);
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);
$PAGE->set_url('/blocks/backadel/index.php');

$PAGE->requires->js('/lib/jquery.js');
$PAGE->requires->js('/blocks/backadel/js/index.js');

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

quick_render('index.tpl', array(), 'block_backadel');

echo $OUTPUT->footer();
