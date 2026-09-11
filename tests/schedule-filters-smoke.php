<?php
// Reuses only the isolated SQLite fixture, never the project database.
require __DIR__.'/native-schedules-smoke.php';
use skeeks\cms\agent\helpers\ScheduleFilters;
use skeeks\cms\agent\models\CmsAgentModel;

$app->db->createCommand('ALTER TABLE cms_job_run ADD COLUMN dedup_key TEXT')->execute();
$app->db->createCommand('ALTER TABLE cms_job_run ADD COLUMN dedup_active TEXT')->execute();
$app->db->createCommand('ALTER TABLE cms_job_run ADD COLUMN status TEXT')->execute();
CmsAgentModel::deleteAll();
$app->set('jobs', ['class' => AgentFixtureJobs::class]);
$app->jobs->registry = new skeeks\cms\job\JobRegistry(['types' => [
    'test.native' => ['handler' => skeeks\cms\job\handlers\ConsoleCommandJobHandler::class, 'permission' => 'test.run'],
]]);
$app->cmsAgent->commands = ['test/bridge' => ['jobType' => 'test.native']];
$add = function ($id, $name, $type = null, $running = 0, $site = 1) use ($app) {
    $app->db->createCommand()->insert('cms_agent', ['id' => $id, 'name' => $name,
        'job_type' => $type, 'cms_site_id' => $site, 'is_running' => $running])->execute();
};
$run = function ($id, $agent, $status, $active = false, $site = 1) use ($app) {
    $app->db->createCommand()->insert('cms_job_run', ['id' => $id, 'job_type' => 'test.native',
        'cms_site_id' => $site, 'dedup_key' => 'cms_agent:'.$agent,
        'dedup_active' => $active ? 'cms_agent:'.$agent : null, 'status' => $status])->execute();
};
$add(1, 'direct'); $add(2, 'direct-running', null, 1);
$add(3, 'native', 'test.native'); $add(4, 'test/bridge');
$add(5, 'queued', 'test.native', 1); $add(6, 'running', 'test.native');
$add(7, 'no-history', 'test.native'); $add(8, 'broken', 'missing');
$add(9, 'job:missing'); $add(10, 'other-site', null, 1, 2);
$add(11, 'foreign-resource', 'test.native'); $add(12, 'timed-out', 'test.native');
$run(1, 3, 'failed'); $run(2, 3, 'succeeded');
$run(3, 4, 'succeeded_with_warnings'); $run(4, 5, 'queued', true);
$run(5, 6, 'running', true); $run(6, 11, 'running', true, 2);
$run(7, 12, 'timed_out');
$select = function ($filters) {
    $query = CmsAgentModel::find()->where(['cms_site_id' => 1]);
    $filter = new ScheduleFilters();
    foreach ($filters as $attribute => $value) { $filter->apply($query, $attribute, $value); }
    return array_map('intval', $query->select('id')->orderBy('id')->column());
};
$check($select(['execution_mode' => 'console']) === [1, 2], 'Direct mode excludes configured bridges and broken job markers');
$check($select(['execution_result' => 'success']) === [3], 'Latest success replaces old failure');
$check($select(['execution_result' => 'warning']) === [4], 'Configured bridge warnings');
$check($select(['execution_result' => 'error']) === [12], 'Timeout is an error');
$check($select(['execution_state' => 'running']) === [2, 6], 'Direct running and queue running; other sites excluded');
$check($select(['execution_state' => 'queued']) === [5], 'Queue ignores stale agent running flag');
$check($select(['execution_state' => 'idle']) === [1, 3, 4, 7, 12], 'Idle includes never-started valid schedules');
$check($select(['execution_state' => 'unknown']) === [8, 9, 11], 'Broken or foreign-resource state is unavailable');
$check($select(['execution_mode' => 'console', 'execution_result' => 'success']) === [], 'Combined filters and zero matches');
$check(count($select(['execution_state' => ''])) === 11, 'Empty value does not filter');
$run(8, 3, 'cancelled');
$check($select(['execution_result' => 'cancelled']) === [3], 'Cancellation has its own result');
$app->user->allowed = false;
$check($select(['execution_result' => 'success']) === [], 'Denied job results are not disclosed');
$app->set('jobs', null);
$check($select(['execution_state' => 'running']) === [2], 'Direct state works without cms-job');
$check($select(['execution_result' => 'success']) === [], 'No invented results without cms-job');
echo "PASS: {$checks} total checks including schedule filters\n";
