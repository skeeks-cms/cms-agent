<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.04.2016
 */

namespace skeeks\cms\agent\console\controllers;

use skeeks\cms\agent\models\CmsAgentModel;
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
        $this->stdout('Agent commmands: ' . count(\Yii::$app->cmsAgent->commands) . "\n");

        \Yii::$app->cmsAgent->loadAgents();
    }


}