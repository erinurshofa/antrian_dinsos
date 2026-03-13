<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\models\Queue;
use app\models\QueueType;
use app\models\AuditLog;

class QueueController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['take'],
                'rules' => [
                    [
                        'actions' => ['take'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Visitor kiosk — touch screen to take ticket
     * Public access (no login required)
     */
    public function actionKiosk()
    {
        $this->layout = 'kiosk';

        $queueTypes = QueueType::find()->where(['is_active' => 1])->all();
        $today = date('Y-m-d');

        // Get today's counts
        $counts = [];
        foreach ($queueTypes as $qt) {
            $counts[$qt->id] = Queue::find()
                ->where(['queue_type_id' => $qt->id, 'queue_date' => $today, 'office_id' => 1])
                ->count();
        }

        return $this->render('kiosk', [
            'queueTypes' => $queueTypes,
            'counts' => $counts,
        ]);
    }

    /**
     * AJAX: Generate a queue ticket
     */
    public function actionTake()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $queueTypeId = $request->post('queue_type_id');

        if (!$queueTypeId) {
            return ['success' => false, 'message' => 'Tipe antrian tidak valid'];
        }

        $officeId = 1; // Default office, can be dynamic
        $queue = Queue::generateTicket($officeId, $queueTypeId);

        if ($queue) {
            $queueType = $queue->queueType;
            return [
                'success' => true,
                'data' => [
                    'id' => $queue->id,
                    'queue_number' => $queue->queue_number,
                    'queue_type' => $queueType->name,
                    'queue_date' => $queue->queue_date,
                    'daily_number' => $queue->daily_number,
                    'created_at' => date('H:i:s'),
                ],
            ];
        }

        return ['success' => false, 'message' => 'Gagal membuat antrian'];
    }

    /**
     * Public queue display (TV screen)
     */
    public function actionDisplay()
    {
        $this->layout = 'kiosk';
        $officeId = 1;

        $serving = Queue::getCurrentServing($officeId);
        $waitingCounts = Queue::getWaitingCounts($officeId);
        $queueTypes = QueueType::find()->where(['is_active' => 1])->indexBy('id')->all();

        return $this->render('display', [
            'serving' => $serving,
            'waitingCounts' => $waitingCounts,
            'queueTypes' => $queueTypes,
        ]);
    }

    /**
     * SSE stream for real-time updates
     */
    public function actionStream()
    {
        $officeId = Yii::$app->request->get('office_id', 1);

        // Disable buffering
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', 1);
        }
        @ini_set('zlib.output_compression', 0);
        @ini_set('output_buffering', 0);
        @ini_set('implicit_flush', 1);

        // Clear ALL output buffers
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        // Send SSE headers directly
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Send initial data
        $data = $this->getDisplayData($officeId);
        echo "data: " . json_encode($data) . "\n\n";
        @ob_flush();
        @flush();

        $lastUpdate = time();
        $maxTime = 60; // Max 60 seconds per connection
        $startTime = time();

        while ((time() - $startTime) < $maxTime) {
            $currentUpdate = Queue::getLastUpdate($officeId);

            if ($currentUpdate > $lastUpdate) {
                $data = $this->getDisplayData($officeId);
                echo "data: " . json_encode($data) . "\n\n";
                @ob_flush();
                @flush();
                $lastUpdate = $currentUpdate;
            }

            // Heartbeat every 15 seconds
            if ((time() - $startTime) % 15 === 0) {
                echo ": heartbeat\n\n";
                @ob_flush();
                @flush();
            }

            if (connection_aborted()) break;
            sleep(2);
        }

        Yii::$app->end();
    }

    /**
     * REST API: Get current queue status
     */
    public function actionStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $officeId = Yii::$app->request->get('office_id', 1);
        return $this->getDisplayData($officeId);
    }

    private function getDisplayData($officeId)
    {
        $serving = Queue::getCurrentServing($officeId);
        $waitingCounts = Queue::getWaitingCounts($officeId);

        $servingData = [];
        foreach ($serving as $q) {
            $servingData[] = [
                'queue_number' => $q->queue_number,
                'loket_name' => $q->loket ? $q->loket->name : '-',
                'loket_code' => $q->loket ? $q->loket->code : '-',
                'queue_type' => $q->queueType ? $q->queueType->name : '-',
                'queue_type_code' => $q->queueType ? $q->queueType->code : '-',
                'queue_type_color' => $q->queueType ? $q->queueType->color : '#0d6efd',
                'status' => $q->status,
                'called_at' => $q->called_at,
            ];
        }

        $waitingData = [];
        foreach ($waitingCounts as $wc) {
            $waitingData[$wc['queue_type_id']] = (int)$wc['cnt'];
        }

        // Called history: all queues that have been called/served/completed today
        $today = date('Y-m-d');
        $calledHistory = Queue::find()
            ->alias('q')
            ->joinWith(['loket l', 'queueType qt'])
            ->where([
                'q.office_id' => $officeId,
                'q.queue_date' => $today,
                'q.status' => [Queue::STATUS_CALLED, Queue::STATUS_SERVING, Queue::STATUS_COMPLETED],
            ])
            ->orderBy(['q.called_at' => SORT_DESC])
            ->limit(30)
            ->all();

        $historyData = [];
        foreach ($calledHistory as $q) {
            $historyData[] = [
                'queue_number' => $q->queue_number,
                'loket_name' => $q->loket ? $q->loket->name : '-',
                'queue_type' => $q->queueType ? $q->queueType->name : '-',
                'queue_type_code' => $q->queueType ? $q->queueType->code : '-',
                'status' => $q->status,
                'called_at' => $q->called_at,
            ];
        }

        return [
            'success' => true,
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'serving' => $servingData,
            'waiting' => $waitingData,
            'history' => $historyData,
        ];
    }
}
