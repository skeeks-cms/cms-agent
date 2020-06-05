<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200605_092000__alter_table__cms_agent extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_agent";

        $this->update($tableName, ['is_period' => 0], ['is_period' => 'N']);
        $this->update($tableName, ['is_period' => 1], ['is_period' => 'Y']);

        $this->update($tableName, ['is_running' => 0], ['is_running' => 'N']);
        $this->update($tableName, ['is_running' => 1], ['is_running' => 'Y']);

        $this->alterColumn($tableName, "is_running", $this->integer(1)->notNull()->defaultValue(1));
        $this->alterColumn($tableName, "is_running", $this->integer(1)->notNull()->defaultValue(0));
    }

    public function safeDown()
    {
        echo "m200410_111000__alter_table__cms_content_element cannot be reverted.\n";
        return false;
    }
}