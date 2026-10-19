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
 * PHPUnit tests for the block class.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_teacher_checklist;

use advanced_testcase;

/**
 * Tests for who sees the block and what it shows.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_teacher_checklist
 */
final class checklist_block_test extends advanced_testcase {
    /** @var \stdClass Test course. */
    private $course;

    #[\Override]
    protected function setUp(): void {
        global $PAGE;

        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course(['visible' => 1]);
        $PAGE->set_course($this->course);
        $PAGE->set_url('/course/view.php', ['id' => $this->course->id]);
    }

    /**
     * Builds a fresh block object, as Moodle does on every page load.
     *
     * @return \block_teacher_checklist The block.
     */
    private function make_block(): \block_teacher_checklist {
        global $DB, $PAGE;

        $record = $DB->get_record('block_instances', ['blockname' => 'teacher_checklist']);
        if (!$record) {
            $record = $this->getDataGenerator()->create_block('teacher_checklist', [
                'parentcontextid' => \context_course::instance($this->course->id)->id,
            ]);
            $record = $DB->get_record('block_instances', ['id' => $record->id]);
        }

        return block_instance('teacher_checklist', $record, $PAGE);
    }

    /**
     * Adds a manual item to the course.
     *
     * @param string $title Item title.
     * @param int $status Item status.
     */
    private function add_manual_item(string $title, int $status = 0): void {
        global $DB, $USER;

        $DB->insert_record('block_teacher_checklist', (object) [
            'courseid' => $this->course->id,
            'userid' => $USER->id,
            'type' => 'manual',
            'subtype' => '',
            'docid' => 0,
            'title' => $title,
            'status' => $status,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * A teacher of the course sees pending manual items in the block.
     */
    public function test_teacher_sees_pending_manual_items(): void {
        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($teacher);
        $this->add_manual_item('Print the syllabus');
        $this->add_manual_item('Already finished item', 1);

        $content = $this->make_block()->get_content();

        $this->assertStringContainsString('Print the syllabus', $content->text);
        $this->assertStringNotContainsString('Already finished item', $content->text);
    }

    /**
     * A student of the same course gets an empty block, so nothing of the checklist leaks.
     */
    public function test_student_gets_no_content(): void {
        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($teacher);
        $this->add_manual_item('Teacher only note');
        $this->setUser($student);

        $content = $this->make_block()->get_content();

        $this->assertFalse(isset($content->text));
    }

    /**
     * Content is built once per block object.
     */
    public function test_content_is_cached_per_block_object(): void {
        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($teacher);
        $block = $this->make_block();

        $this->assertSame($block->get_content(), $block->get_content());
    }

    /**
     * Only manual items count when the automatic scan is switched off.
     */
    public function test_no_automatic_issues_when_scan_is_disabled(): void {
        global $DB;

        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($teacher);
        $DB->set_field('course', 'visible', 0, ['id' => $this->course->id]);
        $this->course->visible = 0;
        $DB->insert_record('block_teacher_checklist', (object) [
            'courseid' => $this->course->id,
            'userid' => $teacher->id,
            'type' => 'config',
            'subtype' => 'scan_enabled',
            'docid' => 0,
            'status' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $this->add_manual_item('Only manual');

        $content = $this->make_block()->get_content();

        $this->assertStringContainsString('Only manual', $content->text);
        $this->assertStringNotContainsString(get_string('issue_course_hidden', 'block_teacher_checklist'), $content->text);
    }

    /**
     * The block can only be added to course pages.
     */
    public function test_applicable_formats(): void {
        $this->assertSame(['course-view' => true], $this->make_block()->applicable_formats());
    }
}
