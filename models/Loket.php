<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Loket extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%loket}}';
    }

    public function rules()
    {
        return [
            [['office_id', 'name', 'code'], 'required'],
            [['name'], 'string', 'max' => 50],
            [['code'], 'string', 'max' => 10],
            [['office_id', 'queue_type_id', 'is_active'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Nama Loket',
            'code' => 'Kode',
            'queue_type_id' => 'Tipe Antrian',
            'is_active' => 'Aktif',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
            }
            $this->updated_at = time();
            return true;
        }
        return false;
    }

    public function getOffice()
    {
        return $this->hasOne(Office::class, ['id' => 'office_id']);
    }

    public function getQueueType()
    {
        return $this->hasOne(QueueType::class, ['id' => 'queue_type_id']);
    }

    public function getOfficer()
    {
        return $this->hasOne(User::class, ['loket_id' => 'id'])
            ->andWhere(['role' => User::ROLE_PETUGAS, 'is_active' => 1]);
    }
}
