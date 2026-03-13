<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Office model - represents a government office
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $address
 * @property string $province
 * @property string $city
 * @property string $phone
 * @property string $email
 * @property int $is_active
 * @property int $created_at
 * @property int $updated_at
 */
class Office extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%offices}}';
    }

    public function rules()
    {
        return [
            [['code', 'name', 'province', 'city'], 'required'],
            [['code'], 'string', 'max' => 20],
            [['name'], 'string', 'max' => 255],
            [['province', 'city'], 'string', 'max' => 100],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['address'], 'string'],
            [['is_active'], 'integer'],
            [['code'], 'unique'],
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

    public function getUsers()
    {
        return $this->hasMany(User::class, ['office_id' => 'id']);
    }

    public function getLokets()
    {
        return $this->hasMany(Loket::class, ['office_id' => 'id']);
    }

    public function getQueues()
    {
        return $this->hasMany(Queue::class, ['office_id' => 'id']);
    }
}
