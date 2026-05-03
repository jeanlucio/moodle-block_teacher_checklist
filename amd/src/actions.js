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

            // Strings pre-loaded for dynamic button creation.
            var strings = {markdone: '', ignore: '', restore: ''};
            Str.get_strings([
                {key: 'mark_done', component: 'block_teacher_checklist'},
                {key: 'ignore', component: 'block_teacher_checklist'},
                {key: 'restore', component: 'block_teacher_checklist'}
            ]).done(function(s) {
                strings.markdone = s[0];
                strings.ignore = s[1];
                strings.restore = s[2];
            });

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
             * Builds an action button element.
             * @param {string} classes Extra CSS classes.
             * @param {number} status Target status value.
             * @param {Object} data Item data attributes.
             * @param {string} iconClass FontAwesome icon class (e.g. "fa-check").
             * @param {string} label Accessible label.
             * @returns {jQuery} The button element.
             */
            function buildBtn(classes, status, data, iconClass, label) {
                return $('<button type="button"></button>')
                    .addClass('btn btn-sm ' + classes)
                    .attr({
                        'data-action': 'toggle-status',
                        'data-courseid': data.courseid,
                        'data-type': data.type,
                        'data-subtype': data.subtype,
                        'data-docid': data.docid,
                        'data-status': status,
                        'title': label,
                        'aria-label': label
                    })
                    .html('<i class="fa ' + iconClass + '" aria-hidden="true"></i>');
            }

            /**
             * Extracts item data from an item element's action buttons.
             * @param {jQuery} item The checklist item element.
             * @returns {Object} Item data for AJAX requests.
             */
            function getItemData(item) {
                var btn = item.find('[data-action="toggle-status"]').first();
                return {
                    courseid: btn.data('courseid'),
                    type: item.data('item-type'),
                    subtype: btn.data('subtype'),
                    docid: btn.data('docid')
                };
            }

            /**
             * Updates a tab badge count by a delta value.
             * @param {string} tabBtnId CSS selector of the tab button.
             * @param {number} delta Positive or negative integer.
             */
            function updateBadge(tabBtnId, delta) {
                var badge = $(tabBtnId).find('.badge');
                var n = Math.max(0, (parseInt(badge.text(), 10) || 0) + delta);
                badge.text(n);
            }

            /**
             * Shows/hides the bulk toolbar and empty-state alert based on list content.
             * @param {jQuery} tabPane The tab-pane container.
             */
            function refreshTabState(tabPane) {
                var hasItems = tabPane.find('.list-group [data-region="checklist-item"]').length > 0;
                tabPane.find('.bulk-toolbar').toggleClass('d-none', !hasItems);
                tabPane.find('.tc-empty-alert').toggleClass('d-none', hasItems);
            }

            /**
             * Renumbers visible items in a list sequentially.
             * @param {jQuery} list The list-group element.
             */
            function renumberList(list) {
                list.find('[data-region="checklist-item"] .item-number').each(function(i) {
                    $(this).text((i + 1) + '.');
                });
            }

            /**
             * Moves a checklist item to the appropriate list after a status change,
             * updating its buttons, badge counts, and tab empty states.
             * @param {jQuery} item The checklist item element.
             * @param {number} newStatus 0 = restore, 1 = done, 2 = ignored.
             */
            function moveItem(item, newStatus) {
                var data = getItemData(item);
                var type = item.data('item-type');
                var actionsDiv = item.find('.actions');
                var checkbox = item.find('.item-checkbox');
                var sourceTabPane = item.closest('.tab-pane');
                var sourceId = sourceTabPane.attr('id');
                var targetListId, targetTabBtnId;

                actionsDiv.empty();

                if (newStatus === 0) {
                    // Restore to pending.
                    item.removeClass('bg-light');
                    if (type === 'manual') {
                        actionsDiv.append(buildBtn('btn-outline-success me-1', 1, data, 'fa-check', strings.markdone));
                        checkbox.attr('data-markable', '1');
                    } else {
                        checkbox.removeAttr('data-markable');
                    }
                    actionsDiv.append(buildBtn('btn-outline-danger', 2, data, 'fa-times', strings.ignore));
                    targetListId = '#list-pending';
                    targetTabBtnId = '#pending-tab';

                } else if (newStatus === 1) {
                    // Mark as done.
                    item.addClass('bg-light');
                    checkbox.removeAttr('data-markable');
                    actionsDiv.append(buildBtn('btn-outline-secondary me-1', 0, data, 'fa-undo', strings.restore));
                    targetListId = '#list-done';
                    targetTabBtnId = '#done-tab';

                } else {
                    // Ignore.
                    item.removeClass('bg-light');
                    checkbox.removeAttr('data-markable');
                    actionsDiv.append(buildBtn('btn-outline-secondary me-1', 0, data, 'fa-undo', strings.restore));
                    targetListId = '#list-ignored';
                    targetTabBtnId = '#ignored-tab';
                }

                var sourceTabBtnId = '#' + sourceId + '-tab';
                var targetList = $(targetListId);

                checkbox.prop('checked', false);
                targetList.append(item);

                updateBadge(sourceTabBtnId, -1);
                updateBadge(targetTabBtnId, 1);
                refreshTabState(sourceTabPane);
                refreshTabState(targetList.closest('.tab-pane'));
                renumberList(sourceTabPane.find('.list-group'));
                renumberList(targetList);
            }

            // 1. INDIVIDUAL TOGGLE BUTTONS (Done, Ignore, Restore).
            // Delegated event on body to support dynamically rendered content.
            $('body').on('click', '[data-action="toggle-status"]', function(e) {
                e.preventDefault();
                var btn = $(this);
                btn.prop('disabled', true);

                var item = btn.closest('[data-region="checklist-item"]');
                var status = parseInt(btn.data('status'), 10);

                processUpdates([{
                    courseid: btn.data('courseid'),
                    type: btn.data('type'),
                    subtype: btn.data('subtype'),
                    docid: btn.data('docid'),
                    status: status
                }], function() {
                    moveItem(item, status);
                });
            });

            // 2. "SELECT ALL" LOGIC.
            $('.select-all-toggle').on('change', function() {
                var targetList = $(this).data('target');
                var isChecked = $(this).is(':checked');

                $(targetList).find('.item-checkbox').prop('checked', isChecked).trigger('change');
            });

            // 3. BULK ACTIONS VISIBILITY.
            // The "Mark done" button is only shown when at least one selected item
            // has data-markable (manual items). Auto-scan items are excluded from that action.
            // Counts on each button reflect how many items each action will affect.
            $('body').on('change', '.item-checkbox', function() {
                var container = $(this).closest('.tab-pane');
                var checked = container.find('.item-checkbox:checked');
                var totalChecked = checked.length;
                var bulkContainer = container.find('.bulk-actions-container');

                if (totalChecked > 0) {
                    var markableCount = checked.filter('[data-markable]').length;
                    var doneBtn = container.find('.bulk-done-btn');
                    var ignoreBtn = container.find('.bulk-ignore-btn');

                    doneBtn.toggle(markableCount > 0);
                    doneBtn.find('.bulk-count').text('(' + markableCount + ')');
                    ignoreBtn.find('.bulk-count').text('(' + totalChecked + ')');
                    container.find('.bulk-btn:not(.bulk-done-btn):not(.bulk-ignore-btn)')
                        .find('.bulk-count').text('(' + totalChecked + ')');

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
                var isDoneAction = (parseInt(newStatus, 10) === 1);
                var checkedBoxes = container.find('.item-checkbox:checked');

                // For "done", only process markable items (manual ones).
                var actionBoxes = isDoneAction ? checkedBoxes.filter('[data-markable]') : checkedBoxes;
                var requests = [];

                actionBoxes.each(function() {
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

                    // Collect only the item elements that will actually be processed.
                    var itemElements = actionBoxes.map(function() {
                        return $(this).closest('[data-region="checklist-item"]')[0];
                    }).get();

                    processUpdates(requests, function() {
                        $.each(itemElements, function(i, el) {
                            moveItem($(el), parseInt(newStatus, 10));
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
