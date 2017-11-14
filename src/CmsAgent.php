<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 14.11.2017
 */

namespace skeeks\cms\agent;

use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * @property string $name;
 *
 * Class CmsAgent
 * @package skeeks\cms\agent
 */
class CmsAgent extends Component
{
    /**
     * @var string console command
     */
    public $command;

    /**
     * @var integer
     */
    public $interval;

    /**
     * @var boolean
     */
    public $is_period = false;

    /**
     * @var string
     */
    protected $_name;

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {

        if (!$this->command) {
            throw new InvalidConfigException('Property command is required!');
        }

        if (!$this->interval || !is_int($this->interval)) {
            throw new InvalidConfigException('Property interval is required is integer!');
        }

        parent::init();
    }


    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        if (is_array($name) && count($name) == 2) {
            $this->_name = \Yii::t($name[0], $name[1]);
        } else {
            if (is_string($name)) {
                $this->_name = $name;
            } else {
                throw new \InvalidArgumentException('Property name must be array or string');
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return (string)$this->_name;
    }
}