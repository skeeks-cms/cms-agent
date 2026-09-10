<?php
define('YII_ENABLE_ERROR_HANDLER', false);
// Isolated SQLite database in memory. Never loads project config or runs a command.
$vendor = (getenv('SKEEKS_APP_ROOT') ?: '/app').'/vendor';
require $vendor.'/autoload.php';
require $vendor.'/yiisoft/yii2/Yii.php';

use skeeks\cms\agent\CmsAgent;
use skeeks\cms\agent\CmsAgentComponent;
use skeeks\cms\agent\models\CmsAgentModel;
use skeeks\cms\job\JobRegistry;

class AgentFixtureUser extends yii\web\User
{
    public $allowed = true;
    public function can($permissionName, $params = [], $allowCaching = true) { return $this->allowed; }
}
class AgentFixtureJobs extends yii\base\Component
{
    public $registry;
    public $pushed = [];
    public function getRegistry() { return $this->registry; }
    public function push($type, $payload, $options) {
        $this->pushed[] = compact('type', 'payload', 'options');
        return (object)['id' => count($this->pushed)];
    }
}
$app = new yii\web\Application([
    'id' => 'agent-native-test', 'basePath' => __DIR__, 'vendorPath' => $vendor,
    'extensions' => [],
    'components' => [
        'i18n' => ['translations' => ['skeeks/agent' => ['class' => yii\i18n\PhpMessageSource::class, 'basePath' => dirname(__DIR__).'/src/messages']]],
        'db' => ['class' => yii\db\Connection::class, 'dsn' => 'sqlite::memory:'],
        'cache' => ['class' => yii\caching\DummyCache::class],
        'request' => ['cookieValidationKey' => 'isolated-fixture', 'scriptFile' => __FILE__, 'scriptUrl' => '/index.php'],
        'user' => ['class' => AgentFixtureUser::class, 'identityClass' => skeeks\cms\models\CmsUser::class, 'enableSession' => false],
        'cmsAgent' => ['class' => CmsAgentComponent::class, 'onHitsEnabled' => false],
        'skeeks' => new class extends yii\base\Component { public $site; },
        'jobs' => ['class' => AgentFixtureJobs::class],
    ],
]);
$app->skeeks->site = (object)['id' => 1];
$app->jobs->registry = new JobRegistry(['types' => [
    'test.native' => ['title' => 'Test native job', 'queue' => 'default',
        'handler' => skeeks\cms\job\handlers\ConsoleCommandJobHandler::class, 'permission' => 'test.run'],
]]);
$app->db->createCommand('CREATE TABLE cms_agent (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, description TEXT, job_type TEXT, job_payload TEXT, cms_site_id INTEGER, last_exec_at INTEGER, next_exec_at INTEGER, agent_interval INTEGER, priority INTEGER, is_system INTEGER, is_active INTEGER, is_running INTEGER, is_period INTEGER)')->execute();
$app->db->createCommand('CREATE TABLE cms_job_run (id INTEGER PRIMARY KEY, job_type TEXT, cms_site_id INTEGER, payload_json TEXT)')->execute();
$checks = 0;
$check = function ($condition, $message) use (&$checks) {
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
};
$make = function () { return new CmsAgentModel(['name' => 'Native schedule', 'executionMode' => 'job', 'job_type' => 'test.native']); };
$agent = $make();
$check($agent->validate(), 'Valid native schedule: '.json_encode($agent->errors));
$check($agent->save(), 'Native schedule saved');
$agent->job_payload = '{"site_id":123}';
$next = $agent->next_exec_at;
$agent->pushJob(true);
$check($app->jobs->pushed[0]['payload'] === ['site_id' => 123], 'Payload passed to job');
$check($app->jobs->pushed[0]['options']['triggerType'] === 'manual' && $agent->next_exec_at === $next, 'Manual push does not reschedule');
$agent->pushJob(false);
$check($app->jobs->pushed[0]['options']['dedupKey'] === $app->jobs->pushed[1]['options']['dedupKey'], 'Manual and scheduled dedup keys match');
$check($app->jobs->pushed[1]['options']['overlapPolicy'] === 'skip', 'Overlap remains skip');
foreach (['0', '[]', 'null', '"text"', '{bad'] as $json) {
    $agent = $make(); $agent->job_payload = $json;
    $check(!$agent->validate() && $agent->hasErrors('job_payload'), 'Reject invalid object: '.$json);
}
$agent = $make(); $agent->job_type = '';
$check(!$agent->validate() && $agent->hasErrors('job_type'), 'Empty type rejected in job mode');
$agent = $make(); $agent->job_type = 'missing.type';
$check(!$agent->validate() && $agent->isJobBased, 'Unknown type fails closed');
$app->user->allowed = false;
$agent = $make();
$check(!$agent->validate() && $agent->hasErrors('job_type'), 'Job permission enforced');
$app->user->allowed = true;
$agent = $make(); $agent->executionMode = 'console'; $agent->name = 'test/command';
$check($agent->validate() && !$agent->isJobBased && $agent->job_type === null, 'Explicit conversion to console');
$legacy = new CmsAgentModel(['name' => 'test/legacy']);
$check($legacy->validate() && !$legacy->isJobBased, 'Legacy console preserved');

