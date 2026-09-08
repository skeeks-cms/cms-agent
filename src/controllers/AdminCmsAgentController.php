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
use skeeks\cms\backend\actions\BackendModelViewAction;
use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\grid\BackendEntityLinkColumn;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\events\ViewRenderEvent;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\formInputs\SmartDurationInputWidget;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\TextField;
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
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsAgentModel::class;

        $this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            'view' => [
                'class' => BackendModelViewAction::class,
                'name' => 'Расписание',
                'priority' => 1,
                'attributes' => [$this, 'scheduleAttributes'],
            ],

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
                        'is_system',
                    ],

                    'filtersModel' => [
                        'rules' => [
                            ['q', 'safe'],
                        ],

                        'attributeDefines' => [
                            'q',
                        ],


                        'fields' => [
                            'is_system' => [
                                'class' => BoolField::class,
                                'formElement' => BoolField::ELEMENT_LISTBOX,
                                'elementOptions' => [
                                    'size' => 1
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value == '1') {
                                        $query->andWhere(
                                            [CmsAgentModel::tableName().'.is_system' => 1],
                                        );
                                    }
                                    if ($e->field->value == '0') {
                                        $query->andWhere(
                                            [CmsAgentModel::tableName().'.is_system' => 0],
                                        );
                                    }
                                },
                                //'allowNull' => false
                            ],
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
                        'job',

                        'last_exec_at',
                        'next_exec_at',

                        'agent_interval',
                        'is_active',
                        'is_system',
                    ],

                    'columns' => [
                        'agent_interval' => [
                            'attribute' => 'agent_interval',
                            'format' => 'raw',
                            'value' => function (CmsAgentModel $model) {
                                return $this->renderInterval($model);
                            },
                        ],
                        'job' => [
                            'label' => 'Фоновое задание', 'format' => 'raw',
                            'value' => function (CmsAgentModel $model) {
                                if (!$model->isJobBased) { return 'Прямой запуск по расписанию'; }
                                return \skeeks\cms\job\widgets\JobButton::widget([
                                    'startUrl' => ['start-job', 'id' => $model->id],
                                    'statusUrl' => ['job-status', 'id' => $model->id],
                                ]);
                            },
                        ],
                        'is_active'    => [
                            'class' => BooleanColumn::class,
                        ],
                        'is_system'    => [
                            'class' => BooleanColumn::class,
                        ],
                        'last_exec_at' => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'next_exec_at' => [
                            'class' => DateTimeColumnData::class,
                        ],

                        'custom' => [
                            'class' => BackendEntityLinkColumn::class,
                            'controllerId' => '/cmsAgent/admin-cms-agent',
                            'action' => 'view',
                            'attribute' => 'name',
                            'label' => 'Расписание',
                            'content' => function (CmsAgentModel $cmsAgentModel) {
                                $result = [];
                                $result[] = Html::tag('span', Html::encode($cmsAgentModel->name), [
                                    'class' => 'sx-collection-cell__primary',
                                ]);

                                $result[] = Html::tag('span', Html::encode($cmsAgentModel->description), [
                                    'class' => 'sx-collection-cell__secondary',
                                ]);

                                if ($cmsAgentModel->is_running) {
                                    $result[] = \yii\helpers\Html::img(\skeeks\cms\agent\assets\CmsAgentAsset::getAssetUrl('loaders/loader.svg'), [
                                        'height' => '30',
                                    ]);
                                }

                                return implode('', $result);
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

        // The scheduler remains usable without cms-job. History is bound to
        // the schedule reference, not the shared job type or resource key.
        if (\Yii::$app->has('jobs') && class_exists(\skeeks\cms\job\models\CmsJobRun::class)) {
            $actions['jobs'] = [
                'class' => BackendGridModelRelatedAction::class,
                'name' => 'Фоновые задания',
                'priority' => 30,
                'controllerRoute' => '/cmsJob/admin-cms-job-run',
                'relation' => ['cms_site_id' => 'cms_site_id'],
                'on gridInit' => function (Event $event) {
                    $action = $event->sender;
                    $index = $action->relatedIndexAction;
                    $baseInit = $index->grid['on init'] ?? null;
                    $triggerRef = 'cms_agent:'.$action->model->id;
                    $index->grid['on init'] = function (Event $event) use ($baseInit, $triggerRef) {
                        if ($baseInit) { $baseInit($event); }
                        $event->sender->dataProvider->query->andWhere(['trigger_ref' => $triggerRef]);
                    };
                    $index->pageHeader = false;
                    $index->navigationActionIds = false;
                    $index->emptyState = [
                        'title' => 'Связанных фоновых заданий пока нет',
                        'description' => 'Здесь появятся задания, поставленные этим расписанием автоматически или вручную. Прямые консольные запуски в эту историю не записываются.',
                        'action' => false,
                    ];
                },
            ];
        }

        return $actions;
    }

    public function renderInterval(CmsAgentModel $model): string
    {
        $seconds = (int)$model->agent_interval;
        return Html::tag('span', Html::encode(\Yii::$app->formatter->asDuration($seconds)), [
            'title' => $seconds.' секунд',
        ]);
    }

    public function scheduleAttributes(BackendModelViewAction $action): array
    {
        $model = $action->model;
        return [
            [
                'label' => 'Ручной запуск',
                'format' => 'raw',
                'visible' => $model->isJobBased,
                'value' => function (CmsAgentModel $agent) {
                    return \skeeks\cms\job\widgets\JobButton::widget([
                        'startUrl' => ['start-job', 'id' => $agent->id],
                        'statusUrl' => ['job-status', 'id' => $agent->id],
                    ]);
                },
            ],
            ['attribute' => 'name', 'label' => 'Консольная команда'],
            'description:ntext',
            ['attribute' => 'agent_interval', 'format' => 'raw', 'value' => $this->renderInterval($model)],
            'is_active:boolean',
            'is_system:boolean',
            ['label' => 'Способ запуска', 'value' => $model->isJobBased ? 'Через очередь' : 'Прямой запуск консольной команды'],
            ['label' => 'Тип фонового задания', 'value' => $model->effectiveJobType ?: '—'],
            ['attribute' => 'last_exec_at', 'format' => 'datetime', 'value' => $model->last_exec_at ?: null],
            ['attribute' => 'next_exec_at', 'format' => 'datetime', 'value' => $model->next_exec_at ?: null],
            ['label' => 'Прямой процесс запущен', 'value' => $model->isJobBased ? 'Не используется при работе через очередь' : ($model->is_running ? 'Да' : 'Нет')],
            ['label' => 'Учёт запусков', 'value' => $model->isJobBased
                ? 'Даты расписания не означают завершение работы. Статус и результат смотрите во вкладке «Фоновые задания».'
                : 'Результаты прямых консольных запусков не сохраняются в истории фоновых заданий.'],
        ];
    }

    public function updateFields($action)
    {
        /**
         * @var $model CmsAgentModel
         */
        $model = $action->model;

        $options = [];
        if ($model->is_system) {
            $options['disabled'] = "disabled";
        }

        return [
            'next_exec_at' => [
                'class'        => WidgetField::class,
                'widgetClass'  => DateControl::class,
                'widgetConfig' => [
                    'type' => DateControl::FORMAT_DATETIME,
                ],
            ],
            'is_active'    => [
                'class'     => BoolField::class,
                'allowNull' => false,
            ],

            'name' => [
                'class' => TextField::class,
                'elementOptions' => $options
            ],

            'description' => [
                'class' => TextareaField::class,
                'elementOptions' => $options
            ],

            /*'is_period' => [
                'class'     => BoolField::class,
                'allowNull' => false,
            ],*/

            'agent_interval' => [
                'class'  => WidgetField::class,
                'widgetClass' => SmartDurationInputWidget::class,
                'widgetConfig' => [
                    'wrapperOptions' => $options
                ],
                
            ],
            /*'priority' => [
                'class' => NumberField::class,
            ],*/
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

    /** Resolve on the server; a URL id must never cross the active site boundary. */
    protected function resolveJobAgent($id)
    {
        if (\Yii::$app->user->isGuest || !\Yii::$app->user->can($this->permissionName)) {
            throw new \yii\web\ForbiddenHttpException();
        }
        $site = \Yii::$app->skeeks->site;
        if (!$site || !\Yii::$app->has('jobs')) { throw new \yii\web\NotFoundHttpException(); }
        $agent = CmsAgentModel::find()->andWhere(['id' => (int)$id, 'cms_site_id' => $site->id])->one();
        if (!$agent || !$agent->isJobBased) { throw new \yii\web\NotFoundHttpException(); }
        $registry = \Yii::$app->jobs->getRegistry();
        if (!$registry->has($agent->effectiveJobType)) { throw new \yii\web\NotFoundHttpException(); }
        $permission = $registry->get($agent->effectiveJobType)->permission;
        if ($permission && !\Yii::$app->user->can($permission)) { throw new \yii\web\ForbiddenHttpException(); }
        return $agent;
    }

    public function actionStartJob($id)
    {
        if (!\Yii::$app->request->isPost) { throw new \yii\web\MethodNotAllowedHttpException(); }
        $agent = $this->resolveJobAgent($id);
        // No rescheduling: the existing run is reused, not expedited or replaced.
        $run = $agent->activeJob;
        if (!$run) { $run = $agent->pushJob(true); }
        if (!$run) { $run = $agent->activeJob; } // Concurrent push won the unique-key race.
        if (!$run) { throw new \yii\web\ConflictHttpException('Повторите проверку статуса.'); }
        return $this->jobResponse($run, $agent);
    }

    public function actionJobStatus($id)
    {
        $agent = $this->resolveJobAgent($id);
        $run = $agent->activeJob;
        if (!$run) {
            $run = \skeeks\cms\job\models\CmsJobRun::find()->andWhere([
                'dedup_key' => $agent->jobDedupKey, 'cms_site_id' => $agent->cms_site_id,
                'job_type' => $agent->effectiveJobType,
            ])->orderBy(['id' => SORT_DESC])->one();
        }
        return $this->jobResponse($run, $agent);
    }

    protected function jobResponse($run, CmsAgentModel $agent)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!$run) { return ['success' => true, 'run' => null]; }
        if ((int)$run->cms_site_id !== (int)$agent->cms_site_id) {
            // Shared output resource can be occupied by another site; do not disclose its run.
            return ['success' => true, 'run' => [
                'label' => 'Общий ресурс занят', 'finished' => false, 'percent' => null, 'message' => '', 'url' => null,
            ]];
        }
        return ['success' => true, 'run' => [
            'id' => (int)$run->id, 'status' => $run->status, 'label' => $run->statusText,
            'finished' => $run->isFinished, 'percent' => $run->progressPercent,
            'message' => $run->progress_message,
            'url' => \yii\helpers\Url::to(['/cmsJob/admin-cms-job-run/view', 'pk' => $run->id]),
            'windowUrl' => \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                '/cmsJob/admin-cms-job-run/view', 'pk' => $run->id,
            ])->enableEmptyLayout()->enableNoActions()->url,
        ]];
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
