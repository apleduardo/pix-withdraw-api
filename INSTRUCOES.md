# Instruções do Projeto

Adicione aqui as principais instruções, diretrizes e informações importantes para o desenvolvimento, execução e manutenção deste projeto.

## Visão geral do projeto
 
 - Docker (https://docker.com)
 - PHP Hyperf 3 (https://hyperf.wiki)
 - Mysql 8
 - Mailhog (ou equivalent
 - PHPUnit para teste

## Estrutura do projeto

 - Use a configuração padrão do framework hyperf
 - Organize o codigo da aplicação usando namespace
 - Use a DI do framework hyperf.
 - Utilize o docker compose para compor os serviços utilizados.
 Performance.
○ Observabilidade.
○ Compatibilidade com escalabilidade horizontal.
○ Segurança.

- Tabelas do banco de dados
`account
    ○ id: string (uuid)
    ○ name: string
    ○ balance: decimal
● account_withdraw
    ○ id: string (uuid)
    ○ account_id: string (uuid)
    ○ method: string
    ○ amount: decimal
    ○ scheduled: boolean
    ○ scheduled_for: datetime
    ○ done: boolean
    ○ error: boolean
    ○ error_reason: string
● account_withdraw_pix
    ○ account_withdraw_id: string (uuid)
    ○ type: string
    ○ key: string`
 

 ## Configuração

 - Use variáveis de ambiente para configuração relacionada à infraestrutura
- Use arquivos `.env` para definir valores específicos do ambiente
- Não use variáveis de ambiente para controlar o comportamento da aplicação

## Fluxo Realizar saque

`` POST /account/{accountId}/balance/withdraw
Body: {
"method": "PIX",
"pix": {
"type": "email",
"key": "fulano@email.com"
},
"amount": 150.75,
// Define o agendamento do saque
// (null informa que o saque deve ocorrer imediatamente)
"schedule": null | "2026-01-01 15:00``

# Regras de negocio
- Não permitir valores negativos para o valor do saque.
- Não permitir saques com chave PIX que não seja do tipo email.
- O sistema deve ser preparado para aceitar outros tipos de chave PIX no futuro (sugestão: padrão Strategy).
- A operação do saque deve ser registrada no banco de dados, usando as tabelas account_withdraw e account_withdraw_pix.
- O saque sem agendamento deve realizar o saque de imediato.
- O saque com agendamento deve ser processado somente via cron (mais detalhes abaixo).
- O saque deve deduzir o saldo da conta na tabela account.
- Atualmente só existe a opção de saque via PIX, podendo ser somente para chaves do tipo email. A implementação deve possibilitar uma fácil expansão de outras formas de saque no futuro.
- Não é permitido sacar um valor maior do que o disponível no saldo da conta digital.
- O saldo da conta não pode ficar negativo.
- Para saque agendado, não é permitido agendar para um momento no passado.
- Após realizar o saque, deve ser enviado um email para o email do PIX, informando que o saque foi efetuado. O envio do email deve ser feito de forma assíncrona. O template do email é irrelevante, a única exigência é conter a data e hora do saque, o valor sacado e os dados do pix informado.
- Utilize um serviço de teste de email, Mailhog.
- Não haverá limite de valor por operação, por enquanto.
- O fluxo de autenticação será feito via token simples no request.

## Fluxo Processar saque agendado
- Uma cron irá verificar se há saques agendados pendentes e fará o processamento do saque diariamente.
Caso no momento do saque for identificado que não há saldo suficiente, deve ser registrado no banco de dados que o saque foi processado, mas com falha de saldo insuficiente.

## Orientações Gerais
- Preferir clareza sobre a abstração
- Siga as convenções do Laravel antes de introduzir padrões personalizados
- Mantenha a configuração explícita e legível
- Evite otimização prematura
- Utilize padrões de Clean Code, evite comentarios, deixe o codigo falar por si sõ.
- Evite alta coesão.
- Trate excessões.
- Adicione Logs em todos os fluxos para debug, info e possiveis erros.
-  Faça validação das entradas usando as boas praticas do hyperf.

