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
 * Define the restore structure steps for the teacher_checklist block.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_teacher_checklist_structure_step extends restore_structure_step {
    /**
     * Define the structure of the restore.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('checklist_item', '/block/teacher_checklist/checklist_items/checklist_item');
        return $paths;
    }

    /**
     * Process the checklist item data.
     *
     * @param stdClass $data The data object from backup XML.
     * @throws dml_exception
     */
    public function process_checklist_item($data) {
        global $DB, $USER;

        $data = (object)$data;
        $data->courseid = $this->get_courseid();

        // Automatic items are never backed up; anything other than manual items and config rows is an anomaly.
        if (!in_array($data->type, ['manual', 'config'], true)) {
            return;
        }

        // Only the automatic-scan switch is a known config row. The destination course keeps its own value
        // when it already has one, so restoring into an existing course never overrides a teacher's choice.
        if ($data->type === 'config') {
            $this->process_config_item($data);
            return;
        }

        // 1. User mapping.
        $newuserid = $this->get_mappingid('user', $data->userid);
        $data->userid = $newuserid ? $newuserid : $USER->id;

        // 2. Data cleaning.
        // Manual items do not use docid, ensure it is 0.
        $data->docid = 0;

        // OPTIONAL: If you want ALL manual items to return as 'Pending' (Status 0)
        // uncomment the line below. Otherwise, it keeps the original status (Done/Ignored).

        // 3. Safe recording (Prevention of SQL Text Error).
        // We use sql_compare_text because the title is TEXT.
        $comparetitle = $DB->sql_compare_text('title');

        $sql = "SELECT id
                FROM {block_teacher_checklist}
                WHERE courseid = ?
                  AND type = 'manual'
                  AND $comparetitle = ?";

        $exists = $DB->record_exists_sql($sql, [$data->courseid, $data->title]);

        if (!$exists) {
            $DB->insert_record('block_teacher_checklist', $data);
        }
    }

    /**
     * Restore a course config row, keeping the destination value when one already exists.
     *
     * @param stdClass $data The config row from the backup XML, with courseid already set to the destination.
     */
    protected function process_config_item(stdClass $data): void {
        global $DB, $USER;

        if ($data->subtype !== 'scan_enabled') {
            return;
        }

        $conditions = [
            'courseid' => $data->courseid,
            'type' => 'config',
            'subtype' => $data->subtype,
            'docid' => 0,
        ];
        if ($DB->record_exists('block_teacher_checklist', $conditions)) {
            return;
        }

        $newuserid = $this->get_mappingid('user', $data->userid);
        $data->userid = $newuserid ? $newuserid : $USER->id;
        $data->docid = 0;
        unset($data->id);
        $DB->insert_record('block_teacher_checklist', $data);
    }
}
