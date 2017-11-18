<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.04.2016
 */

use yii\db\Schema;
use yii\db\Migration;

class m160417_103220_create_table__cms_agent extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_agent}}", true);
        if ($tableExist) {
            return true;
        }


        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_agent}}", [

            'id' => Schema::TYPE_PK,

            'last_exec_at' => Schema::TYPE_INTEGER . ' NULL',
            'next_exec_at' => Schema::TYPE_INTEGER . ' NOT NULL',

            'name' => Schema::TYPE_TEXT . " NOT NULL",
            'description' => Schema::TYPE_TEXT . " NULL",

            'agent_interval' => Schema::TYPE_INTEGER . " NOT NULL DEFAULT '86400'",
            'priority' => Schema::TYPE_INTEGER . " NOT NULL DEFAULT '100'",

            'active' => "CHAR(1) NOT NULL DEFAULT 'Y'",
            'is_period' => "CHAR(1) NOT NULL DEFAULT 'Y'",
            'is_running' => "CHAR(1) NOT NULL DEFAULT 'N'",

        ], $tableOptions);

        $this->createIndex('cms_agent__last_exec_at', '{{%cms_agent}}', 'last_exec_at');
        $this->createIndex('cms_agent__next_exec_at', '{{%cms_agent}}', 'next_exec_at');
        $this->createIndex('cms_agent__agent_interval', '{{%cms_agent}}', 'agent_interval');
        $this->createIndex('cms_agent__priority', '{{%cms_agent}}', 'priority');
        $this->createIndex('cms_agent__active', '{{%cms_agent}}', 'active');
        $this->createIndex('cms_agent__is_period', '{{%cms_agent}}', 'is_period');
        $this->createIndex('cms_agent__is_running', '{{%cms_agent}}', 'is_running');
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_agent}}');
    }
}
