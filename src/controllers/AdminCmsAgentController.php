<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.04.2016
 */

namespace skeeks\cms\agent\controllers;

use skeeks\cms\agent\models\CmsAgentModel;
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
        $this->name = \Yii::t('skeeks/agent', 'Agents');
        $this->modelShowAttribute = "id";
        $this->modelClassName = CmsAgentModel::className();

        $this->generateAccessActions = false;

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
                        'class' => AdminMultiModelEditAction::className(),
                        "name" => \Yii::t('skeeks/agent', 'Activate'),
                        //"icon"              => "fa fa-trash",
                        "eachCallback" => [$this, 'eachMultiActivate'],
                    ],

                "inActivate-multi" =>
                    [
                        'class' => AdminMultiModelEditAction::className(),
                        "name" => \Yii::t('skeeks/agent', 'Deactivate'),
                        //"icon"              => "fa fa-trash",
                        "eachCallback" => [$this, 'eachMultiInActivate'],
                    ]
            ]
        );
    }

    /**
     * Загрузка агентов из файла
     * @return RequestResponse
     */
    public function actionLoad()
    {
        $rr = new RequestResponse();
        if ($rr->isRequestAjaxPost()) {
            \Yii::$app->cmsAgent->loadAgents();
            $rr->message = \Yii::t('skeeks/agent', 'Agents have been updated successfully');
            $rr->success = true;
            return $rr;
        }
    }

    /**
     * Загрузка агентов из файла
     * @return RequestResponse
     */
    public function actionStopExecutable()
    {
        $rr = new RequestResponse();
        if ($rr->isRequestAjaxPost()) {
            $stoppedLong = CmsAgent::stopLongExecutable(0);
            $rr->message = \Yii::t('skeeks/agent', 'Running agents stopped');
            $rr->success = true;
            return $rr;
        }
    }
}
