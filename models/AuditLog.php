<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class AuditLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%audit_logs}}';
    }

    public function rules()
    {
        return [
            [['action'], 'required'],
            [['action'], 'string', 'max' => 50],
            [['entity_type'], 'string', 'max' => 50],
            [['ip_address'], 'string', 'max' => 45],
            [['user_agent'], 'string', 'max' => 255],
            [['old_value', 'new_value'], 'string'],
            [['office_id', 'user_id', 'entity_id'], 'integer'],
        ];
    }

    /**
     * Quick log helper
     */
    public static function log($action, $entityType = null, $entityId = null, $oldValue = null, $newValue = null)
    {
        $log = new self();
        $log->action = $action;
        $log->entity_type = $entityType;
        $log->entity_id = $entityId;
        $log->old_value = is_array($oldValue) ? json_encode($oldValue) : $oldValue;
        $log->new_value = is_array($newValue) ? json_encode($newValue) : $newValue;
        $log->created_at = time();

        if (!Yii::$app instanceof \yii\console\Application) {
            $log->user_id = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
            $log->ip_address = Yii::$app->request->userIP;
            $log->user_agent = substr(Yii::$app->request->userAgent ?? '', 0, 255);

            $user = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;
            $log->office_id = $user ? $user->office_id : null;
        }

        $log->save(false);
        return $log;
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
