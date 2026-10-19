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
 * PHPUnit tests for the privacy provider.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_teacher_checklist\privacy;

use advanced_testcase;
use context_course;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Tests for the block_teacher_checklist privacy provider.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_teacher_checklist\privacy\provider
 */
final class privacy_test extends advanced_testcase {
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
     * @param int $status Status code (0 pending, 1 done, 2 ignored).
     * @param string $title Item title.
     * @return int The inserted record id.
     */
    private function create_item(int $courseid, int $userid, int $status, string $title): int {
        global $DB;

        $now = time();

        return (int) $DB->insert_record('block_teacher_checklist', (object) [
            'courseid'     => $courseid,
            'userid'       => $userid,
            'type'         => 'manual',
            'subtype'      => '',
            'docid'        => 0,
            'title'        => $title,
            'status'       => $status,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * The metadata collection describes the plugin table.
     */
    public function test_get_metadata(): void {
        $collection = new collection('block_teacher_checklist');
        $result = provider::get_metadata($collection);

        $items = $result->get_collection();
        $this->assertCount(1, $items);
        $this->assertEquals('block_teacher_checklist', $items[0]->get_name());
    }

    /**
     * Every column of the table is declared, and every declaration has its language string.
     *
     * Comparing the whole sets fails when a column is added to the table without a privacy decision, which a
     * per-key assertion would not notice.
     */
    public function test_metadata_declares_every_column_of_the_table(): void {
        global $DB;

        $items = provider::get_metadata(new collection('block_teacher_checklist'))->get_collection();
        $declared = $items[0]->get_privacy_fields();

        $columns = array_diff(array_keys($DB->get_columns('block_teacher_checklist')), ['id']);
        $declaredcolumns = array_keys($declared);
        sort($columns);
        sort($declaredcolumns);
        $this->assertSame(array_values($columns), $declaredcolumns);

        foreach ($declared as $stringkey) {
            $this->assertTrue(
                get_string_manager()->string_exists($stringkey, 'block_teacher_checklist'),
                "Missing language string {$stringkey}"
            );
        }
    }

    /**
     * get_contexts_for_userid returns only courses where the user has items.
     */
    public function test_get_contexts_for_userid(): void {
        $user = $this->getDataGenerator()->create_user();
        $coursewith = $this->getDataGenerator()->create_course();
        $coursewithout = $this->getDataGenerator()->create_course();

        $this->create_item($coursewith->id, $user->id, 0, 'Has data');
        $this->create_item($coursewithout->id, $user->id + 1, 0, 'Other user');

        $contextlist = provider::get_contexts_for_userid($user->id);

        $contextids = $contextlist->get_contextids();
        $this->assertCount(1, $contextids);
        $this->assertEquals(context_course::instance($coursewith->id)->id, reset($contextids));
    }

    /**
     * get_users_in_context returns every user with data in the course.
     */
    public function test_get_users_in_context(): void {
        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $this->create_item($course->id, $usera->id, 0, 'A');
        $this->create_item($course->id, $userb->id, 1, 'B');

        $userlist = new userlist($context, 'block_teacher_checklist');
        provider::get_users_in_context($userlist);

        $userids = $userlist->get_userids();
        $this->assertCount(2, $userids);
        $this->assertContains((int) $usera->id, $userids);
        $this->assertContains((int) $userb->id, $userids);
    }

    /**
     * Exported data carries the items with localised, status-specific labels.
     */
    public function test_export_user_data_localises_status(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $this->create_item($course->id, $user->id, 0, 'Pending item');
        $this->create_item($course->id, $user->id, 1, 'Done item');
        $this->create_item($course->id, $user->id, 2, 'Ignored item');

        $contextlist = new approved_contextlist($user, 'block_teacher_checklist', [$context->id]);
        provider::export_user_data($contextlist);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data([get_string('pluginname', 'block_teacher_checklist')]);
        $this->assertCount(3, $data->items);

        $statusbytitle = [];
        foreach ($data->items as $item) {
            $statusbytitle[$item->title] = $item->status;
        }

        $this->assertEquals(get_string('status_pending', 'block_teacher_checklist'), $statusbytitle['Pending item']);
        $this->assertEquals(get_string('status_done', 'block_teacher_checklist'), $statusbytitle['Done item']);
        $this->assertEquals(get_string('status_ignored', 'block_teacher_checklist'), $statusbytitle['Ignored item']);
    }

    /**
     * delete_data_for_all_users_in_context removes every item in the course only.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();

        $this->create_item($course->id, $usera->id, 0, 'A');
        $this->create_item($course->id, $userb->id, 1, 'B');
        $this->create_item($othercourse->id, $usera->id, 0, 'Untouched');

        provider::delete_data_for_all_users_in_context(context_course::instance($course->id));

        $this->assertEquals(0, $DB->count_records('block_teacher_checklist', ['courseid' => $course->id]));
        $this->assertEquals(1, $DB->count_records('block_teacher_checklist', ['courseid' => $othercourse->id]));
    }

    /**
     * delete_data_for_user removes only the requesting user's items in the given context.
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $this->create_item($course->id, $usera->id, 0, 'Mine');
        $this->create_item($course->id, $userb->id, 0, 'Theirs');

        $contextlist = new approved_contextlist($usera, 'block_teacher_checklist', [$context->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertEquals(0, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'userid'   => $usera->id,
        ]));
        $this->assertEquals(1, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'userid'   => $userb->id,
        ]));
    }

    /**
     * delete_data_for_users removes only the listed users in the given context.
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $userc = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $this->create_item($course->id, $usera->id, 0, 'A');
        $this->create_item($course->id, $userb->id, 0, 'B');
        $this->create_item($course->id, $userc->id, 0, 'C');

        $userlist = new approved_userlist($context, 'block_teacher_checklist', [$usera->id, $userb->id]);
        provider::delete_data_for_users($userlist);

        $this->assertEquals(0, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'userid'   => $usera->id,
        ]));
        $this->assertEquals(0, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'userid'   => $userb->id,
        ]));
        $this->assertEquals(1, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'userid'   => $userc->id,
        ]));
    }

    /**
     * Inserts a course-wide state row (an ignored issue or the scan switch) set by a user.
     *
     * @param int $courseid Course the row belongs to.
     * @param int $userid User who set the state last.
     * @param string $type Row type, auto or config.
     * @param string $subtype Row subtype.
     * @param int $status Status code.
     */
    private function create_state_row(int $courseid, int $userid, string $type, string $subtype, int $status): void {
        global $DB;

        $DB->insert_record('block_teacher_checklist', (object) [
            'courseid'     => $courseid,
            'userid'       => $userid,
            'type'         => $type,
            'subtype'      => $subtype,
            'docid'        => 7,
            'status'       => $status,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Erasing a teacher keeps the course state shared with the other teachers.
     *
     * The ignored issue and the scan switch only record who set them last, so they are detached from the user
     * instead of deleted; the manual item written by another teacher is untouched even though this user was
     * the one who marked it as done.
     */
    public function test_delete_data_for_user_keeps_course_state_and_items_of_others(): void {
        global $DB;

        $teachera = $this->getDataGenerator()->create_user();
        $teacherb = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $itemofa = $this->create_item($course->id, $teachera->id, 0, 'Written by A');
        $itemofb = $this->create_item($course->id, $teacherb->id, 0, 'Written by B');
        $this->create_state_row($course->id, $teacherb->id, 'auto', 'mod_assign_nodate', 2);
        $this->create_state_row($course->id, $teacherb->id, 'config', 'scan_enabled', 0);

        // Teacher B marks the item of teacher A as done through the real web service.
        $this->setUser($teacherb);
        $this->getDataGenerator()->enrol_user($teacherb->id, $course->id, 'editingteacher');
        \block_teacher_checklist\external::toggle_item_status($course->id, 'manual', '', $itemofa, 1);

        $contextlist = new approved_contextlist($teacherb, 'block_teacher_checklist', [$context->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertFalse($DB->record_exists('block_teacher_checklist', ['id' => $itemofb]));
        $this->assertEquals(1, $DB->get_field('block_teacher_checklist', 'status', ['id' => $itemofa]));
        $this->assertEquals(2, $DB->get_field('block_teacher_checklist', 'status', [
            'courseid' => $course->id,
            'type' => 'auto',
        ]));
        $this->assertEquals(0, $DB->get_field('block_teacher_checklist', 'status', [
            'courseid' => $course->id,
            'type' => 'config',
        ]));
        $this->assertEquals(0, $DB->count_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'userid' => $teacherb->id,
        ]));
    }

    /**
     * The user-list form erases like the context-list one, in the requested course only.
     */
    public function test_delete_data_for_users_keeps_course_state(): void {
        global $DB;

        $teacher = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $mine = $this->create_item($course->id, $teacher->id, 0, 'Mine');
        $this->create_state_row($course->id, $teacher->id, 'config', 'scan_enabled', 0);
        $inothercourse = $this->create_item($othercourse->id, $teacher->id, 0, 'Other course');

        $userlist = new approved_userlist($context, 'block_teacher_checklist', [$teacher->id]);
        provider::delete_data_for_users($userlist);

        $this->assertFalse($DB->record_exists('block_teacher_checklist', ['id' => $mine]));
        $this->assertTrue($DB->record_exists('block_teacher_checklist', [
            'courseid' => $course->id,
            'type' => 'config',
            'userid' => 0,
        ]));
        $this->assertTrue($DB->record_exists('block_teacher_checklist', ['id' => $inothercourse]));
    }
}
