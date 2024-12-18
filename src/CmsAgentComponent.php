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

        if ($this->commands) {
            /**
             * @var CmsAgent $command
             */
            foreach ($this->commands as $command) {
                $agent = CmsAgentModel::find()->where(['name' => $command->command])->one();
                if ($agent) {
                    //Будет обновлен
                } else {
                    $agent = new CmsAgentModel();
                    $agent->name = $command->command;
                }
                
                $agent->agent_interval = $command->interval;
                $agent->is_period = (int) $command->is_period;
                $agent->description = $command->name;
                $agent->is_system = 1;
                if (!$agent->save()) {
                    throw new Exception(print_r($agent->errors, true));
                }
            }

            //Удалить лишние агенты
            //Поиск системных агентов, которые есть в базе но больше нет в файлах.

            if ($agents = CmsAgentModel::find()->where(['not in', 'name', ArrayHelper::map($this->commands, "command", "command")])->andWhere(['is_system' => 1])->all()) {
                foreach ($agents as $agent)
                {
                    $agent->delete();
                }
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
                if (is_string($config)) {
                    $config = ['class' => $config];
                }
                $config['command'] = $command;
                $this->commands[$command] = \Yii::createObject($config);
            }
        }

        return $this;
    }
}