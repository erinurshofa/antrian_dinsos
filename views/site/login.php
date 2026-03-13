<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\helpers\Html;

$this->title = 'Login';
?>

<div class="login-card fade-in-up">
    <div class="login-header">
        <div class="login-logo">🏛️</div>
        <h2>Sistem Antrian</h2>
        <p>Dinas Sosial Kota Semarang</p>
    </div>

    <?php $form = \yii\widgets\ActiveForm::begin([
        'id' => 'login-form',
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'errorOptions' => ['class' => 'invalid-feedback d-block', 'style' => 'font-size:12px'],
            'labelOptions' => ['class' => 'form-label'],
        ],
    ]); ?>

    <div class="form-group mb-3">
        <?= $form->field($model, 'username')->textInput([
            'class' => 'form-control',
            'placeholder' => 'Masukkan username',
            'autofocus' => true,
        ]) ?>
    </div>

    <div class="form-group mb-3">
        <?= $form->field($model, 'password')->passwordInput([
            'class' => 'form-control',
            'placeholder' => 'Masukkan password',
        ]) ?>
    </div>

    <div class="form-group mb-3">
        <?= $form->field($model, 'rememberMe')->checkbox([
            'template' => '<div class="form-check">{input}{label}</div>',
            'class' => 'form-check-input',
            'labelOptions' => ['class' => 'form-check-label', 'style' => 'font-size:13px'],
        ]) ?>
    </div>

    <?= Html::submitButton('<i class="bi bi-box-arrow-in-right me-2"></i> Masuk', [
        'class' => 'btn btn-primary btn-login',
        'name' => 'login-button',
    ]) ?>

    <?php \yii\widgets\ActiveForm::end(); ?>

    <div class="text-center mt-4">
        <small class="text-muted">© <?= date('Y') ?> Dinas Sosial Kota Semarang</small>
    </div>
</div>
