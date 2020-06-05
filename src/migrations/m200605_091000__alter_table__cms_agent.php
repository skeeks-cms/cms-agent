<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200605_091000__alter_table__cms_agent extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_agent";

        $this->addColumn($tableName, "is_active", $this->integer(1)->notNull()->defaultValue(1));
        $this->update($tableName, ['is_active' => 0], ['active' => 'N']);
    }

    public function safeDown()
    {
        echo "m200410_111000__alter_table__cms_content_element cannot be reverted.\n";
        return false;
    }
}