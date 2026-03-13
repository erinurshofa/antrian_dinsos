<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model with RBAC role field
 *
 * Roles: admin, satpam, petugas, pimpinan
 *
 * @property int $id
 * @property int $office_id
 * @property string $username
 * @property string $fullname
 * @property string $email
 * @property string $auth_key
 * @property string $password_hash
 * @property string $role
 * @property int $loket_id
 * @property int $is_active
 * @property int $created_at
 * @property int $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    const ROLE_ADMIN = 'admin';
    const ROLE_SATPAM = 'satpam';
    const ROLE_PETUGAS = 'petugas';
    const ROLE_PIMPINAN = 'pimpinan';

    public $password;

    public static function tableName()
    {
        return '{{%users}}';
    }

    public function rules()
    {
        return [
            [['office_id', 'username', 'fullname'], 'required'],
            [['username'], 'string', 'max' => 50],
            [['fullname'], 'string', 'max' => 100],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['role'], 'string', 'max' => 20],
            [['role'], 'in', 'range' => [self::ROLE_ADMIN, self::ROLE_SATPAM, self::ROLE_PETUGAS, self::ROLE_PIMPINAN]],
            [['office_id', 'loket_id', 'is_active'], 'integer'],
            [['username'], 'unique'],
            [['password'], 'string', 'min' => 6],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'fullname' => 'Nama Lengkap',
            'email' => 'Email',
            'role' => 'Peran',
            'loket_id' => 'Loket',
            'is_active' => 'Aktif',
        ];
    }

    // IdentityInterface methods
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'is_active' => 1]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'is_active' => 1]);
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
                $this->generateAuthKey();
            }
            if (!empty($this->password)) {
                $this->setPassword($this->password);
            }
            $this->updated_at = time();
            return true;
        }
        return false;
    }

    // Relations
    public function getOffice()
    {
        return $this->hasOne(Office::class, ['id' => 'office_id']);
    }

    public function getLoket()
    {
        return $this->hasOne(Loket::class, ['id' => 'loket_id']);
    }

    // Role helpers
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSatpam()
    {
        return $this->role === self::ROLE_SATPAM;
    }

    public function isPetugas()
    {
        return $this->role === self::ROLE_PETUGAS;
    }

    public function isPimpinan()
    {
        return $this->role === self::ROLE_PIMPINAN;
    }

    public static function getRoleList()
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_SATPAM => 'Satpam',
            self::ROLE_PETUGAS => 'Petugas Pelayanan',
            self::ROLE_PIMPINAN => 'Pimpinan',
        ];
    }

    public function getRoleName()
    {
        $roles = self::getRoleList();
        return $roles[$this->role] ?? $this->role;
    }
}
