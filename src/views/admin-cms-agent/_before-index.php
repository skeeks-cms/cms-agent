<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */

$backend = \yii\helpers\Url::to(['load']);
$backendStop = \yii\helpers\Url::to(['stop-executable']);

$this->registerJs(<<<JS

setInterval(function()  {
    var jContainter = $('.tab-content').closest("[data-pjax-container]").attr("id");
    jQuery.pjax.reload("#" + jContainter, {});
}, 15000)

JS
    ,
    \yii\web\View::POS_END
);

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
        },

        stop: function()
        {
            var ajax = sx.ajax.preparePostQuery(this.get("backendStop"));
            var rr = new sx.classes.AjaxHandlerStandartRespose(ajax);

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
        'backend' : '{$backend}',
        'backendStop' : '{$backendStop}'
    });

})(sx, sx.$, sx._);
JS
);
?>





<div class="row g-mb-10">
    <div class="col-md-12">
        <div class="pull-left">
            <?= \yii\helpers\Html::a("<i class=\"glyphicon glyphicon-stop\"></i> ".\Yii::t('skeeks/agent',
                    'Stop running'), "#", [
                'class' => 'btn btn-primary sx-btn-stop-executable',
            ]); ?>
        </div>


        <div class="pull-right">
            <?= \yii\helpers\Html::a("<i class=\"glyphicon glyphicon-retweet\"></i> ".\Yii::t('skeeks/agent',
                    'Find and download of files'), "#", [
                'class' => 'btn btn-primary sx-btn-make',
            ]); ?>
            <span class="sx-legend">
                    <?= \Yii::t('skeeks/agent', 'Found agents'); ?> <span
                        class="sx-green"><?= count(\Yii::$app->cmsAgent->commands); ?></span>
                </span>
        </div>
    </div>
</div>

