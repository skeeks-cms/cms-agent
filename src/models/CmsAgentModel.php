<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.07.2015
 */

namespace skeeks\cms\agent\models;

use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%cms_agent}}".
 *
 * @property integer      $id
 * @property integer      $last_exec_at
 * @property integer      $next_exec_at
 * @property string       $name
 * @property string       $description
 * @property integer      $agent_interval
 * @property integer      $priority
 * @property integer      $is_active
 * @property integer      $is_period
 * @property integer      $is_running
 * @property integer      $is_system
 * @property integer|null $cms_site_id
 * @property string|null  $job_type
 * @property string|null  $job_payload
 *
 * @property bool         $isRunning
 * @property array        $jobPayload
 * @property bool         $isJobBased
 */
class CmsAgentModel extends \skeeks\cms\base\ActiveRecord
{
    const SCENARIO_CONFIG = 'config';
    private $_executionMode;

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CONFIG] = $scenarios[self::SCENARIO_DEFAULT];
        return $scenarios;
    }

    public function getExecutionMode()
    {
        return $this->_executionMode ?? ($this->getEffectiveJobType() || strpos((string)$this->name, 'job:') === 0 ? 'job' : 'console');
    }

    public function setExecutionMode($value) { $this->_executionMode = $value; }

    public function validateExecutionMode($attribute)
    {
        if ($this->_executionMode === 'console') {
            // A package-owned bridge must not silently change its execution path.
            if ($this->is_system && $this->getEffectiveJobType()) {
                $this->addError($attribute, 'Способ запуска системного расписания задаётся в конфигурации пакета.');
                return;
            }
            $this->job_type = null;
            $this->job_payload = null;
        }
        if ($this->scenario !== self::SCENARIO_CONFIG && !$this->isNewRecord && $this->getOldAttribute('is_system') && Yii::$app instanceof \yii\web\Application) {
            foreach (['name', 'description', 'job_type', 'job_payload', 'agent_interval', 'is_system'] as $field) {
                if ((string)$this->$field !== (string)$this->getOldAttribute($field)) {
                    $this->addError($field, 'Системное расписание изменяется в конфигурации пакета.');
                }
            }
        }
    }

    public function getDisplayName()
    {
        return $this->description ?: $this->name;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_agent}}';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['executionMode', 'in', 'range' => ['console', 'job'], 'skipOnEmpty' => false],
            ['executionMode', 'validateExecutionMode', 'skipOnEmpty' => false],
            [['last_exec_at', 'next_exec_at', 'agent_interval', 'priority', 'is_system'], 'integer'],
            [['name'], 'required'],
            [['description'], 'string'],
            [['name'], 'string'],

            [['is_active', 'is_period', 'is_running'], 'integer'
            //    , 'max' => 1
            ],

            [['is_system'], 'default', 'value' => 0],
            [['is_active'], 'default', 'value' => 1],
            [['is_period'], 'default', 'value' => 0],

            [['is_running'], 'default', 'value' => 0],
            [['agent_interval'], 'default', 'value' => 86400],
            [['priority'], 'default', 'value' => 100],
            [
                ['next_exec_at'],
                'default',
                'value' => function (self $model) {
                    return \Yii::$app->formatter->asTimestamp(time());
                },
            ],
            [
                ['last_exec_at'],
                'default',
                'value' => function (self $model) {
                    return \Yii::$app->formatter->asTimestamp(time());
                },
            ],

            [['cms_site_id',], 'integer'],

            [['job_type'], 'string', 'max' => 128],
            [['job_payload'], 'string'],
            [['job_type', 'job_payload'], 'default', 'value' => null],
            ['job_type', 'validateJobConfiguration', 'skipOnEmpty' => false],
            ['job_payload', 'validateJobPayload', 'skipOnEmpty' => false],

            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],
        ];
    }

    /**
     * Создаёт ли этот агент фоновое задание вместо запуска команды.
     *
     * @return bool
     */
    public function getIsJobBased()
    {
        // Never fall back to executing a job schedule's name as a shell command.
        return $this->_executionMode === 'job' || (bool)$this->getEffectiveJobType() || strpos((string)$this->name, 'job:') === 0;
    }

    public function validateJobConfiguration($attribute): void
    {
        if (!$this->isJobBased) { return; }
        if ($this->isDisablingUnchangedJob()) { return; }
        if (!\Yii::$app->has('jobs')) {
            $this->addError($attribute, 'Для этого расписания требуется пакет cms-job.'); return;
        }
        $registry = \Yii::$app->jobs->getRegistry();
        if (!$registry->has($this->effectiveJobType)) {
            $this->addError($attribute, 'Выберите зарегистрированный тип задания.'); return;
        }
        $permission = $registry->get($this->effectiveJobType)->permission;
        if ($this->scenario !== self::SCENARIO_CONFIG && \Yii::$app instanceof \yii\web\Application && $permission && !\Yii::$app->user->can($permission)) {
            $this->addError($attribute, 'Нет права запуска выбранного типа задания.');
        }
    }

    public function validateJobPayload($attribute): void
    {
        if (!$this->isJobBased || $this->isDisablingUnchangedJob()) { return; }
        try { $this->getJobPayload(); }
        catch (\InvalidArgumentException $error) { $this->addError($attribute, $error->getMessage()); }
    }

    private function isDisablingUnchangedJob(): bool
    {
        if ($this->isNewRecord || (int)$this->is_active !== 0) { return false; }
        foreach (['name', 'job_type', 'job_payload'] as $field) {
            if ((string)$this->$field !== (string)$this->getOldAttribute($field)) { return false; }
        }
        return true;
    }

    public function getEffectiveJobType()
    {
        if ($this->job_type) { return $this->job_type; }
        $component = \Yii::$app->get('cmsAgent', false);
        $command = $component ? ($component->commands[$this->name] ?? null) : null;
        return is_array($command) ? ($command['jobType'] ?? null)
            : ($command instanceof \skeeks\cms\agent\CmsAgent ? $command->jobType : null);
    }

    public function getEffectiveJobPayload(): array
    {
        if ($this->job_type) { return $this->jobPayload; }
        $component = \Yii::$app->get('cmsAgent', false);
        $command = $component ? ($component->commands[$this->name] ?? null) : null;
        return is_array($command) ? (array)($command['jobPayload'] ?? [])
            : ($command instanceof \skeeks\cms\agent\CmsAgent ? $command->jobPayload : []);
    }

    public function getJobDedupKey()
    {
        $definition = \Yii::$app->jobs->getRegistry()->get($this->effectiveJobType);
        $run = new \skeeks\cms\job\models\CmsJobRun([
            'job_type' => $this->effectiveJobType, 'cms_site_id' => $this->cms_site_id,
        ]);
        $run->setPayload($this->effectiveJobPayload);
        if (is_callable($definition->dedupKey)) {
            $key = call_user_func($definition->dedupKey, $this->effectiveJobPayload, $run);
            if ($key) { return $key; }
        }
        return 'cms_agent:'.$this->id;
    }

    public function getActiveJob()
    {
        return \skeeks\cms\job\models\CmsJobRun::find()
            ->andWhere(['dedup_active' => $this->jobDedupKey])->one();
    }

    /**
     * @return array
     */
    public function getJobPayload()
    {
        if ($this->job_payload === null || $this->job_payload === '') {
            return [];
        }

        try {
            $data = \yii\helpers\Json::decode((string)$this->job_payload);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Параметры задания должны содержать корректный JSON-объект.');
        }

        if (!is_array($data) || substr(ltrim((string)$this->job_payload), 0, 1) !== '{') {
            throw new \InvalidArgumentException('Параметры задания должны быть JSON-объектом, например {"site_id": 123}.');
        }
        return $data;
    }

    /**
     * @return $this
     */
    public function setJobPayload(array $value)
    {
        $this->job_payload = $value ? \yii\helpers\Json::encode((object)$value) : null;

        return $this;
    }

    /**
     * Поставить задание по расписанию.
     *
     * Расписание не выполняет тяжёлый код само: оно создаёт такое же задание,
     * какое создаёт кнопка в интерфейсе.
     *
     * Политика `skip` с ключом по агенту решает давнюю проблему пересечений:
     * прежний флаг `is_running` защищал строку только от самой себя, поэтому
     * полное и инкрементальное обновление одного поставщика — разные строки —
     * сталкивались на одних данных.
     *
     * @return \skeeks\cms\job\models\CmsJobRun|null null, если задание уже
     *                                               выполняется
     */
    public function pushJob($manual = false)
    {
        if (!\Yii::$app->has('jobs') || !$this->effectiveJobType) {
            throw new \yii\base\InvalidConfigException('Для расписания не настроен cms-job или тип задания.');
        }
        return \Yii::$app->jobs->push($this->effectiveJobType, $this->effectiveJobPayload, [
            'title' => $this->description ? $this->description : $this->name,
            'siteId' => $this->cms_site_id,
            'triggerType' => $manual ? 'manual' : 'schedule',
            'triggerRef' => 'cms_agent:'.$this->id,
            'dedupKey' => $this->jobDedupKey,
            'overlapPolicy' => 'skip',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'             => Yii::t('skeeks/agent', 'ID'),
            'last_exec_at'   => Yii::t('skeeks/agent', 'Last Execution At'),
            'next_exec_at'   => Yii::t('skeeks/agent', 'Next Execution At'),
            'name'           => 'Название расписания / консольная команда',
            'executionMode'  => 'Способ запуска',
            'job_type'       => 'Тип фонового задания',
            'job_payload'    => 'Параметры задания (JSON)',
            'agent_interval' => "Интервал",
            'priority'       => "Сортировка",
            'is_active'      => Yii::t('skeeks/agent', 'Active'),
            'is_period'      => Yii::t('skeeks/agent', 'Periodic'),
            'is_running'     => Yii::t('skeeks/agent', 'Is Running'),
            'description'    => Yii::t('skeeks/agent', 'Description'),
            'is_system'    => "Системный?",
        ];
    }


    /**
     * @return bool
     */
    public function stop()
    {
        $this->is_running = 0;
        $this->next_exec_at = \Yii::$app->formatter->asTimestamp(time()) + (int)$this->agent_interval;
        $this->last_exec_at = \Yii::$app->formatter->asTimestamp(time());
        return $this->save(false, ['is_running', 'next_exec_at', 'last_exec_at']);
    }

    /**
     * Stop long executable agents
     *
     * @return int
     */
    static public function stopLongExecutable($agentMaxExecuteTime = null)
    {
        if ($agentMaxExecuteTime === null) {
            $agentMaxExecuteTime = \Yii::$app->cmsAgent->agentMaxExecuteTime;
        }

        $time = \Yii::$app->formatter->asTimestamp(time()) - (int)$agentMaxExecuteTime;

        $running = static::find()
            ->where([
                'is_running' => 1,
            ])
            ->orderBy('priority')
            ->all();;

        $stoping = 0;

        if ($running) {
            /**
             * @var $agent CmsAgent
             */
            foreach ($running as $agent) {
                if ($agent->next_exec_at <= $time) {
                    if ($agent->stop()) {
                        $stoping++;
                    } else {
                        \Yii::error('Not stopped long agent: '.$agent->name, 'skeeks/agent');
                    }
                }
            }
        }

        return $stoping;
    }

    /**
     * Агенты к выполнению
     *
     * @return ActiveQuery
     */
    static public function findForExecute()
    {
        return static::find()->active()
            ->andWhere([
                'is_running' => 0,
            ])
            ->andWhere([
                '<=',
                'next_exec_at',
                \Yii::$app->formatter->asTimestamp(time()),
            ])->orderBy('priority');
    }
}
