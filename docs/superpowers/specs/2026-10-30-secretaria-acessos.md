# Gestão de Acessos dos Alunos — especificação aprovada

Issue: https://github.com/MarkinBr/lms/issues/126

A Central da Secretaria atende exclusivamente contas de alunos. Somente administradores e secretários ativos podem utilizá-la. O usuário aprovou a prévia visual e funcional em 30/10/2026.

## Interface e cadastro

- Entrada em `/secretaria/central`, integrada ao painel Filament existente; identidade CETSADP em azul-marinho e dourado, cartões de módulos e layout responsivo.
- Busca por nome, matrícula de acesso, CPF e e-mail; filtros ativo, bloqueado e primeiro acesso pendente.
- Ficha com foto e abas Dados cadastrais, Acesso e segurança, Inscrições e cursos e Histórico.
- Edição de identificação, contatos, endereço, escolaridade, formação teológica e origem da indicação. CPF opcional; e-mail opcional para alunos atendidos por WhatsApp.
- Prévia antes de criar ou salvar; conferência de identidade/destino, motivo de atendimento, senha atual da equipe, validação no servidor e proteção contra proposta alterada ou atendimento concorrente.
- Criar conta de acesso não cria matrícula acadêmica, cobrança ou inscrição.
- Inscrições originais são consultadas pelo vínculo explícito de matrícula, nunca por coincidência de CPF/e-mail. A ficha aceita vários cursos e mostra as datas de inscrição separadas das demais datas.
- Foto armazenada de forma privada e consultada por autorização. Documentos pessoais não são fotos de perfil.

## Acesso e atendimento

- Ativação, recuperação, senha temporária, bloqueio/desbloqueio e encerramento de sessões.
- Senha temporária com oito dígitos criptográficos, apresentados em dois grupos de quatro, validade de 30 minutos, uso único e máximo de cinco erros, com limites agregados que não reiniciam na reemissão.
- Código ou link só inicia sessão restrita para escolher senha definitiva. Nenhum acesso acadêmico é liberado antes dessa etapa. A senha definitiva segue a política já adotada pelo portal.
- Emitir credencial invalida senha antiga, credenciais anteriores, sessões e cookies de lembrança. SSO e painel antigo também respeitam a revogação.
- WhatsApp nunca comprova propriedade do e-mail. Corrigir e-mail mantém endereço anterior até confirmação individual na nova caixa postal.
- Prévia de link em GET não consome credencial; confirmação depende de POST.

## E-mail, WhatsApp e entrega presencial

- Ativação, recuperação, código e mensagens de orientação podem ser preparados para e-mail ou WhatsApp. Código temporário admite entrega presencial ao próprio aluno.
- E-mail utiliza fila database durável, com apenas o identificador da entrega no job. Conteúdo sensível permanece cifrado até envio/cancelamento/expiração.
- Antes de enviar, revalidar ator, aluno, contato, versão, validade e uso da credencial. Repetir um job concluído não repete o envio; falhas de transporte não devem expor conteúdo.
- WhatsApp utiliza rascunho oficial `wa.me`, aberto somente após conferência explícita. A equipe conclui o envio no aplicativo. Não há API paga, sessão não oficial nem indicação falsa de entrega/leitura.
- Tela de entrega é de exibição única, sem cache e sem envio de referência. Histórico contém metadados, não senhas, códigos, tokens ou conteúdo da mensagem.
- Conteúdos cifrados expirados são removidos pelo comando agendado `acessos:limpar-entregas`.

## Inscrição pública e limites

- Persistir ano de conclusão, formação teológica anterior, instituição, como conheceu e indicação, também em inscrição para vários cursos.
- Consentimento de termos só é registrado quando explicitamente aceito. Dados históricos ausentes permanecem nulos.
- Matrículas, notas, presença, financeiro, documentos e Telegram existentes são preservados; sua gestão completa pertence a outras etapas.
- Sem custos adicionais de integração. Sem envio real para alunos durante testes.
- Issue → branch → testes e revisão → PR. Implantação na VPS é uma etapa separada, com validação da candidata e preservação dos dados reais.
