# SCP Escolar

MVP em PHP 8 + MySQL para controle seguro de entrada e saída de alunos.

## Instalação

1. Copie `config/config.example.php` para `config/config.php` e informe o banco.
2. Importe `database/schema.sql` no MySQL/MariaDB.
3. Crie o primeiro acesso: `php scripts/create_admin.php "Administrador" admin@escola.com "senha-segura"`.
4. Aponte o document root para `public/` ou publique com `deploy.ps1`.

## Deploy

Copie `.env.example` para `.env`, preencha as credenciais SSH e execute:

```powershell
.\deploy.ps1
```

O script cria commit, envia ao `origin` quando configurado e publica com Plink. Os arquivos `.env` e `config/config.php` são ignorados pelo Git.

## Fluxo rápido da portaria

O perfil de portaria pode abrir `portaria/cadastro.php`, fotografar o aluno pelo celular, preencher nome e turma e gerar imediatamente um crachá em PNG. Na tela do crachá, o botão de compartilhamento usa o menu nativo do celular para envio pelo WhatsApp; quando o navegador não oferece compartilhamento de arquivos, a imagem é baixada e o WhatsApp é aberto como alternativa.

O fluxo recomendado fica em `portaria/convites.php`: o agente informa somente o WhatsApp do responsável e gera um convite em QR Code. O responsável abre o convite no próprio celular, fotografa a si e à criança, cria sua senha e envia os dados. A portaria recebe a pendência, confere as fotos, aprova e envia pelo WhatsApp o link do crachá digital, que também pode ser impresso.

Em instalações existentes, execute uma vez `database/migrations/20260622_convites_cadastro.sql` antes de usar os convites.
