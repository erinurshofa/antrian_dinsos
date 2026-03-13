<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Service extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%services}}';
    }

    public function rules()
    {
        return [
            [['queue_id', 'office_id', 'loket_id', 'officer_id', 'start_time'], 'required'],
            [['queue_id', 'office_id', 'loket_id', 'officer_id', 'service_type_id', 'duration_seconds'], 'integer'],
            [['nama'], 'string', 'max' => 100],
            [['jenis_kelamin'], 'string', 'max' => 1],
            [['jenis_kelamin'], 'in', 'range' => ['L', 'P']],
            [['no_hp'], 'string', 'max' => 20],
            [['nik'], 'string', 'max' => 16],
            [['keperluan'], 'string', 'max' => 100],
            [['keterangan'], 'string'],
            [['start_time', 'end_time'], 'safe'],
            [['status'], 'in', 'range' => ['serving', 'completed', 'cancelled']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'nama' => 'Nama',
            'jenis_kelamin' => 'Jenis Kelamin',
            'no_hp' => 'No. HP',
            'nik' => 'NIK',
            'keperluan' => 'Keperluan Layanan',
            'keterangan' => 'Keterangan',
            'service_type_id' => 'Jenis Layanan',
            'start_time' => 'Waktu Mulai',
            'end_time' => 'Waktu Selesai',
            'duration_seconds' => 'Durasi (detik)',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
            }
            $this->updated_at = time();

            // Auto calculate duration
            if ($this->end_time && $this->start_time) {
                $start = strtotime($this->start_time);
                $end = strtotime($this->end_time);
                $this->duration_seconds = $end - $start;
            }

            return true;
        }
        return false;
    }

    // Relations
    public function getQueue()
    {
        return $this->hasOne(Queue::class, ['id' => 'queue_id']);
    }

    public function getLoket()
    {
        return $this->hasOne(Loket::class, ['id' => 'loket_id']);
    }

    public function getOfficer()
    {
        return $this->hasOne(User::class, ['id' => 'officer_id']);
    }

    public function getServiceType()
    {
        return $this->hasOne(ServiceType::class, ['id' => 'service_type_id']);
    }

    public function getOffice()
    {
        return $this->hasOne(Office::class, ['id' => 'office_id']);
    }

    /**
     * Format duration as human readable
     */
    public function getDurationFormatted()
    {
        if (!$this->duration_seconds) return '-';
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        return "{$minutes} menit {$seconds} detik";
    }

    public function getJenisKelaminLabel()
    {
        return $this->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    }
}
