<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\Queue;
use app\models\Service;
use app\models\ServiceType;
use app\models\QueueType;
use app\models\Loket;
use app\models\AuditLog;

class OfficerController extends Controller
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
                            return $user && ($user->isPetugas() || $user->isAdmin());
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'call' => ['post'],
                    'complete' => ['post'],
                    'save-service' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Service officer panel
     */
    public function actionPanel()
    {
        $user = Yii::$app->user->identity;
        $loket = $user->loket;

        if (!$loket) {
            Yii::$app->session->setFlash('error', 'Anda belum ditugaskan ke loket manapun.');
            return $this->render('no-loket');
        }

        $today = date('Y-m-d');

        // Current queue being served
        $currentQueue = Queue::find()
            ->where([
                'loket_id' => $loket->id,
                'queue_date' => $today,
                'status' => [Queue::STATUS_CALLED, Queue::STATUS_SERVING],
            ])
            ->orderBy(['called_at' => SORT_DESC])
            ->one();

        // Current service record
        $currentService = null;
        if ($currentQueue) {
            $currentService = Service::find()
                ->where(['queue_id' => $currentQueue->id])
                ->one();

            if (!$currentService) {
                $currentService = new Service();
            }
        }

        // Waiting count
        $waitingQuery = Queue::find()
            ->where([
                'office_id' => $user->office_id,
                'queue_date' => $today,
                'status' => Queue::STATUS_WAITING,
            ]);

        if ($loket->queue_type_id) {
            $waitingQuery->andWhere(['queue_type_id' => $loket->queue_type_id]);
        }
        $waitingCount = $waitingQuery->count();

        // Completed today by this officer
        $completedCount = Queue::find()
            ->where([
                'called_by' => $user->id,
                'queue_date' => $today,
                'status' => Queue::STATUS_COMPLETED,
            ])
            ->count();

        $serviceTypes = ServiceType::getDropdownList();

        return $this->render('panel', [
            'user' => $user,
            'loket' => $loket,
            'currentQueue' => $currentQueue,
            'currentService' => $currentService,
            'waitingCount' => $waitingCount,
            'completedCount' => $completedCount,
            'serviceTypes' => $serviceTypes,
        ]);
    }

    /**
     * AJAX: Call next queue
     */
    public function actionCall()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        $loket = $user->loket;

        if (!$loket) {
            return ['success' => false, 'message' => 'Anda belum ditugaskan ke loket'];
        }

        // Check if there's already a queue being served
        $today = date('Y-m-d');
        $activeQueue = Queue::find()
            ->where([
                'loket_id' => $loket->id,
                'queue_date' => $today,
                'status' => [Queue::STATUS_CALLED, Queue::STATUS_SERVING],
            ])
            ->one();

        if ($activeQueue) {
            return [
                'success' => false,
                'message' => 'Selesaikan antrian ' . $activeQueue->queue_number . ' terlebih dahulu',
            ];
        }

        $queue = Queue::callNext($loket->id, $user->id);

        if ($queue) {
            // Create service record
            $service = new Service();
            $service->queue_id = $queue->id;
            $service->office_id = $user->office_id;
            $service->loket_id = $loket->id;
            $service->officer_id = $user->id;
            $service->start_time = date('Y-m-d H:i:s');
            $service->status = 'serving';
            $service->save(false);

            // Update queue status
            $queue->status = Queue::STATUS_SERVING;
            $queue->served_at = date('Y-m-d H:i:s');
            $queue->save(false);

            // Loket name for voice
            $loketNumber = preg_replace('/[^0-9]/', '', $loket->name);

            return [
                'success' => true,
                'data' => [
                    'queue_id' => $queue->id,
                    'queue_number' => $queue->queue_number,
                    'queue_type' => $queue->queueType->name,
                    'loket_name' => $loket->name,
                    'loket_number' => $loketNumber,
                    'service_id' => $service->id,
                    'called_at' => $queue->called_at,
                ],
            ];
        }

        return ['success' => false, 'message' => 'Tidak ada antrian yang menunggu'];
    }

    /**
     * AJAX: Complete current service
     */
    public function actionComplete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        $queueId = Yii::$app->request->post('queue_id');

        $queue = Queue::findOne($queueId);
        if (!$queue || $queue->called_by != $user->id) {
            return ['success' => false, 'message' => 'Antrian tidak ditemukan'];
        }

        $queue->status = Queue::STATUS_COMPLETED;
        $queue->completed_at = date('Y-m-d H:i:s');
        $queue->save(false);

        // Update service
        $service = Service::find()->where(['queue_id' => $queue->id])->one();
        if ($service) {
            $service->end_time = date('Y-m-d H:i:s');
            $service->status = 'completed';
            $service->save(false);
        }

        AuditLog::log('queue_completed', 'queue', $queue->id, null, $queue->queue_number);

        return [
            'success' => true,
            'message' => 'Antrian ' . $queue->queue_number . ' telah selesai dilayani',
        ];
    }

    /**
     * AJAX: Save service data entry
     */
    public function actionSaveService()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $serviceId = Yii::$app->request->post('service_id');
        $service = Service::findOne($serviceId);

        if (!$service) {
            return ['success' => false, 'message' => 'Data layanan tidak ditemukan'];
        }

        $service->nama = Yii::$app->request->post('nama');
        $service->jenis_kelamin = Yii::$app->request->post('jenis_kelamin');
        $service->no_hp = Yii::$app->request->post('no_hp');
        $service->nik = Yii::$app->request->post('nik');
        $service->service_type_id = Yii::$app->request->post('service_type_id') ?: null;
        $service->keperluan = Yii::$app->request->post('keperluan');
        $service->keterangan = Yii::$app->request->post('keterangan');

        if ($service->save()) {
            return ['success' => true, 'message' => 'Data layanan berhasil disimpan'];
        }

        return ['success' => false, 'message' => 'Gagal menyimpan data', 'errors' => $service->errors];
    }

    /**
     * History of completed services — filterable by date, search, queue type
     */
    public function actionHistory()
    {
        $request = Yii::$app->request;
        $dateFrom = $request->get('date_from', date('Y-m-d'));
        $dateTo = $request->get('date_to', date('Y-m-d'));
        $search = $request->get('search', '');
        $queueType = $request->get('queue_type', '');

        $query = Service::find()
            ->alias('s')
            ->joinWith(['queue q', 'loket l', 'officer o', 'serviceType st', 'queue.queueType qt'])
            ->where(['s.status' => 'completed'])
            ->andWhere(['>=', 'q.queue_date', $dateFrom])
            ->andWhere(['<=', 'q.queue_date', $dateTo]);

        if ($search) {
            $query->andWhere([
                'or',
                ['like', 's.nama', $search],
                ['like', 's.no_hp', $search],
                ['like', 's.nik', $search],
                ['like', 'q.queue_number', $search],
                ['like', 's.keperluan', $search],
            ]);
        }

        if ($queueType) {
            $query->andWhere(['q.queue_type_id' => $queueType]);
        }

        $totalCount = clone $query;
        $total = $totalCount->count();

        // Pagination
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 20;
        $totalPages = max(1, ceil($total / $perPage));
        $page = min($page, $totalPages);

        $services = $query
            ->orderBy(['q.queue_date' => SORT_DESC, 'q.called_at' => SORT_DESC])
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->all();

        $queueTypes = QueueType::find()->where(['is_active' => 1])->all();

        return $this->render('history', [
            'services' => $services,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'search' => $search,
            'queueType' => $queueType,
            'queueTypes' => $queueTypes,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ]);
    }

    /**
     * Export completed services to CSV
     */
    public function actionExport()
    {
        $request = Yii::$app->request;
        $dateFrom = $request->get('date_from', date('Y-m-d'));
        $dateTo = $request->get('date_to', date('Y-m-d'));
        $search = $request->get('search', '');
        $queueType = $request->get('queue_type', '');

        $query = Service::find()
            ->alias('s')
            ->joinWith(['queue q', 'loket l', 'officer o', 'serviceType st', 'queue.queueType qt'])
            ->where(['s.status' => 'completed'])
            ->andWhere(['>=', 'q.queue_date', $dateFrom])
            ->andWhere(['<=', 'q.queue_date', $dateTo]);

        if ($search) {
            $query->andWhere([
                'or',
                ['like', 's.nama', $search],
                ['like', 's.no_hp', $search],
                ['like', 's.nik', $search],
                ['like', 'q.queue_number', $search],
            ]);
        }

        if ($queueType) {
            $query->andWhere(['q.queue_type_id' => $queueType]);
        }

        $services = $query
            ->orderBy(['q.queue_date' => SORT_DESC, 'q.called_at' => SORT_DESC])
            ->all();

        // Build CSV
        $filename = "riwayat_layanan_{$dateFrom}_sd_{$dateTo}.csv";

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        // Clear buffers
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $fp = fopen('php://output', 'w');

        // BOM for UTF-8 Excel compatibility
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header row
        fputcsv($fp, [
            'No',
            'Tanggal',
            'No. Antrian',
            'Tipe Antrian',
            'Nama',
            'Jenis Kelamin',
            'No. HP',
            'NIK',
            'Jenis Layanan',
            'Keperluan',
            'Keterangan',
            'Loket',
            'Petugas',
            'Waktu Mulai',
            'Waktu Selesai',
            'Durasi',
        ]);

        // Data rows
        $no = 0;
        foreach ($services as $svc) {
            $no++;
            $q = $svc->queue;
            fputcsv($fp, [
                $no,
                $q ? $q->queue_date : '-',
                $q ? $q->queue_number : '-',
                $q && $q->queueType ? $q->queueType->name : '-',
                $svc->nama ?: '-',
                $svc->jenis_kelamin === 'L' ? 'Laki-laki' : ($svc->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                $svc->no_hp ?: '-',
                $svc->nik ?: '-',
                $svc->serviceType ? $svc->serviceType->name : '-',
                $svc->keperluan ?: '-',
                $svc->keterangan ?: '-',
                $svc->loket ? $svc->loket->name : '-',
                $svc->officer ? $svc->officer->fullname : '-',
                $svc->start_time ?: '-',
                $svc->end_time ?: '-',
                $svc->getDurationFormatted(),
            ]);
        }

        fclose($fp);
        Yii::$app->end();
    }
}
