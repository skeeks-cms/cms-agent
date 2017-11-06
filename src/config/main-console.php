<?php
return [

    'components' =>
    [
        'cmsAgent' => [
            'class'     => 'skeeks\cms\agent\CmsAgentComponent',
        ],

        'i18n' => [
            'translations' =>
            [
                'skeeks/agent' => [
                    'class'             => 'yii\i18n\PhpMessageSource',
                    'basePath'          => '@skeeks/cms/agent/messages',
                    'fileMap' => [
                        'skeeks/agent' => 'main.php',
                    ],
                ]
            ]
        ]
    ],

    'modules' =>
    [
        'cmsAgent' => [
            'class'                 => 'skeeks\cms\agent\CmsAgentModule',
            'controllerNamespace'   => 'skeeks\cms\agent\console\controllers'
        ]
    ]
];