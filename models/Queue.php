<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Queue model - core queue logic
 *
 * Status lifecycle: waiting → called → serving → completed / cancelled
 */
class Queue extends ActiveRecord
{
    const STATUS_WAITING = 'waiting';
    const STATUS_CALLED = 'called';
    const STATUS_SERVING = 'serving';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_SKIPPED = 'skipped';

    public static function tableName()
    {
        return '{{%queues}}';
    }

    public function rules()
    {
        return [
            [['office_id', 'queue_type_id', 'queue_number', 'daily_number', 'queue_date'], 'required'],
            [['office_id', 'queue_type_id', 'daily_number', 'loket_id', 'called_by'], 'integer'],
            [['queue_number'], 'string', 'max' => 10],
            [['queue_date', 'called_at', 'served_at', 'completed_at', 'cancelled_at'], 'safe'],
            [['status'], 'in', 'range' => [
                self::STATUS_WAITING,
                self::STATUS_CALLED,
                self::STATUS_SERVING,
                self::STATUS_COMPLETED,
                self::STATUS_CANCELLED,
                self::STATUS_SKIPPED,
            ]],
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

    // Relations
    public function getQueueType()
    {
        return $this->hasOne(QueueType::class, ['id' => 'queue_type_id']);
    }

    public function getLoket()
    {
        return $this->hasOne(Loket::class, ['id' => 'loket_id']);
    }

    public function getOffice()
    {
        return $this->hasOne(Office::class, ['id' => 'office_id']);
    }

    public function getCaller()
    {
        return $this->hasOne(User::class, ['id' => 'called_by']);
    }

    public function getService()
    {
        return $this->hasOne(Service::class, ['queue_id' => 'id']);
    }

    /**
     * Generate a new queue ticket
     * @param int $officeId
     * @param int $queueTypeId
     * @return Queue|null
     */
    public static function generateTicket($officeId, $queueTypeId)
    {
        $today = date('Y-m-d');
        $queueType = QueueType::findOne($queueTypeId);
        if (!$queueType) return null;

        // Get next daily number with row-lock to prevent race condition
        $lastQueue = static::find()
            ->where([
                'office_id' => $officeId,
                'queue_type_id' => $queueTypeId,
                'queue_date' => $today,
            ])
            ->orderBy(['daily_number' => SORT_DESC])
            ->limit(1)
            ->one();

        $nextNumber = $lastQueue ? ($lastQueue->daily_number + 1) : 1;
        $queueNumber = $queueType->prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $queue = new self();
        $queue->office_id = $officeId;
        $queue->queue_type_id = $queueTypeId;
        $queue->queue_number = $queueNumber;
        $queue->daily_number = $nextNumber;
        $queue->queue_date = $today;
        $queue->status = self::STATUS_WAITING;

        if ($queue->save()) {
            AuditLog::log('queue_created', 'queue', $queue->id, null, $queueNumber);
            return $queue;
        }

        return null;
    }

    /**
     * Call next waiting queue for a loket
     * @param int $loketId
     * @param int $userId
     * @return Queue|null
     */
    public static function callNext($loketId, $userId)
    {
        $loket = Loket::findOne($loketId);
        if (!$loket) return null;

        $today = date('Y-m-d');

        $query = static::find()
            ->where([
                'office_id' => $loket->office_id,
                'queue_date' => $today,
                'status' => self::STATUS_WAITING,
            ]);

        // If loket is assigned to a queue type, only pull that type
        if ($loket->queue_type_id) {
            $query->andWhere(['queue_type_id' => $loket->queue_type_id]);
        }

        $queue = $query->orderBy(['daily_number' => SORT_ASC])->limit(1)->one();

        if ($queue) {
            $queue->status = self::STATUS_CALLED;
            $queue->loket_id = $loketId;
            $queue->called_by = $userId;
            $queue->called_at = date('Y-m-d H:i:s');
            $queue->save(false);

            AuditLog::log('queue_called', 'queue', $queue->id, null, $queue->queue_number . ' → ' . $loket->name);
        }

        return $queue;
    }

    /**
     * Get currently serving/called queues for display
     */
    public static function getCurrentServing($officeId)
    {
        $today = date('Y-m-d');

        return static::find()
            ->alias('q')
            ->joinWith(['loket l', 'queueType qt'])
            ->where([
                'q.office_id' => $officeId,
                'q.queue_date' => $today,
                'q.status' => [self::STATUS_CALLED, self::STATUS_SERVING],
            ])
            ->orderBy(['q.called_at' => SORT_DESC])
            ->all();
    }

    /**
     * Get waiting count per queue type for today
     */
    public static function getWaitingCounts($officeId)
    {
        $today = date('Y-m-d');

        return static::find()
            ->select(['queue_type_id', 'COUNT(*) as cnt'])
            ->where([
                'office_id' => $officeId,
                'queue_date' => $today,
                'status' => self::STATUS_WAITING,
            ])
            ->groupBy('queue_type_id')
            ->asArray()
            ->all();
    }

    /**
     * Get last called queue number (for display update check)
     */
    public static function getLastUpdate($officeId)
    {
        $today = date('Y-m-d');
        $queue = static::find()
            ->where([
                'office_id' => $officeId,
                'queue_date' => $today,
            ])
            ->orderBy(['updated_at' => SORT_DESC])
            ->limit(1)
            ->one();

        return $queue ? $queue->updated_at : 0;
    }

    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_WAITING => '<span class="badge bg-warning text-dark">Menunggu</span>',
            self::STATUS_CALLED => '<span class="badge bg-info">Dipanggil</span>',
            self::STATUS_SERVING => '<span class="badge bg-primary">Dilayani</span>',
            self::STATUS_COMPLETED => '<span class="badge bg-success">Selesai</span>',
            self::STATUS_CANCELLED => '<span class="badge bg-danger">Dibatalkan</span>',
            self::STATUS_SKIPPED => '<span class="badge bg-secondary">Dilewati</span>',
        ];
        return $labels[$this->status] ?? $this->status;
    }
}
