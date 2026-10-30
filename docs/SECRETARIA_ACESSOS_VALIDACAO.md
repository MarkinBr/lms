# Aceitação da Gestão de Acessos — Issue #126

Base: `4403d169038c4c9afb19e7bce8e887574431dbd4` (`feat/120-telegram-administracao`, PR #121). Branch da função: `feat/126-secretaria-gestao-acessos`.

## Verificado em 30/10/2026

- Aceitação final após os ajustes: **183 testes, 1.052 assertions, zero falhas/erros**. Classes: GestãoCadastro, Central HTTP, Entregas, AtendimentoSeguro HTTP, Credenciais, Inscrição complementar, AprovarInscricao e EmailAluno. PHPUnit registra 52 depreciações de metadados já existentes.
- Subconjunto de autenticação/portal/SSO/fotos: 153 testes/1.055 assertions, aprovados. Revisão independente conferiu revogação no Filament, restauração por remember e recuperação convencional.
- Inscrição/API/acompanhamento e aprovação: testes preservam dados originais, suportam múltiplos cursos, não sobrescrevem usuário existente e não inventam consentimento histórico.
- Pint passou nos PHP alterados. Biome verificou/formatou os novos CSS/JS, sem erros e com 29 avisos de estilo CSS; sintaxe dos dois JavaScripts alterados passou. Build Vite passou, sem alteração do package-lock.
- Aplicação integrada em `127.0.0.1:8897`, banco SQLite exclusivo e dados fictícios: login Filament → Central → lista → ficha → edição → revisão → confirmação → histórico. Tela de 390 px sem rolagem horizontal; viewport desktop conferido. Nenhuma mensagem real enviada.
- Revisão independente sem novos P1/P2 após correções. Casos de uso único, cinco erros, expiração, reemissão, snapshot obsoleto, ator/alvo sem autorização, duplicação de confirmação e entrega obsoleta têm cobertura.

## Limites da evidência

A suíte geral também foi executada durante a integração: 766 testes, 9 erros e 12 falhas. Dezenove ocorrências pertencem a `PatchValidationTest`, que presume schema previamente instalado e utiliza consultas PostgreSQL em um SQLite vazio; esse teste e os arquivos financeiros envolvidos não foram alterados. Duas ocorrências de autenticação/assinatura surgidas durante as alterações foram reexecutadas com sucesso na aceitação final acima. Não se declara a suíte geral totalmente verde.

O runner `tests/infra/secretaria_acessos_postgres.php` exige banco PostgreSQL descartável `cetsadp_telegram_ci`, verifica dois processos bloqueados concorrendo por código, snapshot e reserva de e-mail, e recusa produção antes do bootstrap. Está integrado ao CI; não foi executado em um PostgreSQL local, indisponível nesta máquina. O resultado real deve ser conferido no PR antes da implantação.

Não houve implantação, migração de banco real, merge ou envio a destinatários reais nesta etapa. Fotos, conteúdo cifrado e auditoria exigem storage persistente e preservação da APP_KEY. O piloto mantém rotinas financeiras gerais desligadas; a limpeza de entregas deve ser incorporada separadamente na implantação, conforme o guia operacional.