$app->cmsAgent->commands = ['test/legacy' => ['class' => CmsAgent::class, 'interval' => 60]];
$app->cmsAgent->jobs = ['native-daily' => ['jobType' => 'test.native', 'name' => 'Native daily', 'interval' => 86400, 'jobPayload' => ['site_id' => 123]]];
$app->cmsAgent->initConfigs()->initConfigs();
$check($app->cmsAgent->jobs['native-daily']->command === null, 'Native config needs no command');
$app->cmsAgent->loadAgents()->loadAgents();
$system = CmsAgentModel::findOne(['name' => 'job:native-daily']);
$check($system && $system->jobPayload === ['site_id' => 123], 'Config persisted type and payload');
$check(CmsAgentModel::find()->where(['name' => 'job:native-daily'])->count() == 1, 'Loading is idempotent');
$system->name = 'changed';
$check(!$system->validate() && $system->hasErrors('name'), 'System fields protected server-side');
$app->cmsAgent->jobs['native-daily']->interval = 300;
$app->cmsAgent->loadAgents();
$check(CmsAgentModel::findOne(['name' => 'job:native-daily'])->agent_interval == 300, 'Config may update system fields');
$app->skeeks->site = (object)['id' => 2];
$app->cmsAgent->loadAgents();
$check(CmsAgentModel::find()->where(['name' => 'job:native-daily'])->count() == 2, 'Config synchronization scoped by site');
$app->skeeks->site = (object)['id' => 1];
$app->cmsAgent->jobs['broken'] = ['jobType' => 'missing.type', 'interval' => 60];
$before = CmsAgentModel::find()->count();
try { $app->cmsAgent->loadAgents(); throw new LogicException('Expected validation failure'); }
catch (yii\base\Exception $error) { $check(CmsAgentModel::find()->count() == $before, 'Config sync rollback'); }

$app->set('jobs', null);
$agent = $make();
$check(!$agent->validate() && $agent->isJobBased, 'Missing cms-job never falls back to console');
$check((new CmsAgentModel(['name' => 'test/legacy']))->validate(), 'Legacy remains valid without cms-job');
$broken = CmsAgentModel::findOne(['name' => 'job:native-daily', 'cms_site_id' => 1]);
$broken->is_active = 0;
$check($broken->save(), 'Existing job can be disabled when runtime is missing');
$broken->is_active = 1;
$check(!$broken->validate(), 'Cannot reactivate without runtime');
$broken->executionMode = '';
$broken->name = 'forged';
$check(!$broken->validate(), 'Empty mode cannot bypass system protection');
$controller = (new ReflectionClass(\skeeks\cms\agent\controllers\AdminCmsAgentController::class))->newInstanceWithoutConstructor();
$now = 2000000000;
CmsAgentModel::deleteAll();
$check($controller->scheduleHealth($now)['state'] === 'default', 'Empty schedules do not imply health');
$insertSchedule = function ($site, $active, $next) use ($app) {
    $app->db->createCommand()->insert('cms_agent', [
        'name' => 'fixture', 'cms_site_id' => $site, 'is_active' => $active, 'next_exec_at' => $next,
    ])->execute();
    return $app->db->getLastInsertID();
};
$insertSchedule(2, 1, $now - 600);
$insertSchedule(1, 0, $now - 600);
$check($controller->scheduleHealth($now)['state'] === 'default', 'Other sites and disabled agents excluded');
$scheduleId = $insertSchedule(1, 1, $now - 60);
$check($controller->scheduleHealth($now)['state'] === 'success', 'Exactly sixty seconds is not overdue');
CmsAgentModel::updateAll(['next_exec_at' => $now - 61], ['id' => $scheduleId]);
$check($controller->scheduleHealth($now)['state'] === 'warning', 'Sixty-one seconds warns without cms-job');
CmsAgentModel::updateAll(['next_exec_at' => $now + 60], ['id' => $scheduleId]);
$check($controller->scheduleHealth($now)['state'] === 'success', 'Future execution is healthy');
CmsAgentModel::updateAll(['next_exec_at' => null], ['id' => $scheduleId]);
$check($controller->scheduleHealth($now)['state'] === 'warning', 'Missing dates do not imply health');
echo "PASS: {$checks} native schedule checks (SQLite in memory)\n";
