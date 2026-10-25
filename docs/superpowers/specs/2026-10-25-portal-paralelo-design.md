# Transição do Portal do Aluno — Issue #84

Aprovado pelo usuário em 25/10/2026. O novo portal funcionará em paralelo ao legado, inicialmente em homologação privada na VPS existente. A origem continua como referência e não será alterada. Não haverá novos serviços pagos, exportação de dados pessoais para o computador ou alteração de DNS/portais em produção nesta entrega.

Alternativas consideradas: substituição imediata (não escolhida) e migração em paralelo (escolhida, permite conferir dados antes de abrir ao público). Para acesso, caixas postais institucionais foram comparadas com matrícula permanente e e-mail pessoal opcional; o usuário escolheu a segunda opção.

Consolidar portal/sala e bot, preservar alterações locais, importar alunos/professores/cursos/vínculos/aulas/materiais com prévia, mapa persistente de IDs e reexecução idempotente. Não somar os tipos de vínculo do legado como se fossem equivalentes. Conflitos impedem sobrescrita; registrar apenas códigos e IDs técnicos em relatórios restritos. Histórico de presença/sessões continua rastreável, sem conversão automática para notas ou frequência oficial.

Matrícula permanece como login; CPF e e-mail confirmado podem ser alternativas sem ambiguidade. Preservar e testar hashes de senha; não criar senha padrão, senha por CPF, e-mail fictício ou exigir e-mail de quem não possui. Aluno pode estudar sem e-mail e depois cadastrar endereço pessoal com confirmação por link; mudar endereço não muda sua identidade ou histórico. Recuperação por e-mail somente após confirmação; antes disso, orientar atendimento da secretaria. Credenciais ausentes/incompatíveis entram em relatório para atendimento assistido.

Homologação usa bancos, rede, cookies, armazenamento e filas próprios, sem integrações reais, cobranças, mensagens externas ou webhook Telegram. Construir release limpa com lockfiles, testar PostgreSQL, autorização e arquivos. Web e worker usam a mesma versão e armazenamento compartilhado. Falha de migração impede inicialização. Preservar configurações/chave e atualizar backup de bancos/arquivos antes de futura publicação.

Aceite: importação repetida não duplica nem sobrescreve alterações locais; todos os alunos importados têm identidade rastreável; login sem e-mail funciona, matrícula permanece válida após confirmar/trocar e-mail; autorização limita dados por aluno/curso; PDF/caderno persistem; legado permanece disponível. Publicação pública e desligamento do legado não estão autorizados neste desenho.
