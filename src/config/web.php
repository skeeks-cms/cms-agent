<?php
return [

    'bootstrap' => ['cmsAgent'],

    'components' => [
        'cmsAgent' => [
            'onHitsEnabled' => true,
        ],

        'backendAdmin' => [
            'menu' => [
                'data' => [
                    'other' => [
                        'items' => [
                            [
                                "label" => ['skeeks/agent', "Agents"],
                                "img"   => ['skeeks\cms\agent\assets\CmsAgentAsset', 'icons/clock.png'],

                                'items' => [
                                    [
                                        "label" => ['skeeks/agent', "Agents"],
                                        "url"   => ["cmsAgent/admin-cms-agent"],
                                        "img"   => ['skeeks\cms\agent\assets\CmsAgentAsset', 'icons/clock.png'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];