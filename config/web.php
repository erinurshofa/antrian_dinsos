<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'antrian-dinsos',
    'name' => 'Sistem Antrian - Dinas Sosial',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => 'id-ID',
    'timeZone' => 'Asia/Jakarta',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'antrian-dinsos-secret-key-change-in-production-2024',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'login' => 'site/login',
                'logout' => 'site/logout',
                'kiosk' => 'queue/kiosk',
                'display' => 'queue/display',
                'api/queue/stream' => 'queue/stream',
                'api/queue/status' => 'queue/status',
                'api/queue/take' => 'queue/take',
                'api/officer/call' => 'officer/call',
                'api/officer/complete' => 'officer/complete',
                'api/dashboard/stats' => 'dashboard/stats',
            ],
        ],
        'formatter' => [
            'dateFormat' => 'dd MMMM yyyy',
            'datetimeFormat' => 'dd MMMM yyyy HH:mm:ss',
            'timeFormat' => 'HH:mm:ss',
            'locale' => 'id-ID',
            'defaultTimeZone' => 'Asia/Jakarta',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['127.0.0.1', '::1'],
    ];
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
