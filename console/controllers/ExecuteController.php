<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.04.2016
 */
namespace skeeks\cms\agent\console\controllers;
use skeeks\cms\agent\models\CmsAgent;
use skeeks\cms\components\Cms;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Class ExecuteController
 * @package skeeks\cms\console\controllers
 */
class ExecuteController extends Controller
{
    /**
     * Execute agents
     */
    public function actionIndex()
    {
        $agents = CmsAgent::findForExecute()->all();

        \Yii::info('Agents execute: ' . count($agents), 'skeeks/agent::total');
        $this->stdout('Agents execute: ' . count($agents) . "\n", Console::BOLD);

        if ($agents)
        {
            foreach ($agents as $agent)
            {
                $this->_executeAgent($agent);
            }
        }
    }


    /**
     * Выполнить агента
     *
     * @return $this
     */
    protected function _executeAgent(CmsAgent $cmsAgent)
    {
        function microtime_float()
        {
            list($usec, $sec) = explode(" ", microtime());
            return ((float)$usec + (float)$sec);
        }

        //Если уже запщен, то не будем запускать еще раз.
        if ($cmsAgent->is_running == Cms::BOOL_Y)
        {
            $this->stdout('Agent is already running: ' . $cmsAgent->name, Console::BOLD);
            return $this;
        }

        //Перед выполнением отмечаем что он сейчас выполняется.
        $cmsAgent->is_running = Cms::BOOL_Y;
        $cmsAgent->save();

        $time_start = microtime_float();

        $this->stdout("------------------------------\n");
        $this->stdout(" > {$cmsAgent->name}\n");
        $result = \Yii::$app->console->execute("cd " . ROOT_DIR . "; php yii " . $cmsAgent->name);
        $this->stdout( $result . "\n");

        $time_end = microtime_float();
        $time = $time_end - $time_start;

        $this->stdout("Lead time > {$time} sec\n");
        $this->stdout("------------------------------\n");

        \Yii::info("Execute agent > {$cmsAgent->name}\n{$result}\nLead time > {$time} sec", 'skeeks/agent::' . $cmsAgent->name);

        $cmsAgent->is_running   = Cms::BOOL_N;
        $cmsAgent->next_exec_at = \Yii::$app->formatter->asTimestamp(time()) + (int) $cmsAgent->agent_interval;
        $cmsAgent->last_exec_at = \Yii::$app->formatter->asTimestamp(time());
        $cmsAgent->save();

        return $this;
    }
}