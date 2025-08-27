{{-- Template de notificação de pedido --}}
📦 **Atualização do Pedido #{{ $order_id }}**

Status: {{ $status }}
@if($tracking_code)
Código de rastreamento: {{ $tracking_code }}
@endif

**Itens do pedido:**
@foreach($items as $item)
- {{ $item['name'] }} ({{ $item['quantity'] }}x) - R$ {{ number_format($item['price'], 2, ',', '.') }}
@endforeach

**Total:** R$ {{ number_format($total, 2, ',', '.') }}

@if($estimated_delivery)
**Previsão de entrega:** {{ $estimated_delivery->format('d/m/Y') }}
@endif

@if($next_action)
**Próximos passos:** {{ $next_action }}
@endif

Acompanhe seu pedido em: {{ $tracking_url }}
