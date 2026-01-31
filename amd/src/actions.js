define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    return {
        init: function() {

            /**
             * Processa e envia as atualizações de status para o servidor.
             *
             * @param {Array} itemsData Lista de objetos contendo os dados dos itens a atualizar.
             */
            function processUpdates(itemsData) {
                // Cria o array de chamadas para o Moodle
                var calls = itemsData.map(function(data) {
                    return {
                        methodname: 'block_teacher_checklist_toggle_item_status',
                        args: data
                    };
                });

                // Executa em lote
                Ajax.call(calls)[calls.length - 1].done(function() {
                    window.location.reload();
                }).fail(Notification.exception);
            }

            // 1. AÇÃO INDIVIDUAL (Botões laterais)
            $('.block_teacher_checklist').on('click', '[data-action="toggle-status"]', function(e) {
                e.preventDefault();
                var btn = $(this);
                // Chama a função de processar (agora em camelCase)
                processUpdates([{
                    courseid: btn.data('courseid'),
                    type: btn.data('type'),
                    subtype: btn.data('subtype'),
                    docid: btn.data('docid'),
                    status: btn.data('status')
                }]);
            });

            // 2. LÓGICA DO "SELECIONAR TODOS"
            $('.select-all-toggle').on('change', function() {
                var targetList = $(this).data('target');
                var isChecked = $(this).is(':checked');

                // Marca/Desmarca todos os checkboxes VISÍVEIS dentro da lista alvo
                $(targetList).find('.item-checkbox').prop('checked', isChecked).trigger('change');
            });

            // 3. EXIBIR/OCULTAR BARRA DE AÇÕES EM MASSA
            $('.item-checkbox').on('change', function() {
                // Procura a barra de ações mais próxima (dentro da mesma aba)
                var container = $(this).closest('.tab-pane');
                var totalChecked = container.find('.item-checkbox:checked').length;

                if (totalChecked > 0) {
                    container.find('.bulk-actions-container').fadeIn(200);
                } else {
                    container.find('.bulk-actions-container').fadeOut(200);
                    // Desmarca o "Select All" se desmarcar um item
                    container.find('.select-all-toggle').prop('checked', false);
                }
            });

            // 4. CLIQUE NOS BOTÕES DE AÇÃO EM MASSA
            $('.bulk-btn').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var newStatus = btn.data('action');
                var courseId = btn.data('courseid');
                var container = btn.closest('.tab-pane');

                var requests = [];

                // Varre todos os checkboxes marcados nesta aba
                container.find('.item-checkbox:checked').each(function() {
                    var chk = $(this);

                    // IMPORTANTE: Se a ação for "Feito" (status 1) e o item for automático, IGNORA.
                    if (newStatus == 1 && chk.data('type') === 'auto') {
                        return; // Pula este item
                    }

                    requests.push({
                        courseid: courseId,
                        type: chk.data('type'),
                        subtype: chk.data('subtype'),
                        docid: chk.data('docid'),
                        status: newStatus
                    });
                });

                if (requests.length > 0) {
                    processUpdates(requests);
                } else {
                    // Feedback caso tente marcar automático como feito
                    Notification.alert('Aviso', 'Nenhum item válido selecionado para esta ação.', 'Ok');
                }
            });

            // 5. NOVO: INTERRUPTOR DE VARREDURA AUTOMÁTICA
            $('#toggleScan').on('change', function() {
                var isChecked = $(this).is(':checked');
                var courseId = $(this).data('courseid');

                // Envia como um item especial do tipo 'config'
                processUpdates([{
                    courseid: courseId,
                    type:     'config',
                    subtype:  'scan_enabled',
                    docid:    0,
                    status:   isChecked ? 1 : 0
                }]);
            });

        }
    };
});
