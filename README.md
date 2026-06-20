# Controle de Portaria Escolar

Sistema simples em PHP, HTML, Bootstrap e MySQL/MariaDB para controle de entrada e saída de alunos com crachá QR Code.

## Domínio provisório

`https://scp.lojadaesquina.store`

## Recursos principais

- Cadastro rápido pela portaria
- Foto da criança e do responsável pelo celular
- Vínculo entre aluno e responsável
- Turma ou professora responsável
- Geração de crachá com QR Code
- Leitura do QR Code pela portaria
- Registro de entrada e saída
- Portal do responsável
- Modo emergência público em `/c/{token}`

## Modo emergência

O QR Code do crachá aponta para `https://scp.lojadaesquina.store/c/{token}`.

Quando aberto por agente de portaria logado, redireciona para o registro de entrada e saída.

Quando aberto por responsável logado e autorizado, redireciona para o histórico do aluno.

Quando aberto por qualquer pessoa sem login, exibe uma página pública de emergência que não revela dados pessoais da criança ou dos responsáveis.
