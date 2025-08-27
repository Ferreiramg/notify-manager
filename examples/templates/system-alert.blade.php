{{-- Template de alerta de sistema --}}
🚨 **ALERTA: {{ $alert_type }}**

**Severidade:** {{ $severity }}
**Sistema:** {{ $system }}
**Timestamp:** {{ $timestamp->format('d/m/Y H:i:s') }}

**Descrição:**
{{ $description }}

@if($error_details)
**Detalhes do erro:**
```
{{ $error_details }}
```
@endif

@if($affected_users)
**Usuários afetados:** {{ $affected_users }}
@endif

@if($resolution_steps)
**Passos para resolução:**
@foreach($resolution_steps as $step)
{{ $loop->iteration }}. {{ $step }}
@endforeach
@endif

@if($incident_url)
**Acompanhe em:** {{ $incident_url }}
@endif

---
Sistema de Monitoramento {{ $app_name }}
