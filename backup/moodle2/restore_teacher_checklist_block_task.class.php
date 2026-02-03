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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/teacher_checklist/backup/moodle2/restore_teacher_checklist_stepslib.php');

/**
 * Restore task for the teacher_checklist block.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_teacher_checklist_block_task extends restore_block_task {
    /**
     * Define the restore steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_teacher_checklist_structure_step('teacher_checklist_structure', 'block_teacher_checklist.xml'));
    }

    /**
     * Define the restore settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Define the decode contents.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * Define the decode rules.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [];
    }

    /**
     * Get the file areas.
     *
     * @return array
     */
    public function get_fileareas() {
        return [];
    }

    /**
     * Get the config data encoded attributes.
     *
     * @return array
     */
    public function get_configdata_encoded_attributes() {
        return [];
    }
}
