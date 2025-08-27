{{-- Template de boas-vindas --}}
Olá {{ $name }}!

Bem-vindo ao {{ $app_name }}. Estamos muito felizes em tê-lo conosco.

@if($has_premium)
Como membro premium, você tem acesso a recursos exclusivos:
@foreach($premium_features as $feature)
- {{ $feature }}
@endforeach
@endif

Para começar, acesse: {{ $login_url }}

@if($support_email)
Se precisar de ajuda, entre em contato: {{ $support_email }}
@endif

Atenciosamente,
Equipe {{ $app_name }}
