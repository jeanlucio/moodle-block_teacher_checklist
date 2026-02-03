<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY;
// without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.
// If not, see <http://www.gnu.org/licenses/>.

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($course->id);
require_capability('moodle/course:update', $context);

// Page Setup.
$PAGE->set_url('/blocks/teacher_checklist/view.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('pluginname', 'block_teacher_checklist'));
$PAGE->set_title($course->shortname . ': ' . get_string('checklist_title', 'block_teacher_checklist'));

// Initialize Renderer.
/** @var \block_teacher_checklist\output\renderer $renderer */
$renderer = $PAGE->get_renderer('block_teacher_checklist');

// --- 1. Form Processing (Manual HTML - Secure & Clean Layout) ---
// Verifica se dados foram enviados E se a chave de sessão é válida (Exigência Moodle.org)
if (data_submitted() && confirm_sesskey()) {
    // Limpeza de dados (Exigência Moodle.org)
    $newtitle = optional_param('manual_title', '', PARAM_TEXT);

    if (!empty($newtitle) && trim($newtitle) !== '') {
        $record = new stdClass();
        $record->courseid = $course->id;
        $record->userid = $USER->id;
        $record->type = 'manual';
        $record->title = $newtitle;
        $record->status = 0; // Pendente
        $record->timecreated = time();
        $record->timemodified = time();

        $DB->insert_record('block_teacher_checklist', $record);

        // Redireciona para evitar reenvio do formulário (Post-Redirect-Get pattern)
        redirect($PAGE->url, get_string('msg_item_added', 'block_teacher_checklist'));
    }
}

// --- 2. Data Gathering ---
$scanner = new \block_teacher_checklist\scanner($course);
$isscanactive = $scanner->is_active();

// Get auto issues (if enabled).
$autoissues = ($isscanactive) ? $scanner->get_all_issues() : [];

// Get manual issues (always).
$manualrecords = $DB->get_records('block_teacher_checklist', ['courseid' => $course->id, 'type' => 'manual']);
$manualissues = [];
foreach ($manualrecords as $rec) {
    // Convert DB record to standard issue array structure.
    $manualissues[] = [
        'id' => $rec->id,
        'type' => 'manual',
        'subtype' => '',
        'docid' => 0,
        'title' => format_string($rec->title), // Usando format_string para segurança
        'url' => '#',
        'icon' => $OUTPUT->image_url('t/edit'),
        'status' => (int)$rec->status,
    ];
}

// Merge and Sort.
$allitems = array_merge($autoissues, $manualissues);

// Sort buckets.
$pending = [];
$ignored = [];
$done = [];

foreach ($allitems as $item) {
    switch ($item['status']) {
        case 1:
            $done[] = $renderer->prepare_issue_for_template($item, $courseid, 'done');
            break;
        case 2:
            $ignored[] = $renderer->prepare_issue_for_template($item, $courseid, 'ignored');
            break;
        default:
            $pending[] = $renderer->prepare_issue_for_template($item, $courseid, 'pending');
    }
}

foreach ($pending as $index => &$item) {
    $item['number'] = $index + 1;
}
unset($item); // Boa prática ao usar referência (&)

foreach ($ignored as $index => &$item) {
    $item['number'] = $index + 1;
}
unset($item);

foreach ($done as $index => &$item) {
    $item['number'] = $index + 1;
}
unset($item);

// --- 3. Prepare Template Data ---
$data = [
    'courseid' => $courseid,
    'action_url' => $PAGE->url->out(false), // Necessário para o formulário manual HTML
    'sesskey' => sesskey(),                 // OBRIGATÓRIO para segurança do formulário manual
    'is_scan_active' => $isscanactive,
    
    // Tab Counts.
    'count_pending' => count($pending),
    'count_ignored' => count($ignored),
    'count_done'    => count($done),

    // Tab Content.
    'has_pending'   => !empty($pending),
    'items_pending' => $pending,
    
    'has_ignored'   => !empty($ignored),
    'items_ignored' => $ignored,
    
    'has_done'      => !empty($done),
    'items_done'    => $done,
];

// --- 4. Render ---
echo $OUTPUT->header();

echo $renderer->render_from_template('block_teacher_checklist/dashboard', $data);

// Inject JS.
$PAGE->requires->js_call_amd('block_teacher_checklist/actions', 'init');

echo $OUTPUT->footer();
