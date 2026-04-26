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
 * Unit tests for the scanner class.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_teacher_checklist\scanner
 */
final class scanner_test extends advanced_testcase {
    /** @var \stdClass Test course. */
    protected $course;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course(['visible' => 1]);
    }

    /**
     * Scanner must be active by default (no config record in DB).
     */
    public function test_is_active_returns_true_by_default(): void {
        $scanner = new scanner($this->course);
        $this->assertTrue($scanner->is_active());
    }

    /**
     * Scanner must be active when the config record explicitly sets status=1.
     */
    public function test_is_active_returns_true_when_status_is_one(): void {
        global $DB;
        $DB->insert_record('block_teacher_checklist', (object)[
            'courseid'     => $this->course->id,
            'userid'       => 2,
            'type'         => 'config',
            'subtype'      => 'scan_enabled',
            'docid'        => 0,
            'status'       => 1,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        $scanner = new scanner($this->course);
        $this->assertTrue($scanner->is_active());
    }

    /**
     * Scanner must be inactive when the config record sets status=0.
     */
    public function test_is_active_returns_false_when_disabled(): void {
        global $DB;
        $DB->insert_record('block_teacher_checklist', (object)[
            'courseid'     => $this->course->id,
            'userid'       => 2,
            'type'         => 'config',
            'subtype'      => 'scan_enabled',
            'docid'        => 0,
            'status'       => 0,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        $scanner = new scanner($this->course);
        $this->assertFalse($scanner->is_active());
    }

    /**
     * get_all_issues() must return an empty array when the scanner is inactive,
     * even if the course has real issues (e.g. is hidden).
     */
    public function test_get_all_issues_returns_empty_when_scanner_inactive(): void {
        global $DB;

        $this->course->visible = 0;
        $DB->set_field('course', 'visible', 0, ['id' => $this->course->id]);

        $DB->insert_record('block_teacher_checklist', (object)[
            'courseid'     => $this->course->id,
            'userid'       => 2,
            'type'         => 'config',
            'subtype'      => 'scan_enabled',
            'docid'        => 0,
            'status'       => 0,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        $scanner = new scanner($this->course);
        $this->assertEmpty($scanner->get_all_issues());
    }

    /**
     * A hidden course must produce an issue with subtype='course' and severity='high'.
     */
    public function test_scan_detects_hidden_course(): void {
        global $DB;

        $this->course->visible = 0;
        $DB->set_field('course', 'visible', 0, ['id' => $this->course->id]);

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $courseissues = array_filter($issues, fn($i) => $i['subtype'] === 'course');
        $this->assertNotEmpty($courseissues, 'Expected a course-visibility issue for a hidden course.');

        $issue = reset($courseissues);
        $this->assertEquals('high', $issue['severity']);
        $this->assertEquals('auto', $issue['type']);
        $this->assertEquals(0, $issue['docid']);
    }

    /**
     * A visible course must NOT produce a course-visibility issue.
     */
    public function test_scan_does_not_flag_visible_course(): void {
        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $courseissues = array_filter($issues, fn($i) => $i['subtype'] === 'course');
        $this->assertEmpty($courseissues, 'Visible course should not produce a visibility issue.');
    }

    /**
     * A course with no gradable activities must produce a 'gradebook' issue.
     */
    public function test_scan_detects_course_with_no_gradebook_items(): void {
        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $gbissues = array_filter($issues, fn($i) => $i['subtype'] === 'gradebook');
        $this->assertNotEmpty($gbissues, 'Expected a gradebook issue when no grade items exist.');

        $issue = reset($gbissues);
        $this->assertEquals(scanner::GRADEBOOK_VIRTUAL_DOCID, $issue['docid']);
    }

    /**
     * A course that has a graded assignment must NOT produce a gradebook issue.
     */
    public function test_scan_no_gradebook_issue_when_assignment_exists(): void {
        $this->getDataGenerator()->create_module('assign', ['course' => $this->course->id]);

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $gbissues = array_filter($issues, fn($i) => $i['subtype'] === 'gradebook');
        $this->assertEmpty($gbissues, 'Course with a gradable activity should not produce a gradebook issue.');
    }

    /**
     * get_status() must return 0 (pending) when there is no DB record for the key.
     */
    public function test_get_status_returns_zero_when_no_record(): void {
        $scanner = new scanner($this->course);

        $method = new \ReflectionMethod($scanner, 'get_status');
        $method->setAccessible(true);

        $this->assertEquals(0, $method->invoke($scanner, 'course', 0));
        $this->assertEquals(0, $method->invoke($scanner, 'gradebook', scanner::GRADEBOOK_VIRTUAL_DOCID));
    }

    /**
     * get_status() must return the correct status loaded from the DB at construction time.
     */
    public function test_get_status_returns_correct_status_from_db(): void {
        global $DB;

        $DB->insert_record('block_teacher_checklist', (object)[
            'courseid'     => $this->course->id,
            'userid'       => 2,
            'type'         => 'auto',
            'subtype'      => 'course',
            'docid'        => 0,
            'status'       => 2,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        $scanner = new scanner($this->course);

        $method = new \ReflectionMethod($scanner, 'get_status');
        $method->setAccessible(true);

        $this->assertEquals(2, $method->invoke($scanner, 'course', 0));
    }

    /**
     * An issue whose DB record has status=2 (ignored) must be returned by get_all_issues()
     * with status=2; the scanner does not filter by status, that is the caller's job.
     */
    public function test_ignored_issue_has_status_two_in_results(): void {
        global $DB;

        $this->course->visible = 0;
        $DB->set_field('course', 'visible', 0, ['id' => $this->course->id]);

        $DB->insert_record('block_teacher_checklist', (object)[
            'courseid'     => $this->course->id,
            'userid'       => 2,
            'type'         => 'auto',
            'subtype'      => 'course',
            'docid'        => 0,
            'status'       => 2,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $courseissues = array_filter($issues, fn($i) => $i['subtype'] === 'course');
        $this->assertNotEmpty($courseissues);

        $issue = reset($courseissues);
        $this->assertEquals(
            2,
            $issue['status'],
            'Issue should carry the ignored status (2) loaded from the DB cache.'
        );
    }

    /**
     * make_issue() must return all required keys with the correct values.
     */
    public function test_make_issue_returns_correct_structure(): void {
        $scanner = new scanner($this->course);

        $method = new \ReflectionMethod($scanner, 'make_issue');
        $method->setAccessible(true);

        $url = new \moodle_url('/course/edit.php', ['id' => $this->course->id]);
        $issue = $method->invoke($scanner, 'course', 0, 'Test title', $url, 'i/hide', 0, 'high');

        $this->assertEquals('auto', $issue['type']);
        $this->assertEquals('course', $issue['subtype']);
        $this->assertEquals(0, $issue['docid']);
        $this->assertEquals('Test title', $issue['title']);
        $this->assertEquals(0, $issue['status']);
        $this->assertEquals('high', $issue['severity']);
        $this->assertArrayHasKey('url', $issue);
        $this->assertArrayHasKey('icon', $issue);
    }

    /**
     * make_issue() must default severity to 'normal' when not specified.
     */
    public function test_make_issue_default_severity_is_normal(): void {
        $scanner = new scanner($this->course);

        $method = new \ReflectionMethod($scanner, 'make_issue');
        $method->setAccessible(true);

        $url = new \moodle_url('/');
        $issue = $method->invoke($scanner, 'gradebook', -1, 'Title', $url, 'i/grades', 0);

        $this->assertEquals('normal', $issue['severity']);
    }

    /**
     * An assignment with no due date must produce a 'mod_assign_nodate' issue.
     */
    public function test_scan_detects_assignment_without_due_date(): void {
        $this->getDataGenerator()->create_module('assign', [
            'course'  => $this->course->id,
            'duedate' => 0,
            'intro'   => 'Has a description',
        ]);

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $nodate = array_filter($issues, fn($i) => $i['subtype'] === 'mod_assign_nodate');
        $this->assertNotEmpty($nodate, 'Assignment without due date should be flagged.');
    }

    /**
     * An assignment without a description must produce a 'mod_assign_nodesc' issue.
     */
    public function test_scan_detects_assignment_without_description(): void {
        global $DB;

        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'  => $this->course->id,
            'duedate' => time() + WEEKSECS,
        ]);
        $DB->set_field('assign', 'intro', '', ['id' => $assign->id]);

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $nodesc = array_filter($issues, fn($i) => $i['subtype'] === 'mod_assign_nodesc');
        $this->assertNotEmpty($nodesc, 'Assignment without description should be flagged.');
    }

    /**
     * A fully configured assignment must not produce configuration issues.
     */
    public function test_scan_does_not_flag_well_configured_assignment(): void {
        $this->getDataGenerator()->create_module('assign', [
            'course'  => $this->course->id,
            'duedate' => time() + WEEKSECS,
            'intro'   => 'Complete this task by the due date.',
        ]);

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $configissues = array_filter($issues, fn($i) => in_array($i['subtype'], [
            'mod_assign_nodate',
            'mod_assign_nodesc',
        ]));
        $this->assertEmpty($configissues, 'Well-configured assignment should not produce configuration issues.');
    }

    /**
     * A course without a summary must produce a 'course_nosummary' issue.
     */
    public function test_scan_detects_course_without_summary(): void {
        global $DB;
        $DB->set_field('course', 'summary', '', ['id' => $this->course->id]);
        $this->course->summary = '';

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $found = array_filter($issues, fn($i) => $i['subtype'] === 'course_nosummary');
        $this->assertNotEmpty($found, 'Course without summary should be flagged.');
    }

    /**
     * A course with a summary must NOT produce a 'course_nosummary' issue.
     */
    public function test_scan_does_not_flag_course_with_summary(): void {
        global $DB;
        $DB->set_field('course', 'summary', 'A valid course description.', ['id' => $this->course->id]);
        $this->course->summary = 'A valid course description.';

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $found = array_filter($issues, fn($i) => $i['subtype'] === 'course_nosummary');
        $this->assertEmpty($found, 'Course with summary should not be flagged.');
    }

    /**
     * A course without an end date must produce a 'course_noenddate' issue.
     */
    public function test_scan_detects_course_without_end_date(): void {
        global $DB;
        $DB->set_field('course', 'enddate', 0, ['id' => $this->course->id]);
        $this->course->enddate = 0;

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $found = array_filter($issues, fn($i) => $i['subtype'] === 'course_noenddate');
        $this->assertNotEmpty($found, 'Course without end date should be flagged.');
    }

    /**
     * A course with an end date set must NOT produce a 'course_noenddate' issue.
     */
    public function test_scan_does_not_flag_course_with_end_date(): void {
        global $DB;
        $DB->set_field('course', 'enddate', time() + YEARSECS, ['id' => $this->course->id]);
        $this->course->enddate = time() + YEARSECS;

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $found = array_filter($issues, fn($i) => $i['subtype'] === 'course_noenddate');
        $this->assertEmpty($found, 'Course with end date should not be flagged.');
    }

    /**
     * A forum without a description must produce a 'mod_forum_nodesc' issue.
     */
    public function test_scan_detects_forum_without_description(): void {
        global $DB;
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $this->course->id]);
        $DB->set_field('forum', 'intro', '', ['id' => $forum->id]);

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $found = array_filter($issues, fn($i) => $i['subtype'] === 'mod_forum_nodesc');
        $this->assertNotEmpty($found, 'Forum without description should be flagged.');
    }

    /**
     * A forum with a description must NOT produce a 'mod_forum_nodesc' issue.
     */
    public function test_scan_does_not_flag_forum_with_description(): void {
        $this->getDataGenerator()->create_module('forum', [
            'course' => $this->course->id,
            'intro'  => 'Use this forum to discuss weekly topics.',
        ]);

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $found = array_filter($issues, fn($i) => $i['subtype'] === 'mod_forum_nodesc');
        $this->assertEmpty($found, 'Forum with description should not be flagged.');
    }

    /**
     * The Announcements (news) forum must never be flagged as empty,
     * even when it has no discussion topics.
     */
    public function test_scan_forum_does_not_flag_news_forum_as_empty(): void {
        $this->getDataGenerator()->create_module('forum', [
            'course' => $this->course->id,
            'type'   => 'news',
        ]);

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $forumissues = array_filter($issues, fn($i) => $i['subtype'] === 'mod_forum_empty');
        $this->assertEmpty($forumissues, 'News/Announcements forum must not be flagged as empty.');
    }

    /**
     * The Announcements (news) forum must never be flagged for missing completion tracking.
     */
    public function test_scan_completion_disabled_does_not_flag_news_forum(): void {
        $this->getDataGenerator()->create_module('forum', [
            'course'     => $this->course->id,
            'type'       => 'news',
            'completion' => COMPLETION_TRACKING_NONE,
        ]);

        $scanner = new scanner($this->course);
        $issues = $scanner->get_all_issues();

        $compissues = array_filter($issues, fn($i) => $i['subtype'] === 'completion_disabled');
        $this->assertEmpty(
            $compissues,
            'News/Announcements forum must not be flagged for disabled completion tracking.'
        );
    }
}
