<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.04.2016
 */
namespace skeeks\cms\agent\console\controllers;
use skeeks\cms\agent\models\CmsAgent;
use yii\console\Controller;

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
        /**
         * Поиск агентов к выполнению
         */
        $agents = CmsAgent::findForExecute()->all();

        \Yii::info('Agents execute: ' . count($agents), CmsAgent::className());

        if ($agents)
        {
            foreach ($agents as $agent)
            {
                $agent->execute();
            }
        }
    }


}