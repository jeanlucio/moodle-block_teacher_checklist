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

defined('MOODLE_INTERNAL') || die();

use stdClass;
use moodle_url;
use context_course;

/**
 * Scanner class to detect issues in the course.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scanner {

    /** @var stdClass The course object. */
    protected $course;

    /** @var \course_modinfo The module info for the course. */
    protected $modinfo;

    /** @var array List of ignored items indexed by unique key. */
    protected $ignoreditems;

    /**
     * Constructor.
     *
     * @param stdClass $course The course object.
     */
    public function __construct(stdClass $course) {
        global $DB;
        $this->course = $course;
        $this->modinfo = get_fast_modinfo($course);
        
        // Load ignored items efficiently.
        // We construct a key "subtype-docid" in PHP to avoid complex cross-db SQL concatenation.
        // FIX: Added 'id' as the first column to ensure unique array keys in get_records return.
        $records = $DB->get_records('block_teacher_checklist', [
            'courseid' => $course->id,
            'type' => 'auto',
        ], '', 'id, subtype, docid, status');

        $this->ignoreditems = [];
        foreach ($records as $r) {
            $key = $r->subtype . '-' . $r->docid;
            $this->ignoreditems[$key] = (int)$r->status;
        }
    }

    /**
     * Checks if automatic scanning is enabled for this course.
     *
     * @return bool True if enabled (default), false otherwise.
     */
    public function is_active(): bool {
        global $DB;
        
        $record = $DB->get_record('block_teacher_checklist', [
            'courseid' => $this->course->id,
            'type' => 'config',
            'subtype' => 'scan_enabled',
        ]);

        // Default is TRUE (1) if no record exists.
        if (!$record) {
            return true;
        }

        return (int)$record->status === 1;
    }

    /**
     * Main method to get all detected issues.
     *
     * @return array List of issues.
     */
    public function get_all_issues(): array {
        if (!$this->is_active()) {
            return [];
        }

        $issues = [];
        
        // Merge results from all scanners.
        $issues = array_merge($issues, $this->scan_course_visibility());
        $issues = array_merge($issues, $this->scan_no_evaluations());
        $issues = array_merge($issues, $this->scan_assignments_issues());
        $issues = array_merge($issues, $this->scan_quiz_grading());
        $issues = array_merge($issues, $this->scan_forum_issues());
        $issues = array_merge($issues, $this->scan_quiz_issues());
        $issues = array_merge($issues, $this->scan_completion_disabled());
        $issues = array_merge($issues, $this->scan_empty_sections());

        return $issues;
    }

    /**
     * Check if course is hidden.
     */
    protected function scan_course_visibility(): array {
        $issues = [];
        if ($this->course->visible == 0) {
            $status = $this->get_status('course', 0);
            $issues[] = $this->make_issue(
                'course',
                0,
                get_string('issue_course_hidden', 'block_teacher_checklist'),
                new moodle_url('/course/edit.php', ['id' => $this->course->id]),
                new moodle_url('/pix/i/hide.png'),
                $status,
                'high'
            );
        }
        return $issues;
    }

    /**
     * Check if course has no gradebook items.
     */
    protected function scan_no_evaluations(): array {
        global $DB;
        $issues = [];
        
        $count = $DB->count_records_select('grade_items', 'courseid = ? AND itemtype = ?', [$this->course->id, 'mod']);

        if ($count == 0) {
            $status = $this->get_status('course', 999); // 999 as a virtual ID for gradebook.
            $issues[] = $this->make_issue(
                'course',
                999,
                get_string('issue_no_evaluations', 'block_teacher_checklist'),
                new moodle_url('/grade/edit/tree/index.php', ['id' => $this->course->id]),
                new moodle_url('/pix/i/grades.png'),
                $status
            );
        }
        return $issues;
    }

    /**
     * Check for assignment configuration issues and grading backlog.
     */
    protected function scan_assignments_issues(): array {
        global $DB;
        $issues = [];

        // Fetch assignments with a subquery to count pending submissions.
        $sql = "SELECT a.id, a.name, a.duedate, a.intro,
                       (SELECT COUNT(s.id)
                        FROM {assign_submission} s
                        LEFT JOIN {assign_grades} g ON s.assignment = g.assignment AND s.userid = g.userid
                        WHERE s.assignment = a.id
                          AND s.status = 'submitted'
                          AND (g.grade IS NULL OR g.grade = -1)
                       ) as pending_grading
                FROM {assign} a
                WHERE a.course = ?";
        
        $assigns = $DB->get_records_sql($sql, [$this->course->id]);

        foreach ($assigns as $assign) {
            $cm = $this->get_cm_by_instance('assign', $assign->id);
            if (!$cm || !$cm->uservisible) {
                continue;
            }

            // check 1: Grading backlog.
            if ($assign->pending_grading > 0) {
                $status = $this->get_status('mod_assign_grading', $assign->id);
                $title = get_string('issue_assign_grading', 'block_teacher_checklist', $assign->pending_grading) .
                         ' ' . $assign->name;
                
                $issues[] = $this->make_issue(
                    'mod_assign_grading',
                    $assign->id,
                    $title,
                    new moodle_url('/mod/assign/view.php', ['id' => $cm->id, 'action' => 'grading']),
                    $cm->get_icon_url(),
                    $status,
                    'high'
                );
            }

            // check 2: No due date.
            if ($assign->duedate == 0) {
                $status = $this->get_status('mod_assign_nodate', $assign->id);
                $issues[] = $this->make_issue(
                    'mod_assign_nodate',
                    $assign->id,
                    get_string('issue_assign_nodate', 'block_teacher_checklist', $assign->name),
                    new moodle_url('/course/modedit.php', ['update' => $cm->id, 'return' => 1]),
                    $cm->get_icon_url(),
                    $status
                );
            }

            // check 3: No description.
            if (empty(strip_tags($assign->intro))) {
                $status = $this->get_status('mod_assign_nodesc', $assign->id);
                $issues[] = $this->make_issue(
                    'mod_assign_nodesc',
                    $assign->id,
                    get_string('issue_assign_nodesc', 'block_teacher_checklist', $assign->name),
                    new moodle_url('/course/modedit.php', ['update' => $cm->id, 'return' => 1]),
                    $cm->get_icon_url(),
                    $status
                );
            }
        }

        return $issues;
    }

    /**
     * Check forum issues.
     */
    protected function scan_forum_issues(): array {
        global $DB;
        $issues = [];

        $sql = "SELECT f.id, f.name, f.intro,
                       (SELECT COUNT(fd.id) FROM {forum_discussions} fd WHERE fd.forum = f.id) as post_count
                FROM {forum} f
                WHERE f.course = ?";
        
        $forums = $DB->get_records_sql($sql, [$this->course->id]);

        foreach ($forums as $forum) {
            $cm = $this->get_cm_by_instance('forum', $forum->id);
            if (!$cm || !$cm->uservisible) {
                continue;
            }

            if ($forum->post_count == 0) {
                $status = $this->get_status('mod_forum_empty', $forum->id);
                $issues[] = $this->make_issue(
                    'mod_forum_empty',
                    $forum->id,
                    get_string('issue_forum_empty', 'block_teacher_checklist', $forum->name),
                    new moodle_url('/mod/forum/view.php', ['id' => $cm->id]),
                    $cm->get_icon_url(),
                    $status
                );
            }
        }
        return $issues;
    }

    /**
     * Check Quiz structure issues.
     */
    protected function scan_quiz_issues(): array {
        global $DB;
        $issues = [];

        $sql = "SELECT q.id, q.name, q.timeclose, q.timelimit,
                       (SELECT COUNT(qs.id) FROM {quiz_slots} qs WHERE qs.quizid = q.id) as question_count
                FROM {quiz} q
                WHERE q.course = ?";
        
        $quizzes = $DB->get_records_sql($sql, [$this->course->id]);

        foreach ($quizzes as $quiz) {
            $cm = $this->get_cm_by_instance('quiz', $quiz->id);
            if (!$cm || !$cm->uservisible) {
                continue;
            }

            if ($quiz->question_count == 0) {
                $status = $this->get_status('mod_quiz_empty', $quiz->id);
                $issues[] = $this->make_issue(
                    'mod_quiz_empty',
                    $quiz->id,
                    get_string('issue_quiz_empty', 'block_teacher_checklist', $quiz->name),
                    new moodle_url('/mod/quiz/edit.php', ['cmid' => $cm->id]),
                    $cm->get_icon_url(),
                    $status
                );
            }

            if ($quiz->timeclose == 0 && $quiz->timelimit == 0) {
                $status = $this->get_status('mod_quiz_insecure', $quiz->id);
                $issues[] = $this->make_issue(
                    'mod_quiz_insecure',
                    $quiz->id,
                    get_string('issue_quiz_insecure', 'block_teacher_checklist', $quiz->name),
                    new moodle_url('/course/modedit.php', ['update' => $cm->id, 'return' => 1]),
                    $cm->get_icon_url(),
                    $status
                );
            }
        }
        return $issues;
    }

    /**
     * Check Quiz manual grading backlog.
     */
    protected function scan_quiz_grading(): array {
        global $DB;
        $issues = [];

        $sql = "SELECT q.id, q.name,
                       (SELECT COUNT(qa.id)
                        FROM {quiz_attempts} qa
                        WHERE qa.quiz = q.id
                          AND qa.state = 'finished'
                          AND qa.sumgrades IS NULL
                       ) as pending_count
                FROM {quiz} q
                WHERE q.course = ?";

        $quizzes = $DB->get_records_sql($sql, [$this->course->id]);

        foreach ($quizzes as $quiz) {
            if ($quiz->pending_count > 0) {
                $cm = $this->get_cm_by_instance('quiz', $quiz->id);
                if (!$cm || !$cm->uservisible) {
                    continue;
                }

                $status = $this->get_status('mod_quiz_grading', $quiz->id);
                $title = get_string('issue_quiz_grading', 'block_teacher_checklist', $quiz->pending_count) .
                         ' ' . $quiz->name;

                $issues[] = $this->make_issue(
                    'mod_quiz_grading',
                    $quiz->id,
                    $title,
                    new moodle_url('/mod/quiz/report.php', ['id' => $cm->id, 'mode' => 'overview']),
                    $cm->get_icon_url(),
                    $status,
                    'high'
                );
            }
        }
        return $issues;
    }

    /**
     * Check for activities with completion disabled (where it might be expected).
     */
    protected function scan_completion_disabled(): array {
        $issues = [];
        foreach ($this->modinfo->cms as $cm) {
            if ($cm->modname == 'label' || !$cm->uservisible) {
                continue;
            }

            if ($cm->completion == COMPLETION_TRACKING_NONE) {
                $status = $this->get_status('completion_disabled', $cm->id);
                $issues[] = $this->make_issue(
                    'completion_disabled',
                    $cm->id,
                    get_string('issue_completion_disabled', 'block_teacher_checklist', $cm->name),
                    new moodle_url('/course/modedit.php', ['update' => $cm->id, 'return' => 1]),
                    $cm->get_icon_url(),
                    $status
                );
            }
        }
        return $issues;
    }

    /**
     * Check for empty sections that are visible.
     */
    protected function scan_empty_sections(): array {
        global $OUTPUT;
        
        $issues = [];
        $sections = $this->modinfo->get_section_info_all();

        foreach ($sections as $section) {
            if ($section->section == 0) {
                continue;
            }

            if ($section->visible && empty($section->sequence) && empty($section->summary)) {
                $status = $this->get_status('section', $section->id);
                $sectionname = get_section_name($this->course, $section);
                
                // Using standard folder icon as a placeholder for section.
                $icon = $OUTPUT->image_url('i/folder');

                $issues[] = $this->make_issue(
                    'section',
                    $section->id,
                    get_string('issue_section_empty', 'block_teacher_checklist', $sectionname),
                    new moodle_url('/course/editsection.php', ['id' => $section->id]),
                    $icon,
                    $status
                );
            }
        }
        return $issues;
    }

    /**
     * Helper to retrieve CM object.
     */
    protected function get_cm_by_instance($modname, $instanceid) {
        $instances = $this->modinfo->get_instances_of($modname);
        return $instances[$instanceid] ?? null;
    }

    /**
     * Helper to determine status from cached ignored items.
     */
    protected function get_status($subtype, $docid): int {
        $key = $subtype . '-' . $docid;
        return $this->ignoreditems[$key] ?? 0;
    }

    /**
     * Helper to build the issue array structure.
     */
    protected function make_issue($subtype, $docid, $title, $url, $icon, $status, $severity = 'normal'): array {
        return [
            'type' => 'auto',
            'subtype' => $subtype,
            'docid' => $docid,
            'title' => $title,
            'url' => $url,
            'icon' => $icon,
            'status' => $status,
            'severity' => $severity,
        ];
    }
}
