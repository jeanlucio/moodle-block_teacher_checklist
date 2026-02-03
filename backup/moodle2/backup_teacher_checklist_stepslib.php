<?php
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
 * Define the backup structure steps for the teacher_checklist block.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_teacher_checklist_structure_step extends backup_block_structure_step {
    /**
     * Define the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        // 1. Define the root element.
        $checklist = new backup_nested_element('teacher_checklist', ['id'], null);

        // 2. Define the items.
        $items = new backup_nested_element('checklist_items');

        // 3. Define the item fields.
        $item = new backup_nested_element('checklist_item', ['id'], [
            'userid', 'type', 'subtype', 'docid', 'title', 'status', 'timecreated', 'timemodified',
        ]);

        // 4. Build the tree.
        $checklist->add_child($items);
        $items->add_child($item);

        // 5. Define data source WITH FILTER.
        // KEY POINT: We only filter where type = 'manual'.
        // Thus, we do not backup the automatic items.
        $item->set_source_table('block_teacher_checklist', [
            'courseid' => backup::VAR_COURSEID,
            'type'     => backup_helper::is_sqlparam('manual'),
        ]);

        // 6. Annotate user ids.
        $item->annotate_ids('user', 'userid');

        // 7. Return the structure.
        return $this->prepare_block_structure($checklist);
    }
}
