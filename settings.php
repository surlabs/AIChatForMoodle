<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'mod_aichat/apikey',
        get_string('apikey', 'aichat'),
        '',
        '',
        PARAM_TEXT
    ));

    $models = [
        'gpt-4o' => 'GPT-4o',
        'gpt-4-turbo' => 'GPT-4 Turbo',
        'gpt-4' => 'GPT-4',
        'gpt-3.5-turbo-0125' => 'GPT-3.5 Turbo 0125',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo'
    ];

    $settings->add(new admin_setting_configselect(
        'mod_aichat/model',
        get_string('model', 'aichat'),
        '',
        '',
        $models,
        'gpt-4'
    ));
}
