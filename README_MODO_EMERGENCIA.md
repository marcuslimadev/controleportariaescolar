# Modo Emergência do QR Code

O QR Code do crachá aponta para `https://scp.lojadaesquina.store/c/{token}`.

Quando o link é aberto por um agente de portaria logado, o sistema redireciona para a tela de entrada e saída do aluno.

Quando o link é aberto por um responsável logado e vinculado ao aluno, o sistema redireciona para o histórico autorizado.

Quando o link é aberto por qualquer pessoa sem login, o sistema exibe uma página pública de emergência, mostrando apenas o nome da escola e botões para alertar a escola. A página não mostra nome completo da criança, CPF, telefone dos responsáveis, endereço, turma, professora ou histórico de movimentações.

Configure estes valores no ambiente ou em `config/database.php`:

```php
define('APP_BASE_URL', getenv('APP_BASE_URL') ?: 'https://scp.lojadaesquina.store');
define('SCHOOL_NAME', getenv('SCHOOL_NAME') ?: 'Escola cadastrada');
define('SCHOOL_PHONE', getenv('SCHOOL_PHONE') ?: '');
define('SCHOOL_WHATSAPP', getenv('SCHOOL_WHATSAPP') ?: '');
```

A tabela `alertas_cracha` registra os avisos enviados pela página pública.
