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
 * PHPUnit tests for external_tasks.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_teacher_checklist;

use advanced_testcase;
use block_teacher_checklist\local\external_tasks;

/**
 * Tests for the external seeding API.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_teacher_checklist\local\external_tasks
 */
final class external_tasks_test extends advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * replace() inserts the right number of items with the correct tags.
     */
    public function test_replace_inserts_items(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        external_tasks::replace($course->id, 'local_virtuallab', ['Task A', 'Task B', 'Task C']);

        $rows = $DB->get_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'type'     => 'manual',
            'subtype'  => 'local_virtuallab',
        ]);
        $this->assertCount(3, $rows);

        $titles = array_column(array_values($rows), 'title');
        $this->assertContains('Task A', $titles);
        $this->assertContains('Task B', $titles);
        $this->assertContains('Task C', $titles);
    }

    /**
     * Calling replace() twice for the same source replaces, not accumulates.
     */
    public function test_replace_is_idempotent(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        external_tasks::replace($course->id, 'local_virtuallab', ['Task A', 'Task B']);
        external_tasks::replace($course->id, 'local_virtuallab', ['Task A', 'Task B']);

        $this->assertEquals(2, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'subtype'  => 'local_virtuallab',
        ]));
    }

    /**
     * replace() leaves items added by the teacher and by other sources untouched.
     */
    public function test_replace_preserves_other_source_items(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $now    = time();

        // Teacher's own manual item (empty subtype).
        $DB->insert_record('block_teacher_checklist', (object) [
            'courseid'     => $course->id,
            'userid'       => 2,
            'type'         => 'manual',
            'subtype'      => '',
            'docid'        => 0,
            'title'        => 'Teacher item',
            'status'       => 0,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);

        // Item provisioned by a different plugin.
        $DB->insert_record('block_teacher_checklist', (object) [
            'courseid'     => $course->id,
            'userid'       => 2,
            'type'         => 'manual',
            'subtype'      => 'other_plugin',
            'docid'        => 0,
            'title'        => 'Other source item',
            'status'       => 0,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);

        external_tasks::replace($course->id, 'local_virtuallab', ['Lab task']);

        $this->assertEquals(1, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'subtype'  => '',
        ]), 'Teacher item must not be removed.');

        $this->assertEquals(1, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'subtype'  => 'other_plugin',
        ]), 'Other-source item must not be removed.');

        $this->assertEquals(1, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'subtype'  => 'local_virtuallab',
        ]), 'Provisioned item must exist.');
    }

    /**
     * Blank and whitespace-only titles are silently skipped.
     */
    public function test_replace_skips_blank_titles(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        external_tasks::replace($course->id, 'local_virtuallab', ['Task A', '', '   ', 'Task B']);

        $this->assertEquals(2, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'subtype'  => 'local_virtuallab',
        ]));
    }

    /**
     * replace() with an empty array removes all items from that source.
     */
    public function test_replace_with_empty_array_clears_source(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        external_tasks::replace($course->id, 'local_virtuallab', ['Task A', 'Task B']);
        external_tasks::replace($course->id, 'local_virtuallab', []);

        $this->assertEquals(0, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'subtype'  => 'local_virtuallab',
        ]));
    }
}
