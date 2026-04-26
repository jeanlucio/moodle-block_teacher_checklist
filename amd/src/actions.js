// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript actions for the teacher checklist block.
 *
 * @module      block_teacher_checklist/actions
 * @copyright   2026 Jean Lúcio
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    return {
        init: function() {

            /**
             * Sends update requests to the server.
             * @param {Array} itemsData Array of item objects.
             * @param {Function} onSuccess Callback executed on success.
             */
            function processUpdates(itemsData, onSuccess) {
                var calls = itemsData.map(function(data) {
                    return {
                        methodname: 'block_teacher_checklist_toggle_item_status',
                        args: data
                    };
                });

                Ajax.call(calls)[calls.length - 1].done(function() {
                    if (typeof onSuccess === 'function') {
                        onSuccess();
                    }
                }).fail(function(ex) {
                    Notification.exception(ex);
                });
            }

            /**
             * Removes a checklist item element from the list with a fade animation.
             * @param {jQuery} el The item element ([data-region="checklist-item"]).
             */
            function removeItem(el) {
                el.fadeOut(300, function() {
                    $(this).remove();
                });
            }

            // 1. INDIVIDUAL TOGGLE BUTTONS (Done, Ignore, Restore).
            // Delegated event on body to support dynamically rendered content.
            $('body').on('click', '[data-action="toggle-status"]', function(e) {
                e.preventDefault();
                var btn = $(this);
                btn.prop('disabled', true);

                var item = btn.closest('[data-region="checklist-item"]');

                processUpdates([{
                    courseid: btn.data('courseid'),
                    type: btn.data('type'),
                    subtype: btn.data('subtype'),
                    docid: btn.data('docid'),
                    status: btn.data('status')
                }], function() {
                    removeItem(item);
                });
            });

            // 2. "SELECT ALL" LOGIC.
            $('.select-all-toggle').on('change', function() {
                var targetList = $(this).data('target');
                var isChecked = $(this).is(':checked');

                $(targetList).find('.item-checkbox').prop('checked', isChecked).trigger('change');
            });

            // 3. BULK ACTIONS VISIBILITY.
            // Delegated on body to handle dynamically added checkboxes.
            $('body').on('change', '.item-checkbox', function() {
                var container = $(this).closest('.tab-pane');
                var totalChecked = container.find('.item-checkbox:checked').length;
                var bulkContainer = container.find('.bulk-actions-container');

                if (totalChecked > 0) {
                    bulkContainer.fadeIn(200);
                } else {
                    bulkContainer.fadeOut(200);
                    container.find('.select-all-toggle').prop('checked', false);
                }
            });

            // 4. BULK ACTION BUTTONS.
            $('.bulk-btn').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var newStatus = btn.data('action');
                var courseId = btn.data('courseid');
                var container = btn.closest('.tab-pane');
                var checkedBoxes = container.find('.item-checkbox:checked');
                var requests = [];

                checkedBoxes.each(function() {
                    var chk = $(this);
                    requests.push({
                        courseid: courseId,
                        type: chk.data('type'),
                        subtype: chk.data('subtype'),
                        docid: chk.data('docid'),
                        status: newStatus
                    });
                });

                if (requests.length > 0) {
                    btn.prop('disabled', true);

                    // Collect item elements before the async call.
                    var itemElements = checkedBoxes.map(function() {
                        return $(this).closest('[data-region="checklist-item"]')[0];
                    }).get();

                    processUpdates(requests, function() {
                        $.each(itemElements, function(i, el) {
                            removeItem($(el));
                        });
                        container.find('.bulk-actions-container').fadeOut(200);
                        container.find('.select-all-toggle').prop('checked', false);
                        btn.prop('disabled', false);
                    });
                } else {
                    Str.get_strings([
                        {key: 'notice', component: 'moodle'},
                        {key: 'no_items_selected', component: 'block_teacher_checklist'}
                    ]).done(function(s) {
                        Notification.alert(s[0], s[1], 'Ok');
                    });
                }
            });

            // 5. AUTO SCAN TOGGLE SWITCH.
            // Requires a full page reload since enabling/disabling the scanner changes
            // the entire list of displayed items.
            $('.toggle-scan-switch').on('change', function() {
                var isChecked = $(this).is(':checked');
                var courseId = $(this).data('courseid');

                processUpdates([{
                    courseid: courseId,
                    type:     'config',
                    subtype:  'scan_enabled',
                    docid:    0,
                    status:   isChecked ? 1 : 0
                }], function() {
                    window.location.reload();
                });
            });
        }
    };
});
