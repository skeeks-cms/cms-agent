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

    /**
     * /**
     * @return $this
     */
    public function loadAgents()
    {
        $this->initConfigs();

        $schedules = $this->commands;
        foreach ($this->jobs as $code => $job) { $schedules['job:'.$code] = $job; }
        if ($schedules) {
            $siteId = \Yii::$app->skeeks->site ? \Yii::$app->skeeks->site->id : null;
            $transaction = \Yii::$app->db->beginTransaction();
            try {
            /**
             * @var CmsAgent $command
             */
            foreach ($schedules as $key => $command) {
                $native = strpos($key, 'job:') === 0;
                $name = $native ? $key : $command->command;
                $agent = CmsAgentModel::find()->where(['name' => $name, 'cms_site_id' => $siteId])->one();
                if ($agent) {
                    //Будет обновлен
                } else {
                    $agent = new CmsAgentModel();
                    $agent->name = $name;
                    $agent->cms_site_id = $siteId;
                }
                $agent->scenario = CmsAgentModel::SCENARIO_CONFIG;
                
                $agent->agent_interval = $command->interval;
                $agent->is_period = (int) $command->is_period;
                $agent->description = $command->name;
                $agent->is_system = 1;
                if ($native) {
                    $agent->job_type = $command->jobType;
                    $agent->setJobPayload($command->jobPayload);
                }
                if (!$agent->save()) {
                    throw new Exception(print_r($agent->errors, true));
                }
            }

            //Удалить лишние агенты
            //Поиск системных агентов, которые есть в базе но больше нет в файлах.

            if ($agents = CmsAgentModel::find()->where(['not in', 'name', array_keys($schedules)])->andWhere(['is_system' => 1, 'cms_site_id' => $siteId])->all()) {
                foreach ($agents as $agent)
                {
                    $agent->delete();
                }
            }
            $transaction->commit();
            } catch (\Throwable $error) {
                $transaction->rollBack();
                throw $error;
            }
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
