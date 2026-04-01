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

namespace block_teacher_checklist;

use advanced_testcase;

/**
 * Unit tests for the external API (toggle_item_status).
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_teacher_checklist\external::toggle_item_status
 */
final class external_test extends advanced_testcase {
    /** @var \stdClass Test course. */
    protected $course;

    /** @var \stdClass Teacher user with moodle/course:update capability. */
    protected $teacher;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course();
        $this->teacher = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user(
            $this->teacher->id,
            $this->course->id,
            'editingteacher'
        );

        $this->setUser($this->teacher);
    }

    /**
     * Status values outside [0, 1, 2] must throw invalid_parameter_exception.
     *
     * @param int $badstatus The invalid status value being tested.
     * @dataProvider invalid_status_provider
     */
    public function test_toggle_throws_on_invalid_status(int $badstatus): void {
        $this->expectException(\invalid_parameter_exception::class);

        external::toggle_item_status(
            $this->course->id,
            'auto',
            'course',
            0,
            $badstatus
        );
    }

    /**
     * Data provider: status values that must be rejected by toggle_item_status.
     *
     * @return array<string, array{int}>
     */
    public static function invalid_status_provider(): array {
        return [
            'negative value' => [-1],
            'out of range'   => [3],
            'large number'   => [99],
        ];
    }

    /**
     * A student (without moodle/course:update) must not be able to toggle items.
     */
    public function test_toggle_requires_course_update_capability(): void {
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $this->course->id, 'student');
        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);

        external::toggle_item_status(
            $this->course->id,
            'auto',
            'course',
            0,
            1
        );
    }

    /**
     * Toggling an auto item that has no existing record must INSERT a new row.
     */
    public function test_toggle_auto_item_creates_record_when_none_exists(): void {
        global $DB;

        $result = external::toggle_item_status(
            $this->course->id,
            'auto',
            'course',
            0,
            2
        );

        $this->assertTrue($result['success']);

        $record = $DB->get_record('block_teacher_checklist', [
            'courseid' => $this->course->id,
            'type'     => 'auto',
            'subtype'  => 'course',
            'docid'    => 0,
        ]);

        $this->assertNotFalse($record, 'A new record should have been created.');
        $this->assertEquals(2, (int)$record->status);
        $this->assertEquals($this->teacher->id, (int)$record->userid);
    }

    /**
     * Toggling an auto item that already exists must UPDATE the existing row
     * instead of inserting a duplicate.
     */
    public function test_toggle_auto_item_updates_existing_record(): void {
        global $DB;

        $id = $DB->insert_record('block_teacher_checklist', (object)[
            'courseid'     => $this->course->id,
            'userid'       => $this->teacher->id,
            'type'         => 'auto',
            'subtype'      => 'course',
            'docid'        => 0,
            'status'       => 0,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        external::toggle_item_status(
            $this->course->id,
            'auto',
            'course',
            0,
            1
        );

        $count = $DB->count_records('block_teacher_checklist', [
            'courseid' => $this->course->id,
            'type'     => 'auto',
            'subtype'  => 'course',
            'docid'    => 0,
        ]);
        $this->assertEquals(1, $count, 'No duplicate record should be created.');

        $record = $DB->get_record('block_teacher_checklist', ['id' => $id]);
        $this->assertEquals(1, (int)$record->status);
    }

    /**
     * A successful toggle must return ['success' => true].
     */
    public function test_toggle_returns_success_true(): void {
        $result = external::toggle_item_status(
            $this->course->id,
            'auto',
            'gradebook',
            -1,
            2
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    /**
     * Toggling a manual item must update its status in the DB.
     */
    public function test_toggle_manual_item_updates_status(): void {
        global $DB;

        $recordid = $DB->insert_record('block_teacher_checklist', (object)[
            'courseid'     => $this->course->id,
            'userid'       => $this->teacher->id,
            'type'         => 'manual',
            'title'        => 'Prepare lecture slides',
            'status'       => 0,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        $result = external::toggle_item_status(
            $this->course->id,
            'manual',
            '',
            $recordid,
            1
        );

        $this->assertTrue($result['success']);

        $updated = $DB->get_record('block_teacher_checklist', ['id' => $recordid]);
        $this->assertEquals(1, (int)$updated->status);
        $this->assertEquals($this->teacher->id, (int)$updated->userid);
    }

    /**
     * Toggling a manual item that does not exist (wrong id) must return success
     * without throwing; the method silently skips missing records.
     */
    public function test_toggle_manual_item_nonexistent_returns_success(): void {
        $result = external::toggle_item_status(
            $this->course->id,
            'manual',
            '',
            999999,
            1
        );

        $this->assertTrue($result['success']);
    }

    /**
     * timemodified must be refreshed on every toggle.
     */
    public function test_toggle_auto_item_refreshes_timemodified(): void {
        global $DB;

        $oldtime = time() - 3600;
        $id = $DB->insert_record('block_teacher_checklist', (object)[
            'courseid'     => $this->course->id,
            'userid'       => $this->teacher->id,
            'type'         => 'auto',
            'subtype'      => 'gradebook',
            'docid'        => -1,
            'status'       => 0,
            'timecreated'  => $oldtime,
            'timemodified' => $oldtime,
        ]);

        external::toggle_item_status(
            $this->course->id,
            'auto',
            'gradebook',
            -1,
            1
        );

        $record = $DB->get_record('block_teacher_checklist', ['id' => $id]);
        $this->assertGreaterThan(
            $oldtime,
            (int)$record->timemodified,
            'timemodified should be updated to a more recent timestamp.'
        );
    }
}
