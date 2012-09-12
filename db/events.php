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
