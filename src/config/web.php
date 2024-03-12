<?php
return [

    'bootstrap' => ['cmsAgent'],

    'components' => [
        /*'cmsAgent' => [
            'onHitsEnabled' => true,
        ],*/

        'backendAdmin' => [
            'menu' => [
                'data' => [
                    'settings' => [
                        'items' => [
                            [
                                "name"  => ['skeeks/agent', "Agents"],
                                "url"   => ["cmsAgent/admin-cms-agent"],
                                "image" => ['skeeks\cms\agent\assets\CmsAgentAsset', 'icons/clock.png'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];