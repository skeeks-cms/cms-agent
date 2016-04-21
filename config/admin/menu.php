<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.04.2016
 */
return
[
    'other' =>
    [
        'items' =>
        [
            [
                "label"     => \Yii::t('skeeks/agent', "Agents"),
                "img"       => ['skeeks\cms\agent\assets\CmsAgentAsset', 'icons/clock.png'],

                'items' =>
                [
                    [
                        "label"     => \Yii::t('skeeks/agent',"Agents"),
                        "url"       => ["cmsAgent/admin-cms-agent"],
                        "img"       => ['skeeks\cms\agent\assets\CmsAgentAsset', 'icons/clock.png'],
                    ],
                ],
            ],
        ]
    ]
];