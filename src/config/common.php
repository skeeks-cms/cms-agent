<?php
return [
    'components' => [
        'cmsAgent' => [
            'class' => 'skeeks\cms\agent\CmsAgentComponent',
        ],

        'i18n' => [
            'translations' => [
                'skeeks/agent' => [
                    'class'    => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@skeeks/cms/agent/messages',
                    'fileMap'  => [
                        'skeeks/agent' => 'main.php',
                    ],
                ],
            ],
        ],

        'authManager' => [
            'config' => [
                'roles' => [
                    [
                        'name'  => \skeeks\cms\rbac\CmsManager::ROLE_ADMIN,
                        'child' => [
                            //Есть доступ к системе администрирования
                            'permissions' => [
                                "cmsAgent/admin-cms-agent",
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'modules' => [
        'cmsAgent' => [
            'class'               => 'skeeks\cms\agent\CmsAgentModule',
        ],
    ],
];