<?php

use yii\db\Migration;

class m260313_010001_create_offices_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%offices}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(20)->notNull()->unique(),
            'name' => $this->string(255)->notNull(),
            'address' => $this->text(),
            'province' => $this->string(100)->notNull(),
            'city' => $this->string(100)->notNull(),
            'phone' => $this->string(20),
            'email' => $this->string(100),
            'is_active' => $this->tinyInteger(1)->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex('idx-offices-province', '{{%offices}}', 'province');
        $this->createIndex('idx-offices-city', '{{%offices}}', 'city');
        $this->createIndex('idx-offices-active', '{{%offices}}', 'is_active');
    }

    public function safeDown()
    {
        $this->dropTable('{{%offices}}');
    }
}
