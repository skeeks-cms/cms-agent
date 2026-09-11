<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */

\skeeks\cms\agent\assets\CmsAgentAsset::register($this);
$health = $this->context->scheduleHealth();
$changes = \Yii::$app->cmsAgent->getScheduleChanges();
$createCount = count($changes['create']);
$updateCount = count($changes['update']);
$deleteCount = count($changes['delete']);
$changeCount = $createCount + $updateCount + $deleteCount;
$loadLabel = ($updateCount || $deleteCount ? 'Обновить расписания' : 'Загрузить расписания').' · '.$changeCount;
$changeSummary = 'Новых: '.$createCount.', изменённых: '.$updateCount.', устаревших к удалению: '.$deleteCount.'.';
$this->registerCss('.sx-agent-config-summary { margin-bottom: 20px; }');

$backend = \yii\helpers\Url::to(['load']);

/*print_r(\Yii::$app->cmsAgent->commands);die;*/
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


        },

        make: function()
        {
            var ajax = sx.ajax.preparePostQuery(this.get("backend"));
            var rr = new sx.classes.AjaxHandlerStandartRespose(ajax, {
                'blockerSelector' : "body",
                'enableBlocker': true,
                'allowResponseSuccessMessage': true,
                'allowResponseErrorMessage': true,
                'ajaxExecuteErrorAllowMessage': true,
            });

            rr.bind('error', function(e, data)
            {
                //$.pjax.reload('#sx-agents', {});
                return false;
            });

            rr.bind('success', function(e, data)
            {
                //$.pjax.reload('#sx-agents', {});
                _.delay(function() {
                    window.location.reload();
                }, 1000);
                return false;
            });

            ajax.execute();
        }
    });


    new sx.classes.LoadAgents({
        'backend' : '{$backend}'
    });

})(sx, sx.$, sx._);
JS
);
?>





<?php if ($health['state'] !== 'success'): ?>
<div class="alert alert-<?= $health['state'] ?>" role="status">
    <strong><?= \yii\helpers\Html::encode($health['title']) ?></strong>
    <div><?= \yii\helpers\Html::encode($health['description']) ?></div>
</div>
<?php endif; ?>

<?= \skeeks\cms\backend\widgets\BackendSurfaceWidget::widget([
    'responsive' => true,
    'options' => ['class' => 'sx-agent-config-summary'],
    'title' => $changeCount ? $changeSummary : '',
    'hint' => 'Расписание запуска процессов, команд, скриптов и фоновых заданий. Здесь можно настроить периодичность и проверить состояние запусков.',
    'actions' => $changeCount ? \yii\helpers\Html::button($loadLabel, [
        'class' => 'sx-button sx-button--secondary sx-btn-make',
        'title' => $changeSummary,
    ]) : '',
]); ?>
