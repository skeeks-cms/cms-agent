<?php
return [

    'controllerMap' => [
        'migrate' => [
            'migrationPath' => [
                '@skeeks/cms/agent/migrations',
            ],
        ],
    ],

    'modules' => [
        'cmsAgent' => [
            'controllerNamespace' => 'skeeks\cms\agent\console\controllers',
        ],
    ],
];