<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * PHPUnit tests for the backup and restore of the block data.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_teacher_checklist;

use advanced_testcase;

/**
 * Backs up a course that has the block and restores it into another course.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class backup_restore_test extends advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    }

    /**
     * Adds the block to the course page.
     *
     * @param \stdClass $course The course.
     */
    private function add_block(\stdClass $course): void {
        global $DB;

        $DB->insert_record('block_instances', (object) [
            'blockname' => 'teacher_checklist',
            'parentcontextid' => \context_course::instance($course->id)->id,
            'showinsubcontexts' => 0,
            'pagetypepattern' => 'course-view-*',
            'subpagepattern' => null,
            'defaultregion' => 'side-pre',
            'defaultweight' => 0,
            'configdata' => base64_encode(serialize(new \stdClass())),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Inserts a checklist row for a course.
     *
     * @param int $courseid Course the row belongs to.
     * @param string $type Row type: manual, auto or config.
     * @param string $subtype Row subtype.
     * @param int $status Row status.
     * @param string $title Title, used by manual rows.
     * @param int $docid Document id, used by auto rows.
     */
    private function add_row(int $courseid, string $type, string $subtype, int $status, string $title, int $docid = 0): void {
        global $DB, $USER;

        $DB->insert_record('block_teacher_checklist', (object) [
            'courseid' => $courseid,
            'userid' => $USER->id,
            'type' => $type,
            'subtype' => $subtype,
            'docid' => $docid,
            'title' => $title,
            'status' => $status,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Backs up the source course and restores it on top of the destination course.
     *
     * @param \stdClass $source Course to back up.
     * @param \stdClass $destination Course that receives the restore.
     */
    private function backup_and_restore(\stdClass $source, \stdClass $destination): void {
        $admin = get_admin();

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $source->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $admin->id
        );
        $bc->execute_plan();
        $backupfile = $bc->get_results()['backup_destination'];
        $bc->destroy();

        $tempdir = \restore_controller::get_tempdir_name($destination->id, $admin->id);
        $backupfile->extract_to_pathname(
            get_file_packer('application/vnd.moodle.backup'),
            make_backup_temp_directory($tempdir)
        );

        $rc = new \restore_controller(
            $tempdir,
            $destination->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $admin->id,
            \backup::TARGET_EXISTING_ADDING
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();
    }

    /**
     * The automatic-scan switch and the manual items travel with the course; automatic items do not.
     */
    public function test_restore_carries_scan_switch_and_manual_items_but_not_auto_items(): void {
        $source = $this->getDataGenerator()->create_course();
        $destination = $this->getDataGenerator()->create_course();
        $this->add_block($source);
        $this->add_row($source->id, 'config', 'scan_enabled', 0, '');
        $this->add_row($source->id, 'manual', '', 1, 'Print the syllabus');
        $this->add_row($source->id, 'auto', 'mod_assign_nodate', 2, '', 123);

        $this->backup_and_restore($source, $destination);

        $scanner = new scanner($destination);
        $this->assertFalse($scanner->is_active(), 'The automatic scan must stay disabled after the restore.');
        $this->assertEquals(1, $this->count_rows($destination->id, 'manual'));
        $this->assertEquals(0, $this->count_rows($destination->id, 'auto'));
        $this->assertEquals(1, $this->count_rows($destination->id, 'config'));
    }

    /**
     * Restoring into a course that already has its own switch keeps the value the teacher chose there.
     */
    public function test_restore_keeps_existing_scan_switch_of_the_destination(): void {
        $source = $this->getDataGenerator()->create_course();
        $destination = $this->getDataGenerator()->create_course();
        $this->add_block($source);
        $this->add_row($source->id, 'config', 'scan_enabled', 0, '');
        $this->add_row($destination->id, 'config', 'scan_enabled', 1, '');

        $this->backup_and_restore($source, $destination);

        $this->assertEquals(1, $this->count_rows($destination->id, 'config'));
        $this->assertTrue((new scanner($destination))->is_active());
    }

    /**
     * A course without a switch row restores without creating one, so the scan stays on by default.
     */
    public function test_restore_without_scan_switch_keeps_the_scan_enabled(): void {
        $source = $this->getDataGenerator()->create_course();
        $destination = $this->getDataGenerator()->create_course();
        $this->add_block($source);
        $this->add_row($source->id, 'manual', '', 0, 'Only a manual item');

        $this->backup_and_restore($source, $destination);

        $this->assertEquals(0, $this->count_rows($destination->id, 'config'));
        $this->assertTrue((new scanner($destination))->is_active());
    }

    /**
     * Counts the checklist rows of one type in a course.
     *
     * @param int $courseid Course id.
     * @param string $type Row type.
     * @return int Number of rows.
     */
    private function count_rows(int $courseid, string $type): int {
        global $DB;

        return $DB->count_records('block_teacher_checklist', ['courseid' => $courseid, 'type' => $type]);
    }
}
