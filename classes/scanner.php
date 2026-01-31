<?php
namespace block_teacher_checklist;

defined('MOODLE_INTERNAL') || die();

class scanner {
    protected $course;
    protected $modinfo;
    protected $ignored_items;

    public function __construct($course) {
        global $DB;
        $this->course = $course;
        $this->modinfo = get_fast_modinfo($course);

        $sql = "SELECT CONCAT(subtype, '-', docid) AS unique_key, status 
                FROM {block_teacher_checklist} 
                WHERE courseid = ? AND type = 'auto'";
        
        $this->ignored_items = $DB->get_records_sql_menu($sql, [$course->id]);
    }

    /**
     * Verifica se o monitoramento automático está ligado para este curso
     */
    public function is_active() {
        global $DB;
        // Procura um registro de configuração na tabela
        $record = $DB->get_record('block_teacher_checklist', [
            'courseid' => $this->course->id,
            'type' => 'config',
            'subtype' => 'scan_enabled'
        ]);
        
        // Se não existir registro, o padrão é LIGADO (true)
        // Se existir, respeita o status (1 = ligado, 0 = desligado)
        return (!$record || $record->status == 1);
    }

    public function get_all_issues() {

    if (!$this->is_active()) {
            return [];
        }
        
        $issues = [];
        
        // GERAIS
        $issues = array_merge($issues, $this->scan_course_visibility());
        $issues = array_merge($issues, $this->scan_no_evaluations());
        
        // CORREÇÃO PENDENTE
        $issues = array_merge($issues, $this->scan_assignments_issues());
        $issues = array_merge($issues, $this->scan_quiz_grading());
        // Lição removida para evitar erros de banco de dados
        
        // CONFIGURAÇÃO
        $issues = array_merge($issues, $this->scan_forum_issues());
        $issues = array_merge($issues, $this->scan_quiz_issues());        
        $issues = array_merge($issues, $this->scan_completion_disabled());
        $issues = array_merge($issues, $this->scan_empty_sections());
        
        return $issues;
    }

    protected function scan_course_visibility() {
        $issues = [];
        if ($this->course->visible == 0) {
            $status = $this->get_status('course', 0);
            $issues[] = $this->make_issue('course', 0, 
                get_string('issue_course_hidden', 'block_teacher_checklist'), 
                '/course/edit.php?id='.$this->course->id, 
                new \moodle_url('/pix/i/hide.png'), $status);
        }
        return $issues;
    }

    protected function scan_no_evaluations() {
        global $DB;
        $issues = [];
        $count = $DB->count_records_select('grade_items', 
            'courseid = ? AND itemtype = ?', 
            [$this->course->id, 'mod']);

        if ($count == 0) {
            $status = $this->get_status('course', 'gradebook');
            $issues[] = $this->make_issue('course', 'gradebook',
                get_string('issue_no_evaluations', 'block_teacher_checklist'),
                '/grade/edit/tree/index.php?id='.$this->course->id,
                new \moodle_url('/pix/i/grades.png'), $status);
        }
        return $issues;
    }
    
