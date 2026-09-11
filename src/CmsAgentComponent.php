<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.04.2016
 */

namespace skeeks\cms\agent;

use skeeks\cms\agent\models\CmsAgentModel;
use skeeks\cms\helpers\FileHelper;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\web\Application;
use Yii;

/**
 * @property [] $agentsConfig
 *
 * Class CmsAgentComponent
 * @package skeeks\cms\agent
 */
class CmsAgentComponent extends Component implements BootstrapInterface
{
    /**
     * @var bool Enabled agents on the hits
     */
    public $onHitsEnabled = true;

    /**
     * @var int Interval if enabled agents on the hits
     */
    public $onHitsInterval = 60;

    /**
     * @var string Custom php binary path for some hostings, defaults to common 'php'.
     *             Used in console script to invoke separate process for each agent.
     */
    public $phpBin = 'php';

    /**
     * @var int Maximum wait time for the agent
     */
    public $agentMaxExecuteTime = 7200; //2 hours

    /**
     * @var array
     */
    public $commands = [];

    /** Native job schedules keyed by a stable schedule code, not a console route. */
    public $jobs = [];


    public function bootstrap($application)
    {
        if ($application instanceof Application && $this->onHitsEnabled) {
            $key = 'Agents';
            Yii::beginProfile(\Yii::t('skeeks/agent', "Agents enabled on the hits"));

            $data = \Yii::$app->cache->get($key);
            if ($data === false) {
                Yii::beginProfile(\Yii::t('skeeks/agent', "Executing"));

                $result = \Yii::$app->console->execute("cd " . ROOT_DIR . '; php yii cmsAgent/execute;');
                \Yii::$app->cache->set($key, '1', (int)$this->onHitsInterval);

                Yii::endProfile(\Yii::t('skeeks/agent', "Executing"));
            }

            Yii::endProfile(\Yii::t('skeeks/agent', "Agents enabled on the hits"));
        }
    }

    /** Compare only fields owned by configuration; never write during page rendering. */
    public function getScheduleChanges(): array
    {
        $this->initConfigs();
        $schedules = $this->commands;
        foreach ($this->jobs as $code => $job) { $schedules['job:'.$code] = $job; }
        $changes = ['create' => [], 'update' => [], 'delete' => []];
        // Preserve the loader's historical no-op for an entirely empty configuration.
        if (!$schedules) { return $changes; }
        $siteId = Yii::$app->skeeks->site ? Yii::$app->skeeks->site->id : null;
        $existing = CmsAgentModel::find()->where(['cms_site_id' => $siteId])->all();
        $byName = [];
        foreach ($existing as $agent) { $byName[$agent->name] = $byName[$agent->name] ?? $agent; }
        $configuredNames = [];
        foreach ($schedules as $key => $command) {
            $native = strpos($key, 'job:') === 0;
            $name = $native ? $key : $command->command;
            $configuredNames[] = $name;
            $agent = isset($byName[$name]) ? clone $byName[$name] : new CmsAgentModel();
            $agent->scenario = CmsAgentModel::SCENARIO_CONFIG;
            $agent->name = $name;
            $agent->cms_site_id = $siteId;
            $attributes = [
                'agent_interval' => $command->interval,
                'is_period' => (int)$command->is_period,
                'description' => $command->name,
                'is_system' => 1,
            ];
            if ($native) {
                $attributes['job_type'] = $command->jobType;
                $agent->setJobPayload($command->jobPayload);
                $attributes['job_payload'] = $agent->job_payload;
                // JSON formatting and object-key order alone are not a configuration change.
                if (!$agent->isNewRecord) {
                    try {
                        $oldPayload = clone $byName[$name];
                        if ($this->normalizePayload($oldPayload->jobPayload) === $this->normalizePayload($command->jobPayload)) {
                            $attributes['job_payload'] = $oldPayload->job_payload;
                        }
                    } catch (\InvalidArgumentException $e) { /* Invalid stored JSON needs repair. */ }
                }
            }
            $changed = false;
            foreach ($attributes as $attribute => $value) {
                if ((string)$agent->getOldAttribute($attribute) !== (string)$value) { $changed = true; }
                $agent->$attribute = $value;
            }
            if ($agent->isNewRecord) { $changes['create'][] = $agent; }
            elseif ($changed) { $changes['update'][] = $agent; }
        }
        foreach ($existing as $agent) {
            if ($agent->is_system && !in_array($agent->name, $configuredNames, true)) {
                $changes['delete'][] = $agent;
            }
        }
        return $changes;
    }

    private function normalizePayload(array $payload): array
    {
        foreach ($payload as &$value) {
            if (is_array($value)) { $value = $this->normalizePayload($value); }
        }
        unset($value);
        if ($payload && array_keys($payload) !== range(0, count($payload) - 1)) { ksort($payload); }
        return $payload;
    }

    /** Synchronize the same differences shown in the administration, recalculated at execution. */
    public function loadAgents()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $changes = $this->getScheduleChanges();
            foreach (array_merge($changes['create'], $changes['update']) as $agent) {
                if (!$agent->save()) { throw new Exception(print_r($agent->errors, true)); }
            }
            foreach ($changes['delete'] as $agent) {
                if ($agent->delete() === false) { throw new Exception('Не удалось удалить устаревшее расписание.'); }
            }
            $transaction->commit();
        } catch (\Throwable $error) {
            $transaction->rollBack();
            throw $error;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function initConfigs()
    {
        if ($this->commands) {
            foreach ($this->commands as $command => $config) {
                if (strpos((string)$command, 'job:') === 0) {
                    throw new \yii\base\InvalidConfigException('The job: prefix is reserved for native schedules.');
                }
                if ($config instanceof CmsAgent) { continue; }
                if (is_string($config)) {
                    $config = ['class' => $config];
                }
                $config['command'] = $command;
                $this->commands[$command] = \Yii::createObject($config);
            }
        }

        foreach ($this->jobs as $code => $config) {
            if (!is_string($code) || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,127}$/D', $code)) {
                throw new \yii\base\InvalidConfigException('Job schedule code must be a stable identifier.');
            }
            if (!$config instanceof CmsAgent) {
                if (!is_array($config)) { throw new \yii\base\InvalidConfigException('Job schedule must be a configuration array.'); }
                $config['class'] = $config['class'] ?? CmsAgent::class;
                $config = \Yii::createObject($config);
            }
            if (!$config instanceof CmsAgent || !$config->jobType || $config->command) {
                throw new \yii\base\InvalidConfigException('Native schedule requires jobType and must not specify command.');
            }
            $this->jobs[$code] = $config;
        }

        return $this;
    }
}
