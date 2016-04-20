<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.04.2016
 */
namespace skeeks\cms\agent\controllers;

use skeeks\cms\agent\models\CmsAgent;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsAgentController
 * @package skeeks\cms\controllers
 */
class AdminCmsAgentController extends AdminModelEditorController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name                     = \Yii::t('skeeks/agent', 'Agents');
        $this->modelShowAttribute       = "id";
        $this->modelClassName           = CmsAgent::className();

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                "activate-multi" =>
                [
                    'class'             => AdminMultiModelEditAction::className(),
                    "name"              => "Активировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback"      => [$this, 'eachMultiActivate'],
                ],

                "inActivate-multi" =>
                [
                    'class'             => AdminMultiModelEditAction::className(),
                    "name"              => "Деактивировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback"      => [$this, 'eachMultiInActivate'],
                ]
            ]
        );
    }

    public function actionLoad()
    {
        $rr = new RequestResponse();
        if ($rr->isRequestAjaxPost())
        {
            \Yii::$app->cmsAgent->loadAgents();
            $rr->message = \Yii::t('skeks/agent', 'Agents have been updated successfully');
            $rr->success = true;
            return $rr;
        }
    }
}