    protected function scan_assignments_issues() {
        global $DB;
        $issues = [];
        
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
            if (!$cm || !$cm->visible) continue;

            if ($assign->pending_grading > 0) {
                $status = $this->get_status('mod_assign_grading', $assign->id);
                $issues[] = $this->make_issue('mod_assign_grading', $assign->id,
                    get_string('issue_assign_grading', 'block_teacher_checklist', $assign->pending_grading) . $assign->name,
                    '/mod/assign/view.php?id='.$cm->id.'&action=grading',
                    $cm->get_icon_url(), $status, 'high');
            }

            if ($assign->duedate == 0) { 
                $status = $this->get_status('mod_assign_nodate', $assign->id);
                $issues[] = $this->make_issue('mod_assign_nodate', $assign->id,
                    get_string('issue_assign_nodate', 'block_teacher_checklist', $assign->name),
                    '/course/modedit.php?update='.$cm->id.'&return=1',
                    $cm->get_icon_url(), $status);
            }

            if (empty(strip_tags($assign->intro))) {
                $status = $this->get_status('mod_assign_nodesc', $assign->id);
                $issues[] = $this->make_issue('mod_assign_nodesc', $assign->id,
                    get_string('issue_assign_nodesc', 'block_teacher_checklist', $assign->name),
                    '/course/modedit.php?update='.$cm->id.'&return=1',
                    $cm->get_icon_url(), $status);
            }
        }
        return $issues;
    }

    protected function scan_forum_issues() {
        global $DB;
        $issues = [];
        $sql = "SELECT f.id, f.name, f.intro, f.type,
                       (SELECT COUNT(fd.id) FROM {forum_discussions} fd WHERE fd.forum = f.id) as post_count
                FROM {forum} f 
                WHERE f.course = ?";
        $forums = $DB->get_records_sql($sql, [$this->course->id]);

        foreach ($forums as $forum) {
            $cm = $this->get_cm_by_instance('forum', $forum->id);
            if (!$cm || !$cm->visible) continue;

            if ($forum->post_count == 0) {
                $status = $this->get_status('mod_forum_empty', $forum->id);
                $issues[] = $this->make_issue('mod_forum_empty', $forum->id,
                    get_string('issue_forum_empty', 'block_teacher_checklist', $forum->name),
                    '/mod/forum/view.php?id='.$cm->id,
                    $cm->get_icon_url(), $status);
            }

            if (empty(strip_tags($forum->intro))) {
                $status = $this->get_status('mod_forum_nodesc', $forum->id);
                $issues[] = $this->make_issue('mod_forum_nodesc', $forum->id,
                    get_string('issue_forum_nodesc', 'block_teacher_checklist', $forum->name),
                    '/course/modedit.php?update='.$cm->id.'&return=1',
                    $cm->get_icon_url(), $status);
            }
        }
        return $issues;
    }

    protected function scan_quiz_issues() {
        global $DB;
        $issues = [];
        $sql = "SELECT q.id, q.name, q.intro, q.timeclose, q.timelimit,
                       (SELECT COUNT(qs.id) FROM {quiz_slots} qs WHERE qs.quizid = q.id) as question_count
                FROM {quiz} q 
                WHERE q.course = ?";
        $quizzes = $DB->get_records_sql($sql, [$this->course->id]);

        foreach ($quizzes as $quiz) {
            $cm = $this->get_cm_by_instance('quiz', $quiz->id);
            if (!$cm || !$cm->visible) continue;

            if ($quiz->question_count == 0) {
                $status = $this->get_status('mod_quiz_empty', $quiz->id);
                $issues[] = $this->make_issue('mod_quiz_empty', $quiz->id,
                    get_string('issue_quiz_empty', 'block_teacher_checklist', $quiz->name),
                    '/mod/quiz/edit.php?cmid='.$cm->id,
                    $cm->get_icon_url(), $status);
            }

            if ($quiz->timeclose == 0 && $quiz->timelimit == 0) {
                $status = $this->get_status('mod_quiz_insecure', $quiz->id);
                $issues[] = $this->make_issue('mod_quiz_insecure', $quiz->id,
                    get_string('issue_quiz_insecure', 'block_teacher_checklist', $quiz->name),
                    '/course/modedit.php?update='.$cm->id.'&return=1',
                    $cm->get_icon_url(), $status);
            }
        }
        return $issues;
    }

    protected function scan_quiz_grading() {
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
                if (!$cm || !$cm->visible) continue;

                $status = $this->get_status('mod_quiz_grading', $quiz->id);
                
                $issues[] = $this->make_issue('mod_quiz_grading', $quiz->id,
                    get_string('issue_quiz_grading', 'block_teacher_checklist', $quiz->pending_count) . $quiz->name,
                    '/mod/quiz/report.php?id='.$cm->id.'&mode=overview',
                    $cm->get_icon_url(), 
                    $status, 
                    'high');
            }
        }
        return $issues;
    }

    protected function scan_completion_disabled() {
        $issues = [];
        foreach ($this->modinfo->cms as $cm) {
            if ($cm->modname == 'label' || !$cm->uservisible) continue;

            if ($cm->completion == COMPLETION_TRACKING_NONE) {
                $status = $this->get_status('completion_disabled', $cm->id);
                $issues[] = $this->make_issue('completion_disabled', $cm->id,
                    get_string('issue_completion_disabled', 'block_teacher_checklist', $cm->name),
                    '/course/modedit.php?update='.$cm->id.'&return=1',
                    $cm->get_icon_url(), $status);
            }
        }
        return $issues;
    }

    protected function scan_empty_sections() {
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot.'/course/lib.php');

        $issues = [];
        $sections = $this->modinfo->get_section_info_all();

        foreach ($sections as $section) {
            if ($section->section == 0) continue; 
            if ($section->visible && empty($section->sequence) && empty($section->summary)) {
                
                $status = $this->get_status('section', $section->id);
                $sectionname = get_section_name($this->course, $section);
                $icon = $OUTPUT->image_url('i/folder'); 

                $issues[] = $this->make_issue('section', $section->id,
                    get_string('issue_section_empty', 'block_teacher_checklist', $sectionname),
                    '/course/editsection.php?id='.$section->id,
                    $icon, 
                    $status);
            }
        }
        return $issues;
    }

    protected function make_issue($subtype, $docid, $title, $url, $icon, $status, $severity = 'normal') {
        return [
            'type' => 'auto',
            'subtype' => $subtype,
            'docid' => $docid,
            'title' => $title,
            'url' => $url instanceof \moodle_url ? $url : new \moodle_url($url),
            'icon' => $icon,
            'status' => $status,
            'severity' => $severity
        ];
    }

    protected function get_status($subtype, $docid) {
        $key = $subtype . '-' . $docid;
        return isset($this->ignored_items[$key]) ? (int)$this->ignored_items[$key] : 0;
    }

    protected function get_cm_by_instance($modname, $instanceid) {
        $instances = $this->modinfo->get_instances_of($modname);
        return $instances[$instanceid] ?? null;
    }
}
