<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\models\Queue;
use app\models\Service;
use app\models\ServiceType;
use app\models\QueueType;
use app\models\Loket;

class DashboardController extends Controller
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
                            return $user && ($user->isPimpinan() || $user->isAdmin());
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * Leadership dashboard
     */
    public function actionIndex()
    {
        $today = date('Y-m-d');
        $user = Yii::$app->user->identity;
        $officeId = $user->office_id;

        // Summary cards
        $totalToday = Queue::find()->where(['queue_date' => $today, 'office_id' => $officeId])->count();
        $completedToday = Queue::find()->where(['queue_date' => $today, 'office_id' => $officeId, 'status' => Queue::STATUS_COMPLETED])->count();
        $waitingNow = Queue::find()->where(['queue_date' => $today, 'office_id' => $officeId, 'status' => Queue::STATUS_WAITING])->count();
        $servingNow = Queue::find()->where(['queue_date' => $today, 'office_id' => $officeId, 'status' => [Queue::STATUS_CALLED, Queue::STATUS_SERVING]])->count();

        // Average service time today
        $avgDuration = Service::find()
            ->where(['>=', 'start_time', $today . ' 00:00:00'])
            ->andWhere(['office_id' => $officeId, 'status' => 'completed'])
            ->andWhere(['IS NOT', 'duration_seconds', null])
            ->average('duration_seconds');

        $serviceTypes = ServiceType::getDropdownList();
        $queueTypes = QueueType::find()->where(['is_active' => 1])->all();
        $lokets = Loket::find()->where(['office_id' => $officeId, 'is_active' => 1])->all();

        return $this->render('index', [
            'totalToday' => $totalToday,
            'completedToday' => $completedToday,
            'waitingNow' => $waitingNow,
            'servingNow' => $servingNow,
            'avgDuration' => $avgDuration ? round($avgDuration) : 0,
            'serviceTypes' => $serviceTypes,
            'queueTypes' => $queueTypes,
            'lokets' => $lokets,
        ]);
    }

    /**
     * AJAX: Dashboard statistics data
     */
    public function actionStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $officeId = Yii::$app->user->identity->office_id;

        // Daily counts for line chart
        $dailyCounts = (new \yii\db\Query())
            ->select(['queue_date', 'COUNT(*) as total', 'queue_type_id'])
            ->from('{{%queues}}')
            ->where(['between', 'queue_date', $startDate, $endDate])
            ->andWhere(['office_id' => $officeId])
            ->groupBy(['queue_date', 'queue_type_id'])
            ->orderBy(['queue_date' => SORT_ASC])
            ->all();

        // Service type distribution (pie chart)
        $serviceDistribution = (new \yii\db\Query())
            ->select(['st.name', 'COUNT(*) as total'])
            ->from('{{%services}} s')
            ->leftJoin('{{%service_types}} st', 'st.id = s.service_type_id')
            ->where(['between', 's.start_time', $startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->andWhere(['s.office_id' => $officeId])
            ->andWhere(['IS NOT', 's.service_type_id', null])
            ->groupBy(['s.service_type_id'])
            ->all();

        // Gender distribution
        $genderDistribution = (new \yii\db\Query())
            ->select(['jenis_kelamin', 'COUNT(*) as total'])
            ->from('{{%services}}')
            ->where(['between', 'start_time', $startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->andWhere(['office_id' => $officeId])
            ->andWhere(['IS NOT', 'jenis_kelamin', null])
            ->andWhere(['<>', 'jenis_kelamin', ''])
            ->groupBy(['jenis_kelamin'])
            ->all();

        // Peak hours (bar chart)
        $peakHours = (new \yii\db\Query())
            ->select([new \yii\db\Expression('HOUR(called_at) as hour'), 'COUNT(*) as total'])
            ->from('{{%queues}}')
            ->where(['between', 'queue_date', $startDate, $endDate])
            ->andWhere(['office_id' => $officeId])
            ->andWhere(['IS NOT', 'called_at', null])
            ->groupBy([new \yii\db\Expression('HOUR(called_at)')])
            ->orderBy(['hour' => SORT_ASC])
            ->all();

        // Loket performance
        $loketPerformance = (new \yii\db\Query())
            ->select(['l.name', 'COUNT(*) as total', 'AVG(s.duration_seconds) as avg_duration'])
            ->from('{{%services}} s')
            ->leftJoin('{{%loket}} l', 'l.id = s.loket_id')
            ->where(['between', 's.start_time', $startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->andWhere(['s.office_id' => $officeId, 's.status' => 'completed'])
            ->groupBy(['s.loket_id'])
            ->all();

        return [
            'success' => true,
            'data' => [
                'dailyCounts' => $dailyCounts,
                'serviceDistribution' => $serviceDistribution,
                'genderDistribution' => $genderDistribution,
                'peakHours' => $peakHours,
                'loketPerformance' => $loketPerformance,
            ],
        ];
    }

    /**
     * Export to Excel
     */
    public function actionExportExcel()
    {
        $request = Yii::$app->request;
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $officeId = Yii::$app->user->identity->office_id;

        $services = Service::find()
            ->joinWith(['queue', 'loket', 'serviceType', 'officer'])
            ->where(['between', 'services.start_time', $startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->andWhere(['services.office_id' => $officeId])
            ->orderBy(['services.start_time' => SORT_ASC])
            ->all();

        // Simple CSV export (works without PhpSpreadsheet if not installed)
        $filename = "laporan_antrian_{$startDate}_{$endDate}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // BOM for Excel UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            'No', 'Tanggal', 'No Antrian', 'Loket', 'Petugas',
            'Nama', 'Jenis Kelamin', 'No HP', 'NIK',
            'Jenis Layanan', 'Keperluan', 'Keterangan',
            'Waktu Mulai', 'Waktu Selesai', 'Durasi (menit)', 'Status'
        ]);

        $no = 1;
        foreach ($services as $s) {
            fputcsv($output, [
                $no++,
                $s->queue ? $s->queue->queue_date : '-',
                $s->queue ? $s->queue->queue_number : '-',
                $s->loket ? $s->loket->name : '-',
                $s->officer ? $s->officer->fullname : '-',
                $s->nama,
                $s->jenis_kelamin === 'L' ? 'Laki-laki' : ($s->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                $s->no_hp,
                $s->nik,
                $s->serviceType ? $s->serviceType->name : '-',
                $s->keperluan,
                $s->keterangan,
                $s->start_time,
                $s->end_time,
                $s->duration_seconds ? round($s->duration_seconds / 60, 1) : '-',
                $s->status,
            ]);
        }

        fclose($output);
        Yii::$app->end();
    }

    /**
     * Export to PDF
     */
    public function actionExportPdf()
    {
        $request = Yii::$app->request;
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $officeId = Yii::$app->user->identity->office_id;

        $services = Service::find()
            ->joinWith(['queue', 'loket', 'serviceType', 'officer'])
            ->where(['between', 'services.start_time', $startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->andWhere(['services.office_id' => $officeId])
            ->orderBy(['services.start_time' => SORT_ASC])
            ->all();

        // Build HTML for PDF
        $html = '<h2 style="text-align:center">DINAS SOSIAL KOTA SEMARANG</h2>';
        $html .= '<h3 style="text-align:center">Laporan Layanan Antrian</h3>';
        $html .= '<p style="text-align:center">Periode: ' . $startDate . ' s/d ' . $endDate . '</p>';
        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%;font-size:10px">';
        $html .= '<tr style="background:#0d6efd;color:#fff;font-weight:bold">';
        $html .= '<td>No</td><td>Tanggal</td><td>No Antrian</td><td>Loket</td><td>Nama</td><td>Jenis Layanan</td><td>Mulai</td><td>Selesai</td><td>Durasi</td>';
        $html .= '</tr>';

        $no = 1;
        foreach ($services as $s) {
            $html .= '<tr>';
            $html .= '<td>' . $no++ . '</td>';
            $html .= '<td>' . ($s->queue ? $s->queue->queue_date : '-') . '</td>';
            $html .= '<td>' . ($s->queue ? $s->queue->queue_number : '-') . '</td>';
            $html .= '<td>' . ($s->loket ? $s->loket->name : '-') . '</td>';
            $html .= '<td>' . ($s->nama ?: '-') . '</td>';
            $html .= '<td>' . ($s->serviceType ? $s->serviceType->name : '-') . '</td>';
            $html .= '<td>' . ($s->start_time ? date('H:i', strtotime($s->start_time)) : '-') . '</td>';
            $html .= '<td>' . ($s->end_time ? date('H:i', strtotime($s->end_time)) : '-') . '</td>';
            $html .= '<td>' . ($s->duration_seconds ? round($s->duration_seconds / 60, 1) . 'm' : '-') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '<p style="font-size:9px;margin-top:20px">Dicetak: ' . date('d/m/Y H:i:s') . '</p>';

        // Use TCPDF if available, otherwise render as HTML
        if (class_exists('\TCPDF')) {
            $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8');
            $pdf->SetCreator('Sistem Antrian Dinsos');
            $pdf->SetTitle('Laporan Antrian');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');

            $filename = "laporan_antrian_{$startDate}_{$endDate}.pdf";
            $pdf->Output($filename, 'D');
        } else {
            // Fallback: render as HTML print
            echo '<html><head><title>Laporan Antrian</title>';
            echo '<style>body{font-family:Arial;} table{border-collapse:collapse;} td,th{padding:4px;border:1px solid #333;}</style>';
            echo '</head><body>';
            echo $html;
            echo '<script>window.print();</script>';
            echo '</body></html>';
        }

        Yii::$app->end();
    }
}
