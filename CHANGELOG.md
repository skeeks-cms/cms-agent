CHANGELOG
==============

3.2.2 — 2026-09-10
-----------------
 * Подсветка строк по результату фонового задания стандартными цветами админки.
 * Предупреждение о задержке расписания активных агентов более чем на минуту.
 * Дата последнего запуска оставлена в карточке агента, системные агенты отмечены замочком.
 * Проверены границы задержки, активность и изоляция сайтов; новые миграции не требуются.

3.2.0 — 2026-09-08
-----------------
 * Native job schedules via cmsAgent.jobs, without a console command.
 * Standard admin form, schedule card and list support job types and JSON parameters.
 * Job registry and permission validation; safe handling of unavailable job types.
 * System schedule fields are protected; unavailable schedules can still be disabled.
 * Transactional, site-scoped synchronization of configured schedules.
 * Existing console commands and queue bridges remain supported.
 * Added 28 isolated native-schedule regression checks.

2.0.0
-----------------
 * Ready
 
2.0.0-alpha2
-----------------
 * Support pgsql
 
2.0.0-alpha
-----------------
 * Change configs
 
1.3.0
-----------------
 * SkeekS CMS ~ 5.0
 
1.2.0
-----------------
 * Using composer-config-plugin
 
1.1.0.1
-----------------
  * Rename deactivate button
  
  
1.1.0
-----------------
  * Update cms

1.0.2
-----------------
  * Fixed #35 [https://github.com/skeeks-cms/cms/issues/35] (Errors in JavaScript code in the behavior of the widget in the admin table)

1.0.1
-----------------
  * Added the ability to stop the forced running agents
  * Fixed some translation

1.0.0.2
-----------------
  * Fixed image loader

1.0.0.1
-----------------
  * Change info

1.0.0
-----------------
  * Stable release
  
1.0.0-rc3
-----------------
  * Automatic updating of the table with the agents
  * Fixed an issue with copying large data
  * Fixed a problem with the launch of many agents

1.0.0-rc2
-----------------
  * Fixed errors

1.0.0-rc1
-----------------
  * Added migration field have been changed TEXT on the field LONGTEXT
  * Changed login process
  * Added load indicator
  * Is running view

1.0.0-beta
-----------------
  * All translated

1.0.0-alpha
-----------------
  * Can be used
