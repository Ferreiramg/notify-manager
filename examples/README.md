# Exemplos de Uso do Notify Manager

Este diretório contém exemplos práticos de como usar o pacote Notify Manager em aplicações Laravel.

## 📁 Arquivos Incluídos

### Templates
- `templates/welcome.blade.php` - Template de boas-vindas com dados dinâmicos
- `templates/order-update.blade.php` - Template para atualizações de pedido
- `templates/system-alert.blade.php` - Template para alertas de sistema

### Controllers
- `NotificationController.php` - Controller completo com exemplos de uso
- `queue-config.php` - Configurações de fila para notificações

## 🚀 Como Usar

### 1. Templates

Copie os templates para `resources/views/notifications/`:

```bash
mkdir -p resources/views/notifications
cp examples/templates/* resources/views/notifications/
```

### 2. Controller

Use o `NotificationController.php` como base para seus próprios controllers:

```php
// Enviar boas-vindas
POST /notifications/welcome
{
    "email": "user@example.com",
    "name": "João Silva",
    "is_premium": true
}

// Atualizar pedido
POST /notifications/order-update
{
    "user_id": 1,
    "order_id": "ORDER-123",
    "status": "enviado",
    "items": [{"name": "Produto A", "quantity": 2, "price": 49.90}],
    "total": 99.80
}

// Enviar alerta de sistema
POST /notifications/system-alert
{
    "alert_type": "Database Connection Error",
    "severity": "critical",
    "system": "API Gateway",
    "description": "Falha na conexão com o banco principal"
}
```

### 3. Configuração de Filas

Adicione as configurações do `queue-config.php` ao seu `config/queue.php`:

```php
// Copie as seções relevantes para seu arquivo de configuração
```

## 💡 Dicas de Implementação

### Templates Seguros
- Always escape user data: `{{ $user_input }}`
- Use `{!! $trusted_html !!}` apenas para HTML confiável
- Validate template data before rendering

### Performance
- Use cache para templates frequentemente usados
- Configure workers dedicados para notificações
- Monitore uso de memória em templates grandes

### Monitoramento
- Log todas as notificações críticas
- Configure alertas para falhas de envio
- Monitore custos por canal

### Segurança
- Valide todos os dados de entrada
- Use rate limiting para APIs públicas
- Criptografe dados sensíveis nos templates

## 📊 Métricas Sugeridas

### Dashboard
```php
// Notificações por dia
$dailyStats = NotificationLog::selectRaw('DATE(created_at) as date, COUNT(*) as count')
    ->where('created_at', '>=', now()->subDays(30))
    ->groupBy('date')
    ->get();

// Custo por canal
$costByChannel = NotificationUsage::selectRaw('channel, SUM(cost) as total_cost')
    ->whereBetween('used_at', [now()->startOfMonth(), now()])
    ->groupBy('channel')
    ->get();

// Taxa de sucesso
$successRate = NotificationLog::selectRaw('
    channel,
    COUNT(*) as total,
    SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as successful,
    (SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) / COUNT(*)) * 100 as success_rate
')
    ->groupBy('channel')
    ->get();
```
