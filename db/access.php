<?php

$capabilities = array(
    'block/backadel:addinstance' => array(
                'riskbitmask' => RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_PREVENT,
            'teacher' => CAP_PREVENT,
            'admin' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
        ),
    );

?>
