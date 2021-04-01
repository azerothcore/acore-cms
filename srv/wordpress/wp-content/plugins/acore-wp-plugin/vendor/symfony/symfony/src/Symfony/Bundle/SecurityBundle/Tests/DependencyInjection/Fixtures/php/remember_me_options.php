<?php

$container->loadFromExtension('security', [
    'providers' => [
        'default' => ['id' => 'foo'],
    ],

    'firewalls' => [
        'main' => [
            'form_login' => true,
            'remember_me' => [
                'secret' => 'TheSecret',
                'catch_exceptions' => false,
                'token_provider' => 'token_provider_id',
            ],
            'logout_on_user_change' => true,
        ],
    ],
]);
