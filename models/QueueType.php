<?php

namespace app\models;

use yii\db\ActiveRecord;

class QueueType extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%queue_types}}';
    }

    public function rules()
    {
        return [
            [['code', 'name', 'prefix'], 'required'],
            [['code'], 'string', 'max' => 5],
            [['name'], 'string', 'max' => 50],
            [['prefix'], 'string', 'max' => 1],
            [['color'], 'string', 'max' => 7],
            [['description'], 'string'],
            [['is_active'], 'integer'],
            [['code'], 'unique'],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) $this->created_at = time();
            $this->updated_at = time();
            return true;
        }
        return false;
    }
}
