# NotifyManager

[![Tests](https://github.com/Ferreiramg/notify-manager/workflows/Tests/badge.svg)](https://github.com/Ferreiramg/notify-manager/actions)
[![Security](https://github.com/Ferreiramg/notify-manager/workflows/Security%20&%20Quality/badge.svg)](https://github.com/Ferreiramg/notify-manager/actions)
[![Latest Stable Version](https://poser.pugx.org/ferreiramg/notify-manager/v/stable)](https://packagist.org/packages/ferreiramg/notify-manager)
[![Total Downloads](https://poser.pugx.org/ferreiramg/notify-manager/downloads)](https://packagist.org/packages/ferreiramg/notify-manager)
[![License](https://poser.pugx.org/ferreiramg/notify-manager/license)](https://packagist.org/packages/ferreiramg/notify-manager)
![PHP Version](https://img.shields.io/badge/php-8.3+-blue.svg)
![Laravel Version](https://img.shields.io/badge/laravel-11.0+-red.svg)

> 📖 **[English Documentation](README-EN.md)** | **[Documentação em Português](README.md)**

Um poderoso pacote Laravel para gerenciar notificações com regras de envio, logs e recursos de monetização.

## Recursos

- 🚀 **Suporte Multi-Canal**: Envie notificações através de Email, Telegram, Slack, WhatsApp e canais personalizados
- 📋 **Envio Baseado em Regras**: Configure regras sofisticadas para controlar quando e como as notificações são enviadas
- 📊 **Log Abrangente**: Rastreie todas as atividades de notificação com logs detalhados
- 💰 **Monetização**: Rastreamento de custos integrado e monitoramento de uso para serviços de notificação
- ⚡ **PHP Moderno**: Construído com recursos do PHP 8.3+ incluindo classes readonly, enums e sintaxe moderna
- 🧪 **Bem Testado**: Suíte de testes abrangente usando Pest PHP
- 🎨 **Qualidade de Código**: Aplicado com estilo de código Laravel Pint

## Instalação

Instale o pacote via Composer:

```bash
composer require notify-manager/notify-manager
```

Execute o comando de instalação:

```bash
php artisan notify-manager:install
```

Isso irá:
- Publicar o arquivo de configuração
- Publicar as migrações do banco de dados
- Executar as migrações (opcional)

## Configuração

### Configuração Básica

O arquivo de configuração será publicado em `config/notify-manager.php`. Aqui você pode configurar:

```php
return [
    'default_channel' => 'email',
    
    'channels' => [
        'email' => \App\NotificationChannels\EmailChannel::class,
        'telegram' => \App\NotificationChannels\TelegramChannel::class,
    ],
    
    'monetization' => [
        'enabled' => true,
        'currency' => 'USD',
        'default_cost_per_message' => 0.01,
    ],
    
    'logging' => [
        'enabled' => true,
        'retention_days' => 90,
    ],
];
```

### Variáveis de Ambiente

Adicione estas ao seu arquivo `.env`:

```env
NOTIFY_MANAGER_DEFAULT_CHANNEL=email
NOTIFY_MANAGER_MONETIZATION_ENABLED=true
NOTIFY_MANAGER_LOGGING_ENABLED=true
NOTIFY_MANAGER_MAX_PER_HOUR=100
NOTIFY_MANAGER_MAX_PER_DAY=1000
```

## Uso

### Envio Básico de Notificação

```php
use NotifyManager\Facades\NotifyManager;
use NotifyManager\DTOs\NotificationDTO;

// Criar uma notificação
$notification = NotificationDTO::create(
    channel: 'email',
    recipient: 'user@example.com',
    message: 'Olá, esta é uma notificação de teste!',
    options: [
        'subject' => 'Notificação de Teste',
        'priority' => 1,
        'tags' => ['boas-vindas', 'onboarding-usuario']
    ]
);

// Enviar a notificação
$sent = NotifyManager::send($notification);

if ($sent) {
    echo "Notificação enviada com sucesso!";
} else {
    echo "Falha ao enviar notificação.";
}
```

### Criando Regras de Notificação

```php
use NotifyManager\DTOs\NotificationRuleDTO;

$rule = NotificationRuleDTO::create(
    name: 'Limite Email Boas-vindas',
    channel: 'email',
    conditions: [
        [
            'field' => 'tags',
            'operator' => 'contains',
            'value' => 'boas-vindas'
        ]
    ],
    options: [
        'max_sends_per_day' => 1,
        'allowed_hours' => [9, 10, 11, 14, 15, 16, 17],
        'is_active' => true
    ]
);

NotifyManager::createRule($rule);
```

### Implementando Canais Personalizados

Crie um canal de notificação personalizado implementando a `NotificationChannelInterface`:

```php
use NotifyManager\Contracts\NotificationChannelInterface;
use NotifyManager\DTOs\NotificationDTO;

class SlackChannel implements NotificationChannelInterface
{
    public function send(NotificationDTO $notification): bool
    {
        // Implementar integração com API do Slack
        $response = Http::post('https://hooks.slack.com/webhook-url', [
            'text' => $notification->message,
            'channel' => $notification->recipient,
        ]);
        
        return $response->successful();
    }

    public function getName(): string
    {
        return 'slack';
    }

    public function supports(NotificationDTO $notification): bool
    {
        return $notification->channel === 'slack';
    }

    public function getCostPerMessage(): float
    {
        return 0.001; // $0.001 por mensagem
    }

    public function validate(NotificationDTO $notification): bool
    {
        return !empty($notification->recipient) && 
               !empty($notification->message) &&
               str_starts_with($notification->recipient, '#');
    }
}
```

Registre seu canal personalizado no service provider:

```php
// No seu AppServiceProvider ou service provider personalizado
public function boot()
{
    NotifyManager::registerChannel('slack', new SlackChannel());
}
```

### Configuração Avançada de Regras

```php
$advancedRule = NotificationRuleDTO::create(
    name: 'Notificações Premium Usuário VIP',
    channel: 'telegram',
    conditions: [
        [
            'field' => 'recipient',
            'operator' => 'in',
            'value' => ['123456789', '987654321'] // IDs de chat de usuários VIP
        ],
        [
            'field' => 'priority',
            'operator' => '>=',
            'value' => 2
        ]
    ],
    options: [
        'max_sends_per_hour' => 10,
        'max_sends_per_day' => 50,
        'allowed_days' => [1, 2, 3, 4, 5], // Segunda a Sexta
        'allowed_hours' => range(9, 18), // 9h às 18h
        'start_date' => now(),
        'end_date' => now()->addMonths(6),
        'priority' => 1
    ]
);
```

## Esquema do Banco de Dados

O pacote cria três tabelas principais:

### notification_rules
Armazena regras de envio e condições para notificações.

### notification_logs
Rastreia todas as atividades de notificação com status e respostas.

### notification_usages
Registra dados de monetização para rastreamento de custos e faturamento.

## Testes

Execute a suíte de testes:

```bash
composer test
```

Execute testes com cobertura de código:

```bash
composer test-coverage
```

Gere relatório HTML de cobertura:

```bash
composer test-coverage-html
```

O relatório HTML será gerado no diretório `coverage/index.html`.

Formate código com Pint:

```bash
composer format
```

### Configuração do Xdebug

Para que a cobertura de código funcione, certifique-se de que o Xdebug está instalado e configurado. Os scripts do Composer já configuram automaticamente o modo de cobertura (`XDEBUG_MODE=coverage`).

Se você quiser configurar manualmente, adicione ao seu `php.ini`:

```ini
xdebug.mode=coverage
```

Ou use a variável de ambiente:

```bash
$env:XDEBUG_MODE="coverage" # Windows PowerShell
export XDEBUG_MODE=coverage # Linux/Mac
```

## Recursos de Monetização

Rastreie custos e uso de notificações:

```php
// Obter custo de uma notificação
$cost = NotifyManager::calculateCost($notification);

// Custos são automaticamente registrados quando notificações são enviadas
// Consulte dados de uso no modelo NotificationUsage
$usage = \NotifyManager\Models\NotificationUsage::where('channel', 'email')
    ->whereDate('used_at', today())
    ->sum('cost');
```

## Log e Monitoramento

Todas as atividades de notificação são automaticamente registradas:

```php
// Consultar logs de notificação
$logs = \NotifyManager\Models\NotificationLog::where('status', 'sent')
    ->where('channel', 'email')
    ->whereDate('sent_at', today())
    ->get();

// Verificar notificações falhadas
$failures = \NotifyManager\Models\NotificationLog::where('status', 'failed')
    ->with('usage')
    ->latest()
    ->get();
```

## Limitação de Taxa

Configure limites de taxa globalmente ou por regra:

```php
// Limitação de taxa global na configuração
'rate_limiting' => [
    'default_max_per_hour' => 100,
    'default_max_per_day' => 1000,
    'enable_global_limits' => true,
],

// Limitação de taxa por regra
$rule = NotificationRuleDTO::create(
    // ... outros parâmetros
    options: [
        'max_sends_per_hour' => 10,
        'max_sends_per_day' => 50,
    ]
);
```

## Integração com Filas

Habilite processamento em filas para melhor performance:

```php
// Em config/notify-manager.php
'queue' => [
    'enabled' => true,
    'connection' => 'redis',
    'queue_name' => 'notifications',
],
```

## Contribuindo

Damos as boas-vindas a contribuições! Por favor, veja [CONTRIBUTING.md](CONTRIBUTING.md) para detalhes.

## Segurança

Se você descobrir qualquer problema relacionado à segurança, por favor envie um email para security@notify-manager.com ao invés de usar o rastreador de issues.

## Licença

A Licença MIT (MIT). Por favor veja [Arquivo de Licença](LICENSE.md) para mais informações.

## Changelog

Por favor veja [CHANGELOG.md](CHANGELOG.md) para mais informações sobre o que mudou recentemente.

## Suporte

Para suporte, por favor crie uma issue no GitHub ou entre em contato conosco em luis@lpdeveloper.com.br
