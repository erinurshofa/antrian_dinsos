<?php

use yii\db\Migration;

class m260313_010002_create_users_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%users}}', [
            'id' => $this->primaryKey(),
            'office_id' => $this->integer()->notNull(),
            'username' => $this->string(50)->notNull()->unique(),
            'fullname' => $this->string(100)->notNull(),
            'email' => $this->string(100),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string(255)->notNull(),
            'password_reset_token' => $this->string(255)->unique(),
            'role' => $this->string(20)->notNull()->defaultValue('petugas'),
            'loket_id' => $this->integer(),
            'is_active' => $this->tinyInteger(1)->defaultValue(1),
            'last_login_at' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->addForeignKey('fk-users-office', '{{%users}}', 'office_id', '{{%offices}}', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('idx-users-role', '{{%users}}', 'role');
        $this->createIndex('idx-users-active', '{{%users}}', 'is_active');
    }

    public function safeDown()
    {
        $this->dropTable('{{%users}}');
    }
}
