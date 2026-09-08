<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 */

use yii\db\Migration;

/**
 * Расписание начинает создавать фоновое задание вместо запуска команды.
 *
 * Колонки необязательные: агент без job_type работает по-прежнему, поэтому
 * проекты без skeeks/cms-job ничего не теряют.
 */
class m260822_220000__alter_table__cms_agent__job_type extends Migration
{
    public function safeUp()
    {
        $tableName = '{{%cms_agent}}';

        $this->addColumn(
            $tableName,
            'job_type',
            $this->string(128)->null()->comment('Тип задания в реестре; пусто — прежний запуск команды')
        );

        $this->addColumn(
            $tableName,
            'job_payload',
            $this->text()->null()->comment('Параметры задания, JSON')
        );

        $this->createIndex('cms_agent__job_type', $tableName, 'job_type');
    }

    public function safeDown()
    {
        $tableName = '{{%cms_agent}}';

        $this->dropIndex('cms_agent__job_type', $tableName);
        $this->dropColumn($tableName, 'job_payload');
        $this->dropColumn($tableName, 'job_type');

        return true;
    }
}
