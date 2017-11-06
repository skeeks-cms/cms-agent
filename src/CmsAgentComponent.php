<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.04.2016
 */
namespace skeeks\cms\agent;
use skeeks\cms\agent\models\CmsAgent;
use skeeks\cms\helpers\FileHelper;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\web\Application;
use Yii;

/**
 * @property string[] $agentsConfigFiles
 * @property [] $agentsConfig
 *
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

    /**
     * @var int Maximum wait time for the agent
     */
    public $agentMaxExecuteTime    = 7200; //2 hours


    public function bootstrap($application)
    {
        if ($application instanceof Application && $this->onHitsEnabled)
        {
            $key = 'Agents';
            Yii::beginProfile(\Yii::t('skeeks/agent', "Agents enabled on the hits"));

                $data = \Yii::$app->cache->get($key);
                if ($data === false)
                {
                    Yii::beginProfile(\Yii::t('skeeks/agent', "Executing"));

                        $result = \Yii::$app->console->execute("cd " . ROOT_DIR . '; php yii cmsAgent/execute;');
                        \Yii::$app->cache->set($key, '1', (int) $this->onHitsInterval);

                    Yii::endProfile(\Yii::t('skeeks/agent', "Executing"));
                }

            Yii::endProfile(\Yii::t('skeeks/agent', "Agents enabled on the hits"));
        }
    }

    /**
     * @return array
     */
    public function getAgentsConfig()
    {
        $result = [];
        foreach ($this->agentsConfigFiles as $filePath)
        {
            $fileData = (array) include $filePath;
            $result = \yii\helpers\ArrayHelper::merge($result, $fileData);
        }

        return (array) $result;
    }

    /**
     * @return array
     */
    public function getAgentsConfigFiles()
    {
        $files = FileHelper::findExtensionsFiles(['/config/agents.php']);
        $files = array_unique(array_merge(
            [
                \Yii::getAlias('@app/config/agents.php'),
                \Yii::getAlias('@common/config/agents.php'),
            ], $files
        ));

        $result = [];
        foreach ($files as $file)
        {
            if (file_exists($file))
            {
                $result[] = $file;
            }
        }

        return $result;
    }

    /**
     * @return $this
     */
    public function loadAgents()
    {
        if ($this->agentsConfig)
        {
            foreach ($this->agentsConfig as $exec => $data)
            {
                if (CmsAgent::find()->where(['name' => $exec])->one())
                {
                    continue;
                }

                $agent = new CmsAgent();
                $agent->name = $exec;
                $agent->agent_interval = ArrayHelper::getValue($data, 'agent_interval');
                $agent->is_period = ArrayHelper::getValue($data, 'is_period');
                $agent->description = ArrayHelper::getValue($data, 'description');
                $agent->save();
            }
        }

        return $this;
    }
}