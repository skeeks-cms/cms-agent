<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 01.09.2015
 */
/* @var $this yii\web\View */
/* @var $searchModel \skeeks\cms\models\Search */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model \skeeks\cms\models\CmsContentElement */

$backend = \yii\helpers\Url::to(['load']);
$backendStop = \yii\helpers\Url::to(['stop-executable']);

$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.Updater = sx.classes.Component.extend({

        _init: function()
        {
            setInterval(function()
            {
                $.pjax.reload('#sx-agents', {});
            }, 15000);
        }
    });

    new sx.classes.Updater();
})(sx, sx.$, sx._);



JS
);

?>


<? $pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin([
    'id' => 'sx-agents'
]); ?>

<?


$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.LoadAgents = sx.classes.Component.extend({

        _onDomReady: function()
        {
            var self = this;

            $(".sx-btn-make").on('click', function()
            {
                self.make();
                return false;
            });

            $(".sx-btn-stop-executable").on('click', function()
            {
                self.stop();
                return false;
            });
        },

        make: function()
        {
            var ajax = sx.ajax.preparePostQuery(this.get("backend"));
            var rr = new sx.classes.AjaxHandlerStandartRespose(ajax);

            rr.bind('error', function(e, data)
            {
                $.pjax.reload('#sx-agents', {});
                return false;
            });

            rr.bind('success', function(e, data)
            {
                $.pjax.reload('#sx-agents', {});
                return false;
            });

            ajax.execute();
        },

        stop: function()
        {
            var ajax = sx.ajax.preparePostQuery(this.get("backendStop"));
            var rr = new sx.classes.AjaxHandlerStandartRespose(ajax);

            rr.bind('error', function(e, data)
            {
                $.pjax.reload('#sx-agents', {});
                return false;
            });

            rr.bind('success', function(e, data)
            {
                $.pjax.reload('#sx-agents', {});
                return false;
            });

            ajax.execute();
        }
    });


    new sx.classes.LoadAgents({
        'backend' : '{$backend}',
        'backendStop' : '{$backendStop}'
    });

})(sx, sx.$, sx._);
JS
);
$this->registerCss(<<<CSS
.sx-legend
{
    font-size: 16px;
    margin-left: 20px;
}
.sx-orange
{
    color: orange;
    font-weight: bold;
}
.sx-green
{
    color: green;
    font-weight: bold;
}
CSS
);
?>


<?php echo $this->render('_search', [
    'searchModel' => $searchModel,
    'dataProvider' => $dataProvider
]); ?>

<div class="row">
    <div class="col-md-12">
        <div class="pull-left">
            <?= \yii\helpers\Html::a("<i class=\"glyphicon glyphicon-stop\"></i> " . \Yii::t('skeeks/agent',
                    'Stop running'), "#", [
                'class' => 'btn btn-primary sx-btn-stop-executable',
            ]); ?>
        </div>


        <div class="pull-right">
            <?= \yii\helpers\Html::a("<i class=\"glyphicon glyphicon-retweet\"></i> " . \Yii::t('skeeks/agent',
                    'Find and download of files'), "#", [
                'class' => 'btn btn-primary sx-btn-make',
            ]); ?>
            <span class="sx-legend">
                    <?= \Yii::t('skeeks/agent', 'Files with agents'); ?> <span
                        class="sx-orange"><?= count(\Yii::$app->cmsAgent->agentsConfigFiles); ?></span>
                    | <?= \Yii::t('skeeks/agent', 'Found agents'); ?> <span
                        class="sx-green"><?= count(\Yii::$app->cmsAgent->agentsConfig); ?></span>
                </span>
        </div>
    </div>
</div>

<br/>

<?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'adminController' => $controller,
    'pjax' => $pjax,

    "columns" => [
        [
            'attribute' => 'is_running',
            'filter' => \Yii::$app->cms->booleanFormat(),
            'format' => 'raw',
            'value' => function (\skeeks\cms\agent\models\CmsAgentModel $cmsAgent) {
                if ($cmsAgent->is_running == 'Y') {
                    return \yii\helpers\Html::img(\skeeks\cms\agent\assets\CmsAgentAsset::getAssetUrl('loaders/loader.svg'),
                        [
                            'height' => '30'
                        ]);
                }

                return "-";
            },
        ],
        'name',
        'description',

        [
            'class' => \skeeks\cms\grid\DateTimeColumnData::className(),
            'attribute' => "last_exec_at"
        ],

        [
            'class' => \skeeks\cms\grid\DateTimeColumnData::className(),
            'attribute' => "next_exec_at"
        ],

        [
            'attribute' => "agent_interval"
        ],

        [
            'class' => \skeeks\cms\grid\BooleanColumn::className(),
            'attribute' => "active"
        ],
    ],
]); ?>


<? \skeeks\cms\modules\admin\widgets\Pjax::end(); ?>

<? if (\Yii::$app->cmsAgent->onHitsEnabled) : ?>
    <? \yii\bootstrap\Alert::begin([
        'options' => [
            'class' => 'alert-warning',
        ],
    ]) ?>

    <?= \Yii::t('skeeks/agent', 'Attention! You use agents mechanism hits users. If possible, switch them on cron.'); ?>
    <br/>
    <b>* * * * * cd <?= ROOT_DIR; ?> && php yii cmsAgent/execute > /dev/null 2>&1</b>

    <? \yii\bootstrap\Alert::end(); ?>
<? else: ?>
    <? \yii\bootstrap\Alert::begin([
        'options' => [
            'class' => 'alert-success',
        ],
    ]) ?>

    <?= \Yii::t('skeeks/agent',
        'In the project settings specified that you are using a mechanism on the crown agents. If the agents do not work, check the entry in the file cron'); ?>
    <br/>
    <b>* * * * * cd <?= ROOT_DIR; ?> && php yii cmsAgent/execute > /dev/null 2>&1</b>
    <? \yii\bootstrap\Alert::end(); ?>
<? endif; ?>

<!--<hr />-->

<!--<pre>
<? /*
print_r(Yii::$app->cmsAgent->agentsConfig);
*/ ?>
</pre>-->
<? /*=
    \skeeks\cms\modules\admin\widgets\GridView::widget([
        'dataProvider' => new \yii\data\ArrayDataProvider([
            'models' => \Yii::$app->cmsAgent->agentsConfigFiles
        ]),
        'columns' =>
        [

        ]
    ]);
*/ ?>
