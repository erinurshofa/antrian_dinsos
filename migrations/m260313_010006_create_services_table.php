<?php

use yii\db\Migration;

class m260313_010006_create_services_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%services}}', [
            'id' => $this->primaryKey(),
            'queue_id' => $this->integer()->notNull(),
            'office_id' => $this->integer()->notNull(),
            'loket_id' => $this->integer()->notNull(),
            'officer_id' => $this->integer()->notNull(),
            'service_type_id' => $this->integer(),
            'nama' => $this->string(100),
            'jenis_kelamin' => $this->string(1),
            'no_hp' => $this->string(20),
            'nik' => $this->string(16),
            'keperluan' => $this->string(100),
            'keterangan' => $this->text(),
            'start_time' => $this->dateTime()->notNull(),
            'end_time' => $this->dateTime(),
            'duration_seconds' => $this->integer(),
            'status' => $this->string(20)->notNull()->defaultValue('serving'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->addForeignKey('fk-services-queue', '{{%services}}', 'queue_id', '{{%queues}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-services-office', '{{%services}}', 'office_id', '{{%offices}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-services-loket', '{{%services}}', 'loket_id', '{{%loket}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-services-officer', '{{%services}}', 'officer_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-services-type', '{{%services}}', 'service_type_id', '{{%service_types}}', 'id', 'SET NULL', 'CASCADE');

        $this->createIndex('idx-services-date', '{{%services}}', 'start_time');
        $this->createIndex('idx-services-status', '{{%services}}', 'status');
        $this->createIndex('idx-services-officer', '{{%services}}', 'officer_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%services}}');
    }
}
