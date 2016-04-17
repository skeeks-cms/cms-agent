<?php
return [

    'bootstrap' => ['cmsAgent'],

    'components' =>
    [
        'cmsAgent' => [
            'class'     => 'skeeks\cms\agent\CmsAgentComponent',
            'onHits'    =>
            [
                'enabled' => true
            ]
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
            'class'         => 'skeeks\cms\agent\CmsAgentModule',
        ]
    ]
];