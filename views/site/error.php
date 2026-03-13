<?php
use yii\helpers\Html;

$this->title = 'Error';
?>

<div class="text-center py-5">
    <h1 class="display-4 fw-bold text-danger"><?= Html::encode($name) ?></h1>
    <p class="lead text-muted mt-3"><?= nl2br(Html::encode($message)) ?></p>
    <a href="<?= \yii\helpers\Url::home() ?>" class="btn btn-primary mt-4">
        <i class="bi bi-house-door me-2"></i> Kembali ke Beranda
    </a>
</div>
