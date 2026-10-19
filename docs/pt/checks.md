# 🔍 Verificações automáticas

Cada verificação roda sobre as atividades **visíveis para os estudantes**; atividades ocultas não são reportadas. Qualquer problema pode ser marcado como Ignorado e continua ignorado até ser restaurado.

| # | Verificação | O que detecta |
|---|-------------|---------------|
| 1 | Visibilidade do curso | Curso oculto para estudantes |
| 2 | Resumo do curso | Curso sem resumo ou descrição |
| 3 | Data de término | Curso sem data de término configurada |
| 4 | Livro de notas | Nenhum item de avaliação configurado |
| 5 | Tarefas | Sem data de entrega, sem descrição, envios aguardando correção |
| 6 | Questionários | Sem questões, sem data de fechamento e sem limite de tempo, tentativas aguardando correção manual |
| 7 | Fóruns | Sem tópicos de discussão; sem descrição (o Fórum de Avisos é excluído) |
| 8 | Rastreamento de conclusão | Atividades visíveis com rastreamento de conclusão desligado, quando o curso tem conclusão habilitada |
| 9 | Seções vazias | Seções visíveis do curso sem atividades e sem resumo |

### Como "aguardando correção" é contado

* **Tarefas:** só conta a **última tentativa** de um participante com **matrícula ativa**. Fica pendente quando ainda não tem nota, ou quando foi editada depois de corrigida. Tarefas em grupo são contadas pelos envios dos próprios membros, então a linha compartilhada do grupo nunca mantém o alerta vivo depois que todos são corrigidos.
* **Questionários:** tentativas finalizadas cuja nota total ainda está vazia (tipicamente por causa de uma questão dissertativa). As tentativas de **prévia** do próprio professor não são contadas.
