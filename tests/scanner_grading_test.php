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
 * PHPUnit tests for the grading backlog checks of the scanner.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_teacher_checklist;

use advanced_testcase;
use mod_assign_test_generator;
use mod_quiz\quiz_settings;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Tests that pending assignment submissions and quiz attempts are counted like the core does.
 *
 * Every scenario is built through the real mod_assign and mod_quiz APIs, never by inserting rows,
 * so the tests follow the way each module really stores submissions, team rows and previews.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_teacher_checklist\scanner
 */
final class scanner_grading_test extends advanced_testcase {
    use mod_assign_test_generator;

    /** @var \stdClass Test course. */
    private $course;

    /** @var \stdClass Teacher enrolled in the course. */
    private $teacher;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();
        $this->teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
    }

    /**
     * Returns the grading backlog issues of one type found by a fresh scan.
     *
     * @param string $subtype Issue subtype, 'mod_assign_grading' or 'mod_quiz_grading'.
     * @return array The matching issues.
     */
    private function grading_issues(string $subtype): array {
        $scanner = new scanner($this->course);
        return array_values(array_filter(
            $scanner->get_all_issues(),
            fn(array $issue): bool => $issue['subtype'] === $subtype
        ));
    }

    /**
     * Creates an essay question and adds it to a quiz.
     *
     * @param \stdClass $quiz The quiz record.
     */
    private function add_essay_question(\stdClass $quiz): void {
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('essay', null, ['category' => $category->id]);
        quiz_add_quiz_question($question->id, $quiz, 0, 1);
        quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
    }

    /**
     * Starts and finishes a quiz attempt for a user through the quiz API.
     *
     * @param \stdClass $quiz The quiz record.
     * @param \stdClass $user The user taking the attempt, a preview when the user may preview the quiz.
     */
    private function finish_attempt(\stdClass $quiz, \stdClass $user): void {
        $this->setUser($user);
        $quizobj = quiz_settings::create($quiz->id, $user->id);
        $attempt = quiz_prepare_and_start_new_attempt($quizobj, 1, null);
        $this->getDataGenerator()->get_plugin_generator('mod_quiz')->submit_responses(
            $attempt->id,
            [1 => 'My essay answer'],
            false,
            true
        );
    }

    /**
     * Grades a student's submission through the assignment API.
     *
     * Moodle 5.2 renamed the core helper of the same purpose, so the version-independent part is kept here.
     *
     * @param \assign $assign The assignment.
     * @param \stdClass $student The student being graded.
     * @param float $grade The grade to give.
     */
    private function grade_student(\assign $assign, \stdClass $student, float $grade): void {
        global $DB;

        $this->setUser($this->teacher);
        // Move the submission slightly into the past, so the grade is not recorded in the same second.
        $DB->execute(
            'UPDATE {assign_submission} SET timemodified = timemodified - 1 WHERE userid = :userid',
            ['userid' => $student->id]
        );
        $assign->testable_apply_grade_to_user((object) ['grade' => $grade], $student->id, 0);
    }

    /**
     * A submission waits for grading until the teacher grades it.
     */
    public function test_assignment_submission_is_pending_until_graded(): void {
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $assign = $this->create_instance($this->course);
        $this->add_submission($student, $assign);
        $this->submit_for_grading($student, $assign);

        $issues = $this->grading_issues('mod_assign_grading');
        $this->assertCount(1, $issues);

        $this->grade_student($assign, $student, 80.0);

        $this->assertCount(0, $this->grading_issues('mod_assign_grading'));
    }

    /**
     * The shared team row (userid 0) must not keep the alert alive after every member is graded.
     */
    public function test_team_submission_stops_being_pending_once_members_are_graded(): void {
        $student1 = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $group = $this->getDataGenerator()->create_group(['courseid' => $this->course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $student1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $student2->id]);

        $assign = $this->create_instance($this->course, [
            'teamsubmission' => 1,
            'requireallteammemberssubmit' => 0,
        ]);
        $this->add_submission($student1, $assign);
        $this->submit_for_grading($student1, $assign);

        $this->assertNotEmpty($this->grading_issues('mod_assign_grading'));

        $this->grade_student($assign, $student1, 70.0);
        $this->grade_student($assign, $student2, 70.0);

        $this->assertCount(0, $this->grading_issues('mod_assign_grading'));
    }

    /**
     * A submission edited after it was graded needs a new grading.
     */
    public function test_assignment_is_pending_again_when_resubmitted_after_grading(): void {
        global $DB;

        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $assign = $this->create_instance($this->course);
        $this->add_submission($student, $assign);
        $this->submit_for_grading($student, $assign);
        $this->grade_student($assign, $student, 60.0);
        $this->assertCount(0, $this->grading_issues('mod_assign_grading'));

        $DB->set_field('assign_submission', 'timemodified', time() + 60, [
            'assignment' => $assign->get_instance()->id,
            'userid' => $student->id,
        ]);

        $this->assertCount(1, $this->grading_issues('mod_assign_grading'));
    }

    /**
     * A student whose enrolment is suspended does not count, like in the assignment grading table.
     */
    public function test_assignment_ignores_submissions_of_suspended_students(): void {
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $this->course->id, 'student');
        $assign = $this->create_instance($this->course);
        $this->add_submission($student, $assign);
        $this->submit_for_grading($student, $assign);
        $this->assertCount(1, $this->grading_issues('mod_assign_grading'));

        $this->getDataGenerator()->enrol_user(
            $student->id,
            $this->course->id,
            'student',
            'manual',
            0,
            0,
            ENROL_USER_SUSPENDED
        );

        $this->assertCount(0, $this->grading_issues('mod_assign_grading'));
    }

    /**
     * A finished student attempt with an ungraded essay waits for manual grading.
     */
    public function test_quiz_student_attempt_with_essay_is_pending(): void {
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->add_essay_question($quiz);

        $this->finish_attempt($quiz, $student);

        $this->assertCount(1, $this->grading_issues('mod_quiz_grading'));
    }

    /**
     * The teacher's own preview of a quiz with an essay must not raise a grading alert.
     */
    public function test_quiz_teacher_preview_is_not_pending(): void {
        global $DB;

        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->add_essay_question($quiz);

        $this->finish_attempt($quiz, $this->teacher);

        $this->assertTrue($DB->record_exists('quiz_attempts', ['quiz' => $quiz->id, 'preview' => 1]));
        $this->assertCount(0, $this->grading_issues('mod_quiz_grading'));
    }

    /**
     * A hidden quiz does not raise a grading alert even with an attempt waiting.
     */
    public function test_quiz_hidden_from_students_is_not_reported(): void {
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id, 'visible' => 0]);
        $this->add_essay_question($quiz);

        $this->finish_attempt($quiz, $student);

        $this->assertCount(0, $this->grading_issues('mod_quiz_grading'));
    }
}
