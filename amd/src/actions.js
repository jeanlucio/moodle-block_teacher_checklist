define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    return {
        init: function() {

            /**
             * Sends update requests to the server.
             * @param {Array} itemsData Array of item objects.
             */
            function processUpdates(itemsData) {
                var calls = itemsData.map(function(data) {
                    return {
                        methodname: 'block_teacher_checklist_toggle_item_status',
                        args: data
                    };
                });

                Ajax.call(calls)[calls.length - 1].done(function() {
                    window.location.reload();
                }).fail(Notification.exception);
            }

            // 1. INDIVIDUAL TOGGLE BUTTONS (Done, Ignore, Restore)
            // We use delegated events on document or a main container for dynamic content support.
            $('body').on('click', '[data-action="toggle-status"]', function(e) {
                e.preventDefault();
                var btn = $(this);

                processUpdates([{
                    courseid: btn.data('courseid'),
                    type: btn.data('type'),
                    subtype: btn.data('subtype'),
                    docid: btn.data('docid'),
                    status: btn.data('status')
                }]);
            });

            // 2. "SELECT ALL" LOGIC
            $('.select-all-toggle').on('change', function() {
                var targetList = $(this).data('target');
                var isChecked = $(this).is(':checked');

                $(targetList).find('.item-checkbox').prop('checked', isChecked).trigger('change');
            });

            // 3. BULK ACTIONS VISIBILITY
            $('.item-checkbox').on('change', function() {
                var container = $(this).closest('.tab-pane'); // Find parent tab pane
                var totalChecked = container.find('.item-checkbox:checked').length;
                var bulkContainer = container.find('.bulk-actions-container');

                if (totalChecked > 0) {
                    bulkContainer.fadeIn(200);
                } else {
                    bulkContainer.fadeOut(200);
                    // Uncheck main toggle if no items are checked
                    container.find('.select-all-toggle').prop('checked', false);
                }
            });

            // 4. BULK ACTION BUTTONS
            $('.bulk-btn').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var newStatus = btn.data('action');
                var courseId = btn.data('courseid');
                var container = btn.closest('.tab-pane');
                var requests = [];

                container.find('.item-checkbox:checked').each(function() {
                    var chk = $(this);

                    // Prevent marking "auto" items as "done" (status 1) via bulk action if needed,
                    // but logic permits it if displayed. Assuming auto items CANNOT be manually marked done
                    // unless they are removed from the list automatically.
                    // Logic check: Auto items usually disappear when done in Moodle.
                    // If the checkbox is there, we assume it's actionable.

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
                    Str.get_strings([
                        {key: 'notice', component: 'moodle'},
                        {key: 'no_items_selected', component: 'block_teacher_checklist'}
                    ]).done(function(s) {
                        Notification.alert(s[0], s[1], 'Ok');
                    });
                }
            });

            // 5. AUTO SCAN TOGGLE SWITCH
            $('#toggleScan').on('change', function() {
                var isChecked = $(this).is(':checked');
                var courseId = $(this).data('courseid');

                processUpdates([{
                    courseid: courseId,
                    type: 'config',
                    subtype: 'scan_enabled',
                    docid: 0,
                    status: isChecked ? 1 : 0
                }]);
            });
        }
    };
});
