<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.07.2015
 */

namespace skeeks\cms\agent\models;

use skeeks\cms\components\Cms;
use skeeks\cms\query\CmsActiveQuery;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

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
 * @property integer       $is_active
 * @property integer       $is_period
 * @property integer       $is_running
 * @property integer|null $cms_site_id
 *
 * @property bool         $isRunning
 */
class CmsAgentModel extends \skeeks\cms\base\ActiveRecord
{
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
            [['last_exec_at', 'next_exec_at', 'agent_interval', 'priority'], 'integer'],
            [['name'], 'required'],
            [['description'], 'string'],
            [['name'], 'string'],
            [['is_active', 'is_period', 'is_running'], 'integer', 'max' => 1],

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
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'             => Yii::t('skeeks/agent', 'ID'),
            'last_exec_at'   => Yii::t('skeeks/agent', 'Last Execution At'),
            'next_exec_at'   => Yii::t('skeeks/agent', 'Next Execution At'),
            'name'           => Yii::t('skeeks/agent', "Agent's Function"),
            'agent_interval' => Yii::t('skeeks/agent', 'Interval (sec)'),
            'priority'       => Yii::t('skeeks/agent', 'Priority'),
            'is_active'         => Yii::t('skeeks/agent', 'Active'),
            'is_period'      => Yii::t('skeeks/agent', 'Periodic'),
            'is_running'     => Yii::t('skeeks/agent', 'Is Running'),
            'description'    => Yii::t('skeeks/agent', 'Description'),
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
        return $this->save();
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