<?php

use yii\db\Migration;

class m260313_010007_create_audit_logs_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%audit_logs}}', [
            'id' => $this->bigPrimaryKey(),
            'office_id' => $this->integer(),
            'user_id' => $this->integer(),
            'action' => $this->string(50)->notNull(),
            'entity_type' => $this->string(50),
            'entity_id' => $this->integer(),
            'old_value' => $this->text(),
            'new_value' => $this->text(),
            'ip_address' => $this->string(45),
            'user_agent' => $this->string(255),
            'created_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex('idx-audit-user', '{{%audit_logs}}', 'user_id');
        $this->createIndex('idx-audit-action', '{{%audit_logs}}', 'action');
        $this->createIndex('idx-audit-entity', '{{%audit_logs}}', ['entity_type', 'entity_id']);
        $this->createIndex('idx-audit-created', '{{%audit_logs}}', 'created_at');
    }

    public function safeDown()
    {
        $this->dropTable('{{%audit_logs}}');
    }
}
