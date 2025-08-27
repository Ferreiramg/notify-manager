<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuração de Filas para Notify Manager
    |--------------------------------------------------------------------------
    |
    | Exemplo de configuração das filas para o pacote Notify Manager.
    | Adicione estas configurações ao seu arquivo config/queue.php
    |
    */

    'connections' => [
        // Suas outras conexões...

        // Fila específica para notificações
        'notifications' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'notifications',
            'retry_after' => 90,
            'after_commit' => true,
        ],

        // Fila de alta prioridade para notificações críticas
        'notifications_critical' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'critical_notifications',
            'retry_after' => 60,
            'block_for' => null,
            'after_commit' => false,
        ],

        // Fila de baixa prioridade para notificações em lote
        'notifications_bulk' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'bulk_notifications',
            'retry_after' => 300,
            'after_commit' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Workers Sugeridos
    |--------------------------------------------------------------------------
    |
    | Comandos para rodar workers específicos para notificações:
    |
    | # Worker para notificações normais (3 processos)
    | php artisan queue:work notifications --sleep=3 --tries=3 --max-time=3600 --queue=notifications
    |
    | # Worker para notificações críticas (5 processos)
    | php artisan queue:work notifications_critical --sleep=1 --tries=5 --max-time=1800 --queue=critical_notifications
    |
    | # Worker para notificações em lote (1 processo)
    | php artisan queue:work notifications_bulk --sleep=10 --tries=2 --max-time=7200 --queue=bulk_notifications
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Configuração Horizon (se usando Redis)
    |--------------------------------------------------------------------------
    |
    | Adicione ao config/horizon.php:
    |
    | 'environments' => [
    |     'production' => [
    |         'notifications' => [
    |             'connection' => 'notifications_critical',
    |             'queue' => ['critical_notifications', 'notifications'],
    |             'balance' => 'auto',
    |             'processes' => 10,
    |             'tries' => 5,
    |             'nice' => 0,
    |         ],
    |         'bulk-notifications' => [
    |             'connection' => 'notifications_bulk',
    |             'queue' => ['bulk_notifications'],
    |             'balance' => 'simple',
    |             'processes' => 3,
    |             'tries' => 2,
    |             'nice' => 10,
    |         ],
    |     ],
    | ],
    |
    */
];
