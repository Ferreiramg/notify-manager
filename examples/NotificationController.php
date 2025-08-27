<?php

namespace App\Http\Controllers;

use Alexsandrov16\NotifyManager\DTOs\NotificationDTO;
use Alexsandrov16\NotifyManager\Facades\NotifyManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Enviar notificação de boas-vindas
     */
    public function sendWelcome(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'is_premium' => 'boolean',
        ]);

        $templateData = [
            'name' => $request->name,
            'app_name' => config('app.name'),
            'login_url' => route('login'),
            'support_email' => config('mail.support_address'),
            'has_premium' => $request->boolean('is_premium'),
            'premium_features' => $request->boolean('is_premium') ? [
                'Acesso ilimitado',
                'Suporte prioritário',
                'Relatórios avançados',
            ] : [],
        ];

        $notification = NotificationDTO::create(
            channel: 'email',
            recipient: $request->email,
            message: 'Mensagem será gerada pelo template',
            options: [
                'subject' => 'Bem-vindo ao '.config('app.name'),
                'template' => 'welcome',
                'template_data' => $templateData,
                'priority' => 2,
                'tags' => ['welcome', 'onboarding'],
            ]
        );

        if (NotifyManager::send($notification)) {
            return response()->json(['message' => 'Notificação enviada com sucesso']);
        }

        return response()->json(['error' => 'Falha ao enviar notificação'], 500);
    }

    /**
     * Atualizar status do pedido
     */
    public function orderUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'order_id' => 'required|string',
            'status' => 'required|string',
            'items' => 'required|array',
            'total' => 'required|numeric',
        ]);

        // Buscar preferências do usuário
        $user = \App\Models\User::findOrFail($request->user_id);

        $templateData = [
            'order_id' => $request->order_id,
            'status' => $request->status,
            'items' => $request->items,
            'total' => $request->total,
            'tracking_code' => $request->tracking_code,
            'estimated_delivery' => $request->estimated_delivery ?
                \Carbon\Carbon::parse($request->estimated_delivery) : null,
            'next_action' => $request->next_action,
            'tracking_url' => route('orders.track', $request->order_id),
        ];

        // Enviar por múltiplos canais baseado na preferência do usuário
        $channels = $user->notification_preferences ?? ['email'];

        foreach ($channels as $channel) {
            $notification = NotificationDTO::create(
                channel: $channel,
                recipient: $this->getRecipientForChannel($user, $channel),
                message: 'Mensagem será gerada pelo template',
                options: [
                    'subject' => "Pedido {$request->order_id} - {$request->status}",
                    'template' => 'order-update',
                    'template_data' => $templateData,
                    'priority' => 2,
                    'tags' => ['order', 'update', $request->status],
                    'metadata' => [
                        'user_id' => $user->id,
                        'order_id' => $request->order_id,
                    ],
                ]
            );

            // Enviar de forma assíncrona
            NotifyManager::sendAsync($notification);
        }

        return response()->json(['message' => 'Notificações enviadas']);
    }

    /**
     * Enviar alerta de sistema
     */
    public function systemAlert(Request $request): JsonResponse
    {
        $request->validate([
            'alert_type' => 'required|string',
            'severity' => 'required|in:low,medium,high,critical',
            'system' => 'required|string',
            'description' => 'required|string',
        ]);

        $templateData = [
            'alert_type' => $request->alert_type,
            'severity' => strtoupper($request->severity),
            'system' => $request->system,
            'description' => $request->description,
            'timestamp' => now(),
            'error_details' => $request->error_details,
            'affected_users' => $request->affected_users,
            'resolution_steps' => $request->resolution_steps ?? [],
            'incident_url' => $request->incident_url,
            'app_name' => config('app.name'),
        ];

        // Determinar canais baseado na severidade
        $channels = match ($request->severity) {
            'critical' => ['telegram', 'email', 'slack'],
            'high' => ['telegram', 'email'],
            'medium' => ['email'],
            'low' => ['email']
        };

        // Buscar lista de administradores
        $admins = \App\Models\User::role('admin')->get();

        foreach ($admins as $admin) {
            foreach ($channels as $channel) {
                $recipient = $this->getRecipientForChannel($admin, $channel);
                if (! $recipient) {
                    continue;
                }

                $notification = NotificationDTO::create(
                    channel: $channel,
                    recipient: $recipient,
                    message: 'Mensagem será gerada pelo template',
                    options: [
                        'subject' => "[{$request->severity}] {$request->alert_type}",
                        'template' => 'system-alert',
                        'template_data' => $templateData,
                        'priority' => $request->severity === 'critical' ? 3 : 2,
                        'tags' => ['alert', 'system', $request->severity],
                        'metadata' => [
                            'admin_id' => $admin->id,
                            'alert_type' => $request->alert_type,
                            'severity' => $request->severity,
                        ],
                    ]
                );

                // Crítico = imediato, outros = assíncrono
                if ($request->severity === 'critical') {
                    NotifyManager::send($notification);
                } else {
                    NotifyManager::sendAsync($notification);
                }
            }
        }

        return response()->json(['message' => 'Alertas enviados']);
    }

    /**
     * Agendar notificação
     */
    public function schedule(Request $request): JsonResponse
    {
        $request->validate([
            'channel' => 'required|string',
            'recipient' => 'required|string',
            'message' => 'required|string',
            'send_at' => 'required|date|after:now',
        ]);

        $notification = NotificationDTO::create(
            channel: $request->channel,
            recipient: $request->recipient,
            message: $request->message,
            options: $request->options ?? []
        );

        NotifyManager::sendAt(
            $notification,
            \Carbon\Carbon::parse($request->send_at)
        );

        return response()->json([
            'message' => 'Notificação agendada',
            'send_at' => $request->send_at,
        ]);
    }

    /**
     * Calcular custo de uma notificação
     */
    public function calculateCost(Request $request): JsonResponse
    {
        $request->validate([
            'channel' => 'required|string',
            'recipient' => 'required|string',
            'message' => 'required|string',
        ]);

        $notification = NotificationDTO::create(
            channel: $request->channel,
            recipient: $request->recipient,
            message: $request->message,
            options: $request->options ?? []
        );

        $cost = NotifyManager::calculateCost($notification);

        return response()->json([
            'cost' => $cost,
            'currency' => 'BRL',
            'formatted' => 'R$ '.number_format($cost, 4, ',', '.'),
        ]);
    }

    /**
     * Obter destinatário baseado no canal
     */
    private function getRecipientForChannel($user, string $channel): ?string
    {
        return match ($channel) {
            'email' => $user->email,
            'telegram' => $user->telegram_id,
            'slack' => $user->slack_user_id,
            'whatsapp' => $user->phone,
            default => null
        };
    }
}
