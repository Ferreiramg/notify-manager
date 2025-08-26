# GitHub Repository Setup Guide

## Configuração dos Secrets

Para que os GitHub Actions funcionem corretamente, você precisa configurar os seguintes secrets no seu repositório:

### 1. Acesse as configurações do repositório
```
https://github.com/Ferreiramg/notify-manager/settings/secrets/actions
```

### 2. Adicione os seguintes secrets:

#### Para releases automáticas no Packagist (opcional):
- `PACKAGIST_USERNAME`: Seu nome de usuário do Packagist
- `PACKAGIST_TOKEN`: Seu API token do Packagist

**Como obter o token do Packagist:**
1. Acesse https://packagist.org/profile/
2. Vá em "API Token"
3. Gere um novo token
4. Copie e cole no secret `PACKAGIST_TOKEN`

## Configuração da Branch Principal

Certifique-se de que sua branch principal está configurada como `main`:

```bash
git branch -M main
git push -u origin main
```

## Badge Status

Os badges no README serão automaticamente atualizados quando os workflows rodarem.

## Comandos Úteis

### Para desenvolvimento local:
```bash
# Instalar dependências
composer install

# Executar testes
composer test

# Executar testes com cobertura
composer test-coverage

# Verificar estilo do código
composer format-check

# Corrigir estilo do código
composer format
```

### Para criar uma release:
```bash
git tag v1.0.0
git push origin v1.0.0
```

Isso irá disparar automaticamente o workflow de release.

## Estrutura dos Workflows

### 1. Tests (`tests.yml`)
- Executa em PHP 8.3 e 8.4
- Testa no Ubuntu e Windows
- Verifica cobertura de testes
- Verifica estilo do código

### 2. Security & Quality (`security.yml`)
- Executa verificações de segurança
- Analisa dependências desatualizadas
- Valida o composer.json

### 3. Release (`release.yml`)
- Disparado quando uma tag é criada
- Executa todos os testes
- Cria release no GitHub
- Atualiza o Packagist (se configurado)

## Próximos Passos

1. Configure os secrets necessários
2. Faça push do código para o repositório
3. Verifique se os workflows estão executando corretamente
4. Publique no Packagist: https://packagist.org/packages/submit
