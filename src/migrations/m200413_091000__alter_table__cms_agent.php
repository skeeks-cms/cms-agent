<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200413_091000__alter_table__cms_agent extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_agent";

        $this->addColumn($tableName, "cms_site_id", $this->integer());
        $this->createIndex("cms_site_id", $tableName, "cms_site_id");
        $this->addForeignKey(
            "{$tableName}__cms_site_id", $tableName,
            'cms_site_id', "cms_site", 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m200410_111000__alter_table__cms_content_element cannot be reverted.\n";
        return false;
    }
}