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
 * PHPUnit tests for the course_deleted observer.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_teacher_checklist;

use advanced_testcase;

/**
 * Tests for the block_teacher_checklist course_deleted observer.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_teacher_checklist\observer
 */
final class observer_test extends advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Inserts a checklist item for a user in a course and returns its id.
     *
     * @param int $courseid Course the item belongs to.
     * @param int $userid Owner of the item.
     * @param string $title Item title.
     * @return int The inserted record id.
     */
    private function create_item(int $courseid, int $userid, string $title): int {
        global $DB;

        $now = time();

        return (int) $DB->insert_record('block_teacher_checklist', (object) [
            'courseid'     => $courseid,
            'userid'       => $userid,
            'type'         => 'manual',
            'subtype'      => '',
            'docid'        => 0,
            'title'        => $title,
            'status'       => 0,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Deleting a course through the standard Moodle flow must remove its checklist
     * items and leave every other course's items untouched.
     */
    public function test_course_deletion_removes_only_that_courses_items(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();

        $this->create_item($course->id, $user->id, 'To be deleted');
        $this->create_item($othercourse->id, $user->id, 'Untouched');

        delete_course($course->id, false);

        $this->assertEquals(0, $DB->count_records('block_teacher_checklist', ['courseid' => $course->id]));
        $this->assertEquals(1, $DB->count_records('block_teacher_checklist', ['courseid' => $othercourse->id]));
    }
}
