<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m241202_091000__alter_table__cms_agent extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_agent";

        $this->addColumn($tableName, "is_system", $this->integer(1)->defaultValue(0)->notNull()->comment("0-пользовательский 1 - системный"));
        $this->createIndex("is_system", $tableName, "is_system");
    }

    public function safeDown()
    {
        echo "m200410_111000__alter_table__cms_content_element cannot be reverted.\n";
        return false;
    }
}