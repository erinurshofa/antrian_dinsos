<?php

namespace app\models;

use yii\db\ActiveRecord;

class ServiceType extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%service_types}}';
    }

    public function rules()
    {
        return [
            [['code', 'name'], 'required'],
            [['code'], 'string', 'max' => 20],
            [['name'], 'string', 'max' => 100],
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

    public static function getDropdownList()
    {
        return static::find()
            ->select(['name', 'id'])
            ->where(['is_active' => 1])
            ->indexBy('id')
            ->column();
    }
}
