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
 * Init agents from config files
 *
 * Class InitController
 * @package skeeks\cms\agent\console\controllers
 */
class InitController extends Controller
{
    /**
     * Init agents from config files
     */
    public function actionIndex()
    {
        $this->stdout('Agents files: ' . count(\Yii::$app->cmsAgent->agentsConfigFiles) . "\n");
        $this->stdout('Agents in files: ' . count(\Yii::$app->cmsAgent->agentsConfig) . "\n");

        \Yii::$app->cmsAgent->loadAgents();
    }


}