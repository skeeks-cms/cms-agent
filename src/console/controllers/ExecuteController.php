<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.04.2016
 */

namespace skeeks\cms\agent\console\controllers;

use skeeks\cms\agent\models\CmsAgentModel;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\StringHelper;
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
        $stoppedLong = CmsAgentModel::stopLongExecutable();
        if ($stoppedLong > 0) {
            \Yii::warning('Agents stopped: ' . count($stoppedLong), 'skeeks/agent');
        }

        $agents = CmsAgentModel::findForExecute()->all();

        \Yii::info('Agents execute: ' . count($agents), 'skeeks/agent::total');
        $this->stdout('Agents execute: ' . count($agents) . "\n", Console::BOLD);

        if ($agents) {
            foreach ($agents as $agent) {
                $this->_executeAgent($agent);
            }
        }
    }


    /**
     * Выполнить агента
     *
     * @return $this
     */
    protected function _executeAgent(CmsAgentModel $cmsAgent)
    {


        //Если уже запщен, то не будем запускать еще раз.
        if ($cmsAgent->is_running == Cms::BOOL_Y) {
            $this->stdout('Agent is already running: ' . $cmsAgent->name, Console::BOLD);
            return $this;
        }

        //Перед выполнением отмечаем что он сейчас выполняется.
        $cmsAgent->is_running = Cms::BOOL_Y;
        $cmsAgent->save();

        $timeStart = $this->_microtimeFloat();

        $this->stdout("------------------------------\n");
        $this->stdout(" > {$cmsAgent->name}\n");
        $result = \Yii::$app->console->execute("cd " . ROOT_DIR . "; php yii " . $cmsAgent->name);
        $this->stdout($result . "\n");

        $time = $this->_microtimeFloat() - $timeStart;

        $this->stdout("Lead time > {$time} sec\n");
        $this->stdout("------------------------------\n");

        $result = $this->_getShortResultContent($result);

        \Yii::info("Execute agent > {$cmsAgent->name}\n{$result}\nLead time > {$time} sec",
            'skeeks/agent::' . $cmsAgent->name);

        $cmsAgent->stop();

        return $this;
    }

    /**
     * @param string $result
     * @return string
     */
    protected function _getShortResultContent($result = '')
    {
        if (StringHelper::strlen($result) > 10000) {
            $totalLenght = StringHelper::strlen($result);
            $newResult = '';
            $newResult .= StringHelper::substr($result, 0, 5000);
            $newResult .= "\n\n..............\n\n........ Total lenght: {$totalLenght}  ........\n\n..............\n\n";
            $newResult .= StringHelper::substr($result, ($totalLenght - 3000), $totalLenght);

            return $newResult;
        } else {
            return $result;
        }
    }

    protected function _microtimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}