Agents for SkeekS CMS
===================================

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist skeeks/cms-agent "*"
```

or add

```
"skeeks/cms-agent": "*"
```

Configuration app
----------

```php

'bootstrap' => ['cmsAgent'],

'components' =>
[
    'cmsAgent' => [
        'class'             => 'skeeks\cms\agent\CmsAgentComponent',
        'onHitsEnabled'     => true
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

```

How to enable execution on cron agents
----------------

#### Configuration app

```php

'components' =>
[
    'cmsAgent' => [
        'class'             => 'skeeks\cms\agent\CmsAgentComponent',
        'onHitsEnabled'     => false
    ],
]

```

#### Cront task

```bash
* * * * * cd /var/www/sites/you-site.com/ && php yii cmsAgent/execute
```




Links
------
* [Web site](https://cms.skeeks.com)
* [Author](https://skeeks.com)
* [ChangeLog](https://github.com/skeeks-cms/cms-agent/blob/master/CHANGELOG.md)


___

> [![skeeks!](https://skeeks.com/img/logo/logo-no-title-80px.png)](https://skeeks.com)  
<i>SkeekS CMS (Yii2) — quickly, easily and effectively!</i>  
[skeeks.com](https://skeeks.com) | [cms.skeeks.com](https://cms.skeeks.com)



