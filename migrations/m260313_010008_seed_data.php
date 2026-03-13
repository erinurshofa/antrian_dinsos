<?php

use yii\db\Migration;

class m260313_010008_seed_data extends Migration
{
    public function safeUp()
    {
        $now = time();

        // Seed default office
        $this->insert('{{%offices}}', [
            'code' => 'DINSOS-SMG',
            'name' => 'Dinas Sosial Kota Semarang',
            'address' => 'Jl. Pemuda No. 175, Semarang, Jawa Tengah',
            'province' => 'Jawa Tengah',
            'city' => 'Kota Semarang',
            'phone' => '(024) 3547691',
            'email' => 'dinsos@semarangkota.go.id',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Seed queue types
        $this->batchInsert('{{%queue_types}}', ['code', 'name', 'prefix', 'description', 'color', 'is_active', 'created_at', 'updated_at'], [
            ['UMUM', 'Antrian Umum', 'U', 'Antrian untuk layanan umum', '#0d6efd', 1, $now, $now],
            ['RENTAN', 'Antrian Rentan', 'R', 'Antrian prioritas untuk lansia, disabilitas, ibu hamil, dan kondisi khusus', '#dc3545', 1, $now, $now],
        ]);

        // Seed service types
        $this->batchInsert('{{%service_types}}', ['code', 'name', 'description', 'is_active', 'created_at', 'updated_at'], [
            ['PBI', 'Rekomendasi PBI', 'Penerima Bantuan Iuran', 1, $now, $now],
            ['UHC', 'Rekomendasi UHC', 'Universal Health Coverage', 1, $now, $now],
            ['TANDA_DAFTAR', 'Rekomendasi Tanda Daftar', 'Tanda daftar yayasan/organisasi sosial', 1, $now, $now],
            ['BANSOS', 'Bantuan Sosial', 'Informasi dan layanan bantuan sosial', 1, $now, $now],
            ['KONSULTASI', 'Konsultasi', 'Konsultasi layanan sosial', 1, $now, $now],
            ['LAINNYA', 'Lainnya', 'Layanan lainnya', 1, $now, $now],
        ]);

        // Seed lokets
        $this->batchInsert('{{%loket}}', ['office_id', 'name', 'code', 'queue_type_id', 'is_active', 'created_at', 'updated_at'], [
            [1, 'Loket 1', 'L1', 1, 1, $now, $now],
            [1, 'Loket 2', 'L2', 2, 1, $now, $now],
            [1, 'Loket 3', 'L3', 1, 1, $now, $now],
            [1, 'Loket 4', 'L4', null, 1, $now, $now],
        ]);

        // Seed admin user (password: admin123)
        $this->insert('{{%users}}', [
            'office_id' => 1,
            'username' => 'admin',
            'fullname' => 'Administrator',
            'email' => 'admin@dinsos-semarang.go.id',
            'auth_key' => Yii::$app->security->generateRandomString(),
            'password_hash' => Yii::$app->security->generatePasswordHash('admin123'),
            'role' => 'admin',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Seed satpam user (password: satpam123)
        $this->insert('{{%users}}', [
            'office_id' => 1,
            'username' => 'satpam',
            'fullname' => 'Petugas Keamanan',
            'email' => 'satpam@dinsos-semarang.go.id',
            'auth_key' => Yii::$app->security->generateRandomString(),
            'password_hash' => Yii::$app->security->generatePasswordHash('satpam123'),
            'role' => 'satpam',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Seed petugas loket 1 (password: petugas123)
        $this->insert('{{%users}}', [
            'office_id' => 1,
            'username' => 'petugas1',
            'fullname' => 'Petugas Loket 1',
            'email' => 'petugas1@dinsos-semarang.go.id',
            'auth_key' => Yii::$app->security->generateRandomString(),
            'password_hash' => Yii::$app->security->generatePasswordHash('petugas123'),
            'role' => 'petugas',
            'loket_id' => 1,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Seed petugas loket 2 (password: petugas123)
        $this->insert('{{%users}}', [
            'office_id' => 1,
            'username' => 'petugas2',
            'fullname' => 'Petugas Loket 2',
            'email' => 'petugas2@dinsos-semarang.go.id',
            'auth_key' => Yii::$app->security->generateRandomString(),
            'password_hash' => Yii::$app->security->generatePasswordHash('petugas123'),
            'role' => 'petugas',
            'loket_id' => 2,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Seed pimpinan (password: pimpinan123)
        $this->insert('{{%users}}', [
            'office_id' => 1,
            'username' => 'pimpinan',
            'fullname' => 'Kepala Dinas Sosial',
            'email' => 'kadis@dinsos-semarang.go.id',
            'auth_key' => Yii::$app->security->generateRandomString(),
            'password_hash' => Yii::$app->security->generatePasswordHash('pimpinan123'),
            'role' => 'pimpinan',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%users}}');
        $this->delete('{{%loket}}');
        $this->delete('{{%service_types}}');
        $this->delete('{{%queue_types}}');
        $this->delete('{{%offices}}');
    }
}
