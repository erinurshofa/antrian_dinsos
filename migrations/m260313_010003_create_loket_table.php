<?php

use yii\db\Migration;

class m260313_010003_create_loket_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%loket}}', [
            'id' => $this->primaryKey(),
            'office_id' => $this->integer()->notNull(),
            'name' => $this->string(50)->notNull(),
            'code' => $this->string(10)->notNull(),
            'queue_type_id' => $this->integer(),
            'is_active' => $this->tinyInteger(1)->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->addForeignKey('fk-loket-office', '{{%loket}}', 'office_id', '{{%offices}}', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('idx-loket-active', '{{%loket}}', 'is_active');
    }

    public function safeDown()
    {
        $this->dropTable('{{%loket}}');
    }
}
