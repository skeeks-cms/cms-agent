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




Native job schedules
--------------------

With `skeeks/cms-job` installed, a schedule can enqueue a registered job
directly, without a console command. In the standard admin form select
«Фоновое задание», choose a registered type and supply a JSON object of
handler parameters (or leave it empty). The queue belongs to the job type,
not to the schedule. A worker is required to execute queued work.

Packages register system schedules under `cmsAgent.jobs` using a stable code:

```php
'components' => [
    'cmsAgent' => [
        'jobs' => [
            'daily-export' => [
                'jobType' => 'my.export', // Must exist in jobRegistry.types.
                'name' => 'Daily export',
                'interval' => 86400,
                'jobPayload' => ['catalog_id' => 123],
            ],
        ],
    ],
],
```

«Загрузить агенты» synchronizes these records as `job:<code>` within the
current CMS site. The code is the persistent schedule identity; changing it
creates a different schedule. System fields are edited in package config,
not the admin form. No new schema migration is needed beyond the existing
`job_type` / `job_payload` migration.

Existing `cmsAgent.commands` (including their optional `jobType` bridge)
remain supported. Unknown job types and missing `cms-job` fail closed: the
schedule name is never executed as a console command. An existing broken
schedule can still be disabled without changing its job configuration.

Manual and scheduled pushes use the same deduplication key and `skip`
overlap policy. Manual start does not change the next scheduled time.
The standard card and related run-history tab separate schedule timestamps
from job execution state. Job-type permissions are enforced by validation
and the manual-start endpoint.

Run isolated checks (SQLite in memory, no project configuration loaded):

```bash
SKEEKS_APP_ROOT=/app php vendor/skeeks/cms-agent/tests/native-schedules-smoke.php
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



