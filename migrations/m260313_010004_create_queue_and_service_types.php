<?php

use yii\db\Migration;

class m260313_010004_create_queue_and_service_types extends Migration
{
    public function safeUp()
    {
        // Queue Types
        $this->createTable('{{%queue_types}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(5)->notNull()->unique(),
            'name' => $this->string(50)->notNull(),
            'prefix' => $this->char(1)->notNull(),
            'description' => $this->text(),
            'color' => $this->string(7)->defaultValue('#0d6efd'),
            'is_active' => $this->tinyInteger(1)->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        // Service Types
        $this->createTable('{{%service_types}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(20)->notNull()->unique(),
            'name' => $this->string(100)->notNull(),
            'description' => $this->text(),
            'is_active' => $this->tinyInteger(1)->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');
    }

    public function safeDown()
    {
        $this->dropTable('{{%service_types}}');
        $this->dropTable('{{%queue_types}}');
    }
}
