<?php

use yii\db\Migration;

class m260313_010005_create_queues_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%queues}}', [
            'id' => $this->primaryKey(),
            'office_id' => $this->integer()->notNull(),
            'queue_type_id' => $this->integer()->notNull(),
            'queue_number' => $this->string(10)->notNull(),
            'daily_number' => $this->integer()->notNull(),
            'queue_date' => $this->date()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('waiting'),
            'loket_id' => $this->integer(),
            'called_by' => $this->integer(),
            'called_at' => $this->dateTime(),
            'served_at' => $this->dateTime(),
            'completed_at' => $this->dateTime(),
            'cancelled_at' => $this->dateTime(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->addForeignKey('fk-queues-office', '{{%queues}}', 'office_id', '{{%offices}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-queues-type', '{{%queues}}', 'queue_type_id', '{{%queue_types}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-queues-loket', '{{%queues}}', 'loket_id', '{{%loket}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-queues-caller', '{{%queues}}', 'called_by', '{{%users}}', 'id', 'SET NULL', 'CASCADE');

        // Performance indexes
        $this->createIndex('idx-queues-date', '{{%queues}}', 'queue_date');
        $this->createIndex('idx-queues-status', '{{%queues}}', 'status');
        $this->createIndex('idx-queues-office-date', '{{%queues}}', ['office_id', 'queue_date']);
        $this->createIndex('idx-queues-type-date', '{{%queues}}', ['queue_type_id', 'queue_date']);
        $this->createIndex('idx-queues-qnumber', '{{%queues}}', 'queue_number');
    }

    public function safeDown()
    {
        $this->dropTable('{{%queues}}');
    }
}
