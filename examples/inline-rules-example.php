<?php

declare(strict_types=1);

use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\Models\NotificationRule;
use NotifyManager\Services\NotificationManager;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Exemplo demonstrando como usar regras inline no NotificationDTO
 *
 * Este exemplo mostra como passar regras diretamente no NotificationDTO
 * em vez de depender apenas das regras armazenadas no banco de dados.
 */

// Inicializar o NotificationManager
$manager = new NotificationManager;

// Exemplo 1: Regra inline para validar prioridade mínima
echo "=== Exemplo 1: Regra de Prioridade Mínima ===\n";

$priorityRule = new NotificationRule([
    'name' => 'Priority Check',
    'channel' => 'email',
    'conditions' => [
        [
            'field' => 'priority',
            'operator' => '>=',
            'value' => 2,
        ],
    ],
    'is_active' => true,
    'max_sends_per_day' => 0,
    'max_sends_per_hour' => 0,
    'allowed_days' => [],
    'allowed_hours' => [],
    'priority' => 1,
    'metadata' => [],
]);

// Notificação com prioridade baixa (será bloqueada)
$lowPriorityNotification = NotificationDTO::create(
    channel: 'email',
    recipient: 'user@example.com',
    message: 'Mensagem de baixa prioridade',
    options: [
        'priority' => 1, // Menor que 2, será bloqueada
        'rules' => [$priorityRule],
    ]
);

$shouldSend = $manager->shouldSend($lowPriorityNotification);
echo 'Notificação de baixa prioridade (priority=1): '.($shouldSend ? 'PERMITIDA' : 'BLOQUEADA')."\n";

// Notificação com prioridade alta (será permitida)
$highPriorityNotification = NotificationDTO::create(
    channel: 'email',
    recipient: 'user@example.com',
    message: 'Mensagem de alta prioridade',
    options: [
        'priority' => 3, // Maior que 2, será permitida
        'rules' => [$priorityRule],
    ]
);

$shouldSend = $manager->shouldSend($highPriorityNotification);
echo 'Notificação de alta prioridade (priority=3): '.($shouldSend ? 'PERMITIDA' : 'BLOQUEADA')."\n\n";

// Exemplo 2: Múltiplas regras inline
echo "=== Exemplo 2: Múltiplas Regras ===\n";

$recipientRule = new NotificationRule([
    'name' => 'Recipient Check',
    'channel' => 'email',
    'conditions' => [
        [
            'field' => 'recipient',
            'operator' => 'contains',
            'value' => '@company.com',
        ],
    ],
    'is_active' => true,
    'max_sends_per_day' => 0,
    'max_sends_per_hour' => 0,
    'allowed_days' => [],
    'allowed_hours' => [],
    'priority' => 1,
    'metadata' => [],
]);

$messageRule = new NotificationRule([
    'name' => 'Message Length Check',
    'channel' => 'email',
    'conditions' => [
        [
            'field' => 'message',
            'operator' => 'not_contains',
            'value' => 'spam',
        ],
    ],
    'is_active' => true,
    'max_sends_per_day' => 0,
    'max_sends_per_hour' => 0,
    'allowed_days' => [],
    'allowed_hours' => [],
    'priority' => 1,
    'metadata' => [],
]);

// Notificação que passa em todas as regras
$validNotification = NotificationDTO::create(
    channel: 'email',
    recipient: 'employee@company.com',
    message: 'Mensagem válida para o funcionário',
    options: [
        'priority' => 2,
        'rules' => [$priorityRule, $recipientRule, $messageRule],
    ]
);

$shouldSend = $manager->shouldSend($validNotification);
echo 'Notificação válida: '.($shouldSend ? 'PERMITIDA' : 'BLOQUEADA')."\n";

// Notificação que falha em uma das regras
$invalidNotification = NotificationDTO::create(
    channel: 'email',
    recipient: 'external@external.com', // Não contém @company.com
    message: 'Mensagem válida',
    options: [
        'priority' => 2,
        'rules' => [$priorityRule, $recipientRule, $messageRule],
    ]
);

$shouldSend = $manager->shouldSend($invalidNotification);
echo 'Notificação com recipient externo: '.($shouldSend ? 'PERMITIDA' : 'BLOQUEADA')."\n\n";

// Exemplo 3: Regras com restrições de horário
echo "=== Exemplo 3: Regras de Horário ===\n";

$timeRestrictedRule = new NotificationRule([
    'name' => 'Business Hours Only',
    'channel' => 'email',
    'conditions' => [],
    'is_active' => true,
    'max_sends_per_day' => 0,
    'max_sends_per_hour' => 0,
    'allowed_days' => [1, 2, 3, 4, 5], // Segunda a Sexta
    'allowed_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17], // 9h às 17h
    'priority' => 1,
    'metadata' => [],
]);

$businessHoursNotification = NotificationDTO::create(
    channel: 'email',
    recipient: 'user@example.com',
    message: 'Mensagem durante horário comercial',
    options: [
        'rules' => [$timeRestrictedRule],
    ]
);

$shouldSend = $manager->shouldSend($businessHoursNotification);
$currentHour = date('H');
$currentDay = date('N'); // 1 = Monday, 7 = Sunday

echo "Horário atual: {$currentHour}h, Dia da semana: {$currentDay}\n";
echo 'Notificação em horário comercial: '.($shouldSend ? 'PERMITIDA' : 'BLOQUEADA')."\n\n";

// Exemplo 4: Regras com limites de envio
echo "=== Exemplo 4: Limite de Envios por Hora ===\n";

$hourlyLimitRule = new NotificationRule([
    'name' => 'Hourly Limit Rule',
    'channel' => 'email',
    'conditions' => [],
    'is_active' => true,
    'max_sends_per_day' => 0,
    'max_sends_per_hour' => 5, // Máximo 5 por hora
    'allowed_days' => [],
    'allowed_hours' => [],
    'priority' => 1,
    'metadata' => [],
]);

$limitedNotification = NotificationDTO::create(
    channel: 'email',
    recipient: 'user@example.com',
    message: 'Mensagem com limite de envio',
    options: [
        'rules' => [$hourlyLimitRule],
    ]
);

$shouldSend = $manager->shouldSend($limitedNotification);
echo 'Notificação com limite por hora: '.($shouldSend ? 'PERMITIDA' : 'BLOQUEADA')."\n";
echo "Nota: Para testar o limite, seria necessário ter registros de envio na última hora.\n\n";

// Exemplo 5: Conversão para array incluindo regras
echo "=== Exemplo 5: Conversão para Array ===\n";

$notificationWithRules = NotificationDTO::create(
    channel: 'email',
    recipient: 'user@example.com',
    message: 'Mensagem com regras',
    options: [
        'priority' => 2,
        'subject' => 'Assunto da mensagem',
        'rules' => [$priorityRule],
        'metadata' => ['source' => 'example'],
    ]
);

$arrayData = $notificationWithRules->toArray();
echo "Dados da notificação em array:\n";
echo '- ID: '.$arrayData['id']."\n";
echo '- Canal: '.$arrayData['channel']."\n";
echo '- Destinatário: '.$arrayData['recipient']."\n";
echo '- Prioridade: '.$arrayData['priority']."\n";
echo '- Número de regras: '.count($arrayData['rules'])."\n";
echo '- Metadados: '.json_encode($arrayData['metadata'])."\n";

echo "\n=== Exemplo Completo ===\n";
echo "As regras inline permitem maior flexibilidade no controle de envios,\n";
echo "permitindo que cada notificação tenha suas próprias regras específicas\n";
echo "sem depender apenas das regras globais do banco de dados.\n";
