<?php
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $content */

$user = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;
$currentRoute = Yii::$app->controller->id . '/' . Yii::$app->controller->action->id;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Antrian Layanan - Dinas Sosial Kota Semarang">
    <title><?= Html::encode($this->title) ?> — Sistem Antrian Dinsos</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= Yii::getAlias('@web') ?>/css/app.css" rel="stylesheet">
    
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="app-sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">🏛️</div>
            <div class="sidebar-brand-text">
                DINSOS SEMARANG
                <small>Sistem Antrian Layanan</small>
            </div>
        </div>

        <ul class="sidebar-menu">
            <?php if ($user && ($user->isAdmin() || $user->isPimpinan())): ?>
            <li class="sidebar-menu-label">Dashboard</li>
            <li>
                <a href="<?= \yii\helpers\Url::to(['dashboard/index']) ?>" class="<?= $currentRoute === 'dashboard/index' ? 'active' : '' ?>">
                    <span class="menu-icon"><i class="bi bi-grid-1x2-fill"></i></span>
                    Dashboard
                </a>
            </li>
            <?php endif; ?>

            <?php if ($user && ($user->isAdmin() || $user->isPetugas())): ?>
            <li class="sidebar-menu-label">Pelayanan</li>
            <li>
                <a href="<?= \yii\helpers\Url::to(['officer/panel']) ?>" class="<?= $currentRoute === 'officer/panel' ? 'active' : '' ?>">
                    <span class="menu-icon"><i class="bi bi-person-workspace"></i></span>
                    Panel Petugas
                </a>
            </li>
            <li>
                <a href="<?= \yii\helpers\Url::to(['officer/history']) ?>" class="<?= $currentRoute === 'officer/history' ? 'active' : '' ?>">
                    <span class="menu-icon"><i class="bi bi-clock-history"></i></span>
                    Riwayat Layanan
                </a>
            </li>
            <?php endif; ?>

            <?php if ($user && ($user->isAdmin() || $user->isSatpam())): ?>
            <li class="sidebar-menu-label">Keamanan</li>
            <li>
                <a href="<?= \yii\helpers\Url::to(['security/panel']) ?>" class="<?= $currentRoute === 'security/panel' ? 'active' : '' ?>">
                    <span class="menu-icon"><i class="bi bi-shield-check"></i></span>
                    Panel Satpam
                </a>
            </li>
            <?php endif; ?>

            <li class="sidebar-menu-label">Layar Publik</li>
            <li>
                <a href="<?= \yii\helpers\Url::to(['queue/kiosk']) ?>" target="_blank">
                    <span class="menu-icon"><i class="bi bi-display"></i></span>
                    Kiosk Ambil Antrian
                </a>
            </li>
            <li>
                <a href="<?= \yii\helpers\Url::to(['queue/display']) ?>" target="_blank">
                    <span class="menu-icon"><i class="bi bi-tv"></i></span>
                    Display Antrian
                </a>
            </li>
        </ul>

        <?php if ($user): ?>
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($user->fullname, 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="name"><?= Html::encode($user->fullname) ?></div>
                <div class="role"><?= $user->getRoleName() ?><?= $user->loket ? ' · ' . $user->loket->name : '' ?></div>
            </div>
            <?= Html::beginForm(['site/logout'], 'post') ?>
                <button type="submit" class="btn btn-sm" style="color:var(--gray-light);padding:4px 8px" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            <?= Html::endForm() ?>
        </div>
        <?php endif; ?>
    </aside>

    <!-- Main -->
    <main class="app-main">
        <header class="app-header">
            <div class="page-title"><?= Html::encode($this->title) ?></div>
            <div class="header-actions">
                <span class="text-muted" style="font-size:13px">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('l, d F Y') ?>
                </span>
            </div>
        </header>

        <div class="app-content">
            <?php foreach (Yii::$app->session->getAllFlashes() as $type => $messages): ?>
                <?php foreach ((array)$messages as $message): ?>
                    <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <?= $content ?>
        </div>
    </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
