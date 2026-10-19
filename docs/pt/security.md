# 🔐 Segurança e Conformidade

* **Acesso por capability:** o bloco, o painel e o web service de status exigem `moodle/course:update` no contexto do curso.
* **Isolamento por curso:** um item manual é sempre buscado junto com o curso pedido, então editar um curso nunca alcança os itens de outro.
* **Proteção contra CSRF:** o formulário do painel só é processado com um `sesskey` válido.
* **Entrada tipada e saída segura:** os títulos são lidos como parâmetros de texto e exibidos via `format_string()`; o status aceita apenas os três valores permitidos.
* **API externa do Moodle:** as ações AJAX usam um web service padrão por meio de `core/ajax`.
* **Privacy API completa:** metadados, exportação e exclusão dos itens do checklist, incluindo listas de usuários.
* **Limpeza na exclusão do curso:** um observador de evento remove os itens do curso quando ele é excluído.
* **Backup e restauração:** itens manuais e a chave da verificação automática são incluídos; os ids de usuário são remapeados na restauração.
* **Sem serviços externos e sem bibliotecas de terceiros:** o bloco nunca contacta um serviço de fora.
