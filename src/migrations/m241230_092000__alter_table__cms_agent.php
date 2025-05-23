<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m241230_092000__alter_table__cms_agent extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_agent";
        $this->alterColumn($tableName, "is_period", $this->integer(1)->notNull()->defaultValue(0));
    }

    public function safeDown()
    {
        echo "m200410_111000__alter_table__cms_content_element cannot be reverted.\n";
        return false;
    }
}