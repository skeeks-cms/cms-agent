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
 * @property integer $id
 * @property integer $last_exec_at
 * @property integer $next_exec_at
 * @property string $name
 * @property string $description
 * @property integer $agent_interval
 * @property integer $priority
 * @property string $active
 * @property string $is_period
 * @property string $is_running
 */
class CmsAgent extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_agent}}';
    }

    /**
     * @return CmsActiveQuery
     */
    public static function find()
    {
        return new CmsActiveQuery(get_called_class());
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
            [['active', 'is_period', 'is_running'], 'string', 'max' => 1],
            [['active', 'is_period', 'is_running'], 'in', 'range' => array_keys(Yii::$app->cms->booleanFormat())],

            [['active'], 'default', 'value' => 'Y'],
            [['is_period'], 'default', 'value' => 'N'],
            [['is_running'], 'default', 'value' => 'N'],
            [['agent_interval'], 'default', 'value' => 86400],
            [['priority'], 'default', 'value' => 100],
            [['next_exec_at'], 'default', 'value' => function(self $model)
            {
                return \Yii::$app->formatter->asTimestamp(time()) + (int) $model->agent_interval;
            }],
            [['last_exec_at'], 'default', 'value' => function(self $model)
            {
                return \Yii::$app->formatter->asTimestamp(time());
            }],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('skeeks/agent', 'ID'),
            'last_exec_at' => Yii::t('skeeks/agent', 'Last Execution At'),
            'next_exec_at' => Yii::t('skeeks/agent', 'Next Execution At'),
            'name' => Yii::t('skeeks/agent', "Agent's Function"),
            'agent_interval' => Yii::t('skeeks/agent', 'Interval (sec)'),
            'priority' => Yii::t('skeeks/agent', 'Priority'),
            'active' => Yii::t('skeeks/agent', 'Active'),
            'is_period' => Yii::t('skeeks/agent', 'Periodic'),
            'is_running' => Yii::t('skeeks/agent', 'Is Running'),
            'description' => Yii::t('skeeks/agent', 'Description'),
        ];
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
                'is_running' => Cms::BOOL_N
            ])
            ->andWhere([
                '<=', 'next_exec_at', \Yii::$app->formatter->asTimestamp(time())
            ])->orderBy('priority')
        ;
    }

}