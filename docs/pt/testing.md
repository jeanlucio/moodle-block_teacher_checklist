# 🧪 Testes Automatizados

Cada push no CI executa as suítes PHPUnit e Behat contra as versões do Moodle suportadas.

### PHPUnit — Testes Unitários e de Integração

| Arquivo de teste | Testes | O que é coberto |
|------------------|------:|----------------|
| `tests/scanner_test.php` | 29 | A chave da verificação (ligada por padrão, ligada, desligada); as verificações automáticas com seus casos negativos — curso oculto, resumo, data de término, livro de notas, tarefas, fóruns (Avisos excluído), questionários vazios, conclusão; status ignorado; atividades ocultas não reportadas |
| `tests/scanner_grading_test.php` | 7 | A fila de correção, montada pelas APIs reais de `mod_assign` e `mod_quiz`: pendente até corrigir, envios em grupo, reenvio depois da nota, estudantes suspensos, tentativas com dissertativa, prévias do professor, questionários ocultos |
| `tests/renderer_test.php` | 8 | Ids dos itens, tratamento do ícone, o marcador `#` nunca renderizado como link, sanitização do título, ações por aba, marca de severidade, o limite de cinco itens do resumo do bloco, o estado vazio |
| `tests/checklist_block_test.php` | 5 | O bloco mostra os itens manuais pendentes ao professor e nada ao estudante, conteúdo em cache por objeto de bloco, sem problemas automáticos com a verificação desligada, formatos de página permitidos |
| `tests/external_test.php` | 9 | O web service de status: status inválido rejeitado, capability exigida, itens automáticos criados e atualizados, itens manuais atualizados e mantendo o autor, item manual inexistente |
| `tests/external_tasks_test.php` | 5 | A API pública: inserção, idempotência, outras origens e itens do professor preservados, títulos em branco ignorados, limpeza de uma origem |
| `tests/observer_test.php` | 1 | Excluir um curso remove apenas os itens daquele curso |
| `tests/backup_restore_test.php` | 3 | Backup e restauração reais de um curso: a chave da verificação e os itens manuais acompanham, os automáticos não, o destino mantém a própria chave |
| `tests/privacy/privacy_test.php` | 10 | Metadados declarando todas as colunas da tabela, descoberta de contextos e usuários, exportação localizada, exclusão por usuário, por contexto e por lista de usuários, e a exclusão preservando o estado do curso e os itens de outros professores |
| **Total** | **77** | |

### Behat — Testes de Aceitação

| Feature | Cenários | O que é coberto |
|---------|---------:|----------------|
| `tests/behat/manual_items.feature` | 3 | Adicionar um item manual no painel, vê-lo no bloco do curso, marcação digitada num título exibida como texto puro |
| `tests/behat/access.feature` | 2 | O estudante não vê o bloco; o professor vê |
