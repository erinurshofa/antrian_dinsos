<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\models\Queue;
use app\models\QueueType;
use app\models\AuditLog;

class SecurityController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            return $user && ($user->isSatpam() || $user->isAdmin());
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * Security officer panel
     */
    public function actionPanel()
    {
        $queueTypes = QueueType::find()->where(['is_active' => 1])->all();
        $today = date('Y-m-d');

        // Today's statistics
        $counts = [];
        $waitingCounts = [];
        foreach ($queueTypes as $qt) {
            $counts[$qt->id] = Queue::find()
                ->where(['queue_type_id' => $qt->id, 'queue_date' => $today, 'office_id' => 1])
                ->count();
            $waitingCounts[$qt->id] = Queue::find()
                ->where(['queue_type_id' => $qt->id, 'queue_date' => $today, 'office_id' => 1, 'status' => Queue::STATUS_WAITING])
                ->count();
        }

        // Recent queues
        $recentQueues = Queue::find()
            ->where(['queue_date' => $today, 'office_id' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->limit(20)
            ->all();

        return $this->render('panel', [
            'queueTypes' => $queueTypes,
            'counts' => $counts,
            'waitingCounts' => $waitingCounts,
            'recentQueues' => $recentQueues,
        ]);
    }

    /**
     * AJAX: Take queue ticket (by security officer)
     */
    public function actionTake()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $queueTypeId = Yii::$app->request->post('queue_type_id');

        if (!$queueTypeId) {
            return ['success' => false, 'message' => 'Tipe antrian tidak valid'];
        }

        $user = Yii::$app->user->identity;
        $queue = Queue::generateTicket($user->office_id, $queueTypeId);

        if ($queue) {
            return [
                'success' => true,
                'data' => [
                    'id' => $queue->id,
                    'queue_number' => $queue->queue_number,
                    'queue_type' => $queue->queueType->name,
                    'queue_date' => $queue->queue_date,
                    'daily_number' => $queue->daily_number,
                    'created_at' => date('H:i:s'),
                ],
            ];
        }

        return ['success' => false, 'message' => 'Gagal membuat antrian'];
    }
}
