<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.04.2016
 */
namespace skeeks\cms\agent;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\web\Application;
use Yii;

/**
 * Class CmsAgentComponent
 * @package skeeks\cms\agent
 */
class CmsAgentComponent extends Component implements BootstrapInterface
{
    /**
     * @var bool Enabled agents on the hits
     */
    public $onHitsEnabled   = true;

    /**
     * @var int Interval if enabled agents on the hits
     */
    public $onHitsInterval  = 60;


    public function bootstrap($application)
    {
        if ($application instanceof Application && $this->onHitsEnabled)
        {
            $key = 'Agents';
            Yii::beginProfile(\Yii::t('skeeks/agent', "Enabled agents on the hits"));

                $data = \Yii::$app->cache->get($key);
                if ($data === false)
                {
                    Yii::beginProfile(\Yii::t('skeeks/agent', "Executing"));

                        $result = \Yii::$app->console->execute("cd " . ROOT_DIR . '; php yii cmsAgent/execute;');
                        \Yii::$app->cache->set($key, '1', (int) $this->onHitsInterval);

                    Yii::endProfile(\Yii::t('skeeks/agent', "Executing"));
                }

            Yii::endProfile(\Yii::t('skeeks/agent', "Enabled agents on the hits"));
        }
    }
}