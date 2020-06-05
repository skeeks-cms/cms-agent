<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\agent\controllers;

use kartik\datecontrol\DateControl;
use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\agent\models\CmsAgentModel;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\events\ViewRenderEvent;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsAgentController extends BackendModelStandartController
{
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
        return ArrayHelper::merge(parent::actions(), [

            "index" => [
                'on beforeRender' => function (ViewRenderEvent $event) {

                    $event->content = $this->renderPartial("_before-index");
                },
                'on afterRender'  => function (ViewRenderEvent $event) {
                    $event->content = $this->renderPartial("_after-index");
                },
                "filters"         => [
                    'visibleFilters' => [
                        'q',
                    ],

                    'filtersModel' => [
                        'rules' => [
                            ['q', 'safe'],
                        ],

                        'attributeDefines' => [
                            'q',
                        ],


                        'fields' => [
                            'q' => [
                                'label'          => 'Поиск',
                                'elementOptions' => [
                                    'placeholder' => 'Поиск',
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        $query->andWhere([
                                            'or',
                                            ['like', CmsAgentModel::tableName().'.name', $e->field->value],
                                            ['like', CmsAgentModel::tableName().'.description', $e->field->value],
                                        ]);

                                        $query->groupBy([CmsAgentModel::tableName().'.id']);
                                    }
                                },
                            ],
                        ],
                    ],
                ],

                "grid" => [
                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;

                        $query->andWhere(['cms_site_id' => \Yii::$app->skeeks->site->id]);
                    },

                    'defaultPageSize' => 50,
                    'visibleColumns'  => [
                        'checkbox',
                        'actions',

                        'custom',

                        'last_exec_at',
                        'next_exec_at',

                        'agent_interval',
                        'is_active',
                    ],

                    'columns' => [
                        'is_active'       => [
                            'class' => BooleanColumn::class,
                        ],
                        'last_exec_at' => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'next_exec_at' => [
                            'class' => DateTimeColumnData::class,
                        ],

                        'custom' => [
                            'format' => 'raw',
                            'value'  => function (CmsAgentModel $cmsAgentModel) {
                                $result = [];
                                $result[] = Html::a($cmsAgentModel->name, "#", [
                                    'class' => 'sx-trigger-action',
                                ]);

                                $result[] = $cmsAgentModel->description;

                                if ($cmsAgentModel->is_running) {
                                    $result[] = \yii\helpers\Html::img(\skeeks\cms\agent\assets\CmsAgentAsset::getAssetUrl('loaders/loader.svg'), [
                                        'height' => '30',
                                    ]);
                                }

                                return implode("<br>", $result);
                            },
                        ],
                    ],
                ],
            ],

            "create" => [
                'fields' => [$this, 'updateFields'],
            ],
            "update" => [
                'fields' => [$this, 'updateFields'],
            ],

            "activate-multi" => [
                'class' => BackendModelMultiActivateAction::class,
            ],

            "inActivate-multi" => [
                'class' => BackendModelMultiDeactivateAction::class,
            ],
        ]);
    }

    public function updateFields()
    {
        return [
            'next_exec_at' => [
                'class'        => WidgetField::class,
                'widgetClass'  => DateControl::class,
                'widgetConfig' => [
                    'type' => DateControl::FORMAT_DATETIME,
                ],
            ],
            'is_active'       => [
                'class'      => BoolField::class,
                'allowNull'  => false,
            ],
            'name',

            'description' => [
                'class' => TextareaField::class,
            ],
            'priority',

            'is_period' => [
                'class'      => BoolField::class,
                'trueValue'  => 'Y',
                'falseValue' => 'N',
                'allowNull'  => false,
            ],

            'agent_interval',
        ];
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
            $stoppedLong = CmsAgentModel::stopLongExecutable(0);
            $rr->message = \Yii::t('skeeks/agent', 'Running agents stopped');
            $rr->success = true;
            return $rr;
        }
    }
}
