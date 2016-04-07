<?php

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

    // Simple Restore events

    array(
        'eventname' => '\block_simple_restore\event\simple_restore_backup_list',
        'callback'  => 'block_backadel_observer::simple_restore_backup_list',
    ),

    array(
        'eventname' => '\block_simple_restore\event\simple_restore_selected_backadel',
        'callback'  => 'block_simple_restore\event\simple_restore_backup_list',
    )
);