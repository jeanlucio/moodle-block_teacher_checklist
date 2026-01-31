<?php
require_once('../../config.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($course->id);
require_capability('moodle/course:update', $context);

// Configuração da Página
$PAGE->set_url('/blocks/teacher_checklist/view.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('pluginname', 'block_teacher_checklist'));
$PAGE->set_title($course->shortname . ': ' . get_string('checklist_title', 'block_teacher_checklist'));

// --- 1. PROCESSAMENTO DE FORMULÁRIO (Adicionar Item Manual) ---
if ($data = data_submitted() && confirm_sesskey()) {
    $newtitle = optional_param('manual_title', '', PARAM_TEXT);
    if (!empty($newtitle)) {
        $record = new stdClass();
        $record->courseid = $course->id;
        $record->userid = $USER->id;
        $record->type = 'manual';
        $record->title = $newtitle;
        $record->status = 0; // Pendente
        $record->timecreated = time();
        $record->timemodified = time();
        
        $DB->insert_record('block_teacher_checklist', $record);
        redirect($PAGE->url, get_string('msg_item_added', 'block_teacher_checklist'));
    }
}

// --- 2. CARREGAR DADOS ---
$scanner = new \block_teacher_checklist\scanner($course);

// Verifica se o monitoramento está ativado no banco
$is_scan_active = $scanner->is_active();

// Se estiver ativo, busca pendências. Se não, array vazio.
$auto_issues = ($is_scan_active) ? $scanner->get_all_issues() : [];

$manual_issues = $DB->get_records('block_teacher_checklist', ['courseid' => $course->id, 'type' => 'manual']);

// --- 3. ORGANIZAR EM ABAS ---
$tab_pending = [];
$tab_ignored = [];
$tab_done = [];

// Automáticos
foreach ($auto_issues as $issue) {
    if ($issue['status'] == 0) $tab_pending[] = $issue;
    if ($issue['status'] == 2) $tab_ignored[] = $issue;
}

// Manuais
foreach ($manual_issues as $manual) {
    $item = [
        'id' => $manual->id,
        'type' => 'manual',
        'subtype' => '',
        'docid' => 0,
        'title' => format_text($manual->title),
        'url' => '#',
        'icon' => $OUTPUT->image_url('t/edit'), 
        'status' => $manual->status
    ];
    
    if ($manual->status == 0) $tab_pending[] = $item;
    if ($manual->status == 1) $tab_done[] = $item;
    if ($manual->status == 2) $tab_ignored[] = $item;
}

// --- 4. RENDERIZAÇÃO ---

echo $OUTPUT->header();

// CABEÇALHO COM INTERRUPTOR (SWITCH)
echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    // Título Principal
    echo $OUTPUT->heading(get_string('checklist_title', 'block_teacher_checklist'), 2, 'mb-0');

    // Interruptor ON/OFF
    echo '<div class="form-check form-switch" title="'.get_string('scan_enabled_help', 'block_teacher_checklist').'">';
    echo '<input class="form-check-input" type="checkbox" id="toggleScan" '.($is_scan_active ? 'checked' : '').' 
            data-courseid="'.$courseid.'">';
    echo '<label class="form-check-label small text-muted" for="toggleScan">'.get_string('scan_enabled', 'block_teacher_checklist').'</label>';
    echo '</div>';
echo '</div>';


// FORMULÁRIO DE ADIÇÃO MANUAL COM AJUDA
echo '<div class="card mb-4">';
echo '<div class="card-body p-3">';
    echo '<form method="post" action="" class="d-flex align-items-center mb-2">';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'">';
    echo '<label class="me-2 text-nowrap"><strong>'.get_string('new_manual_item', 'block_teacher_checklist').'</strong></label>';
    echo '<input type="text" name="manual_title" class="form-control me-2" style="width: 100%;" placeholder="'.get_string('placeholder_manual', 'block_teacher_checklist').'">';
    echo '<button type="submit" class="btn btn-primary text-nowrap">'.get_string('add_btn', 'block_teacher_checklist').'</button>';
    echo '</form>';

    // Botão de Ajuda (Collapse)
    echo '<a class="small text-muted text-decoration-none" data-bs-toggle="collapse" href="#helpManual" role="button" aria-expanded="false">';
    echo '<i class="fa fa-question-circle me-1"></i>'.get_string('help_manual_title', 'block_teacher_checklist');
    echo '</a>';
    echo '<div class="collapse mt-2" id="helpManual">';
    echo '<div class="alert alert-info mb-0 small">';
    echo get_string('help_manual_text', 'block_teacher_checklist');
    echo '</div>';
    echo '</div>'; 
echo '</div>'; 
echo '</div>'; 

// NAVEGAÇÃO DE ABAS
echo '<ul class="nav nav-tabs" id="checklistTabs" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" id="pending-tab" data-bs-toggle="tab" href="#pending" role="tab">'.get_string('tab_pending', 'block_teacher_checklist').' <span class="badge bg-danger">'.count($tab_pending).'</span></a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="ignored-tab" data-bs-toggle="tab" href="#ignored" role="tab">'.get_string('tab_ignored', 'block_teacher_checklist').' <span class="badge bg-secondary">'.count($tab_ignored).'</span></a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="done-tab" data-bs-toggle="tab" href="#done" role="tab">'.get_string('tab_done', 'block_teacher_checklist').' <span class="badge bg-success">'.count($tab_done).'</span></a>
  </li>
</ul>';

echo '<div class="tab-content" id="myTabContent" style="padding-top: 20px;">';

// Renderiza as 3 abas
render_tab_content('pending', $tab_pending, $COURSE->id, 'pending');
render_tab_content('ignored', $tab_ignored, $COURSE->id, 'ignored');
render_tab_content('done', $tab_done, $COURSE->id, 'done');

echo '</div>'; 

$PAGE->requires->js_call_amd('block_teacher_checklist/actions', 'init');

echo $OUTPUT->footer();


// --- FUNÇÃO DE RENDERIZAÇÃO DA ABA (Com Ações em Massa) ---
function render_tab_content($id, $items, $courseid, $mode) {
    $active = ($id === 'pending') ? 'show active' : '';
    echo '<div class="tab-pane fade '.$active.'" id="'.$id.'" role="tabpanel">';
    
    if (empty($items)) {
        // Seleciona a mensagem correta baseada na aba
        $msg = ($mode == 'pending') ? 'alert_clean' : (($mode == 'ignored') ? 'alert_none_ignored' : 'alert_none_done');
        echo '<div class="alert alert-info">'.get_string($msg, 'block_teacher_checklist').'</div>';
    } else {
        // BARRA DE AÇÕES EM MASSA
        echo '<div class="d-flex align-items-center mb-3 bg-light p-2 rounded border">';
        echo '<div class="form-check ms-2 me-3">';
        echo '<input class="form-check-input select-all-toggle" type="checkbox" data-target="#list-'.$id.'">';
        echo '<label class="form-check-label small text-muted">'.get_string('bulk_select_all', 'block_teacher_checklist').'</label>';
        echo '</div>';
        
        echo '<div class="bulk-actions-container" style="display:none;">';
        echo '<span class="me-2 small fw-bold">'.get_string('bulk_actions', 'block_teacher_checklist').'</span>';
        
        if ($mode == 'pending') {
            echo '<button class="btn btn-sm btn-outline-success me-1 bulk-btn" data-action="1" data-courseid="'.$courseid.'">✔ '.get_string('bulk_done', 'block_teacher_checklist').'</button>';
            echo '<button class="btn btn-sm btn-outline-danger bulk-btn" data-action="2" data-courseid="'.$courseid.'">✖ '.get_string('bulk_ignore', 'block_teacher_checklist').'</button>';
        } elseif ($mode == 'ignored' || $mode == 'done') {
            echo '<button class="btn btn-sm btn-outline-secondary bulk-btn" data-action="0" data-courseid="'.$courseid.'">↺ '.get_string('bulk_restore', 'block_teacher_checklist').'</button>';
        }
        echo '</div>';
        echo '</div>';

        // Renderiza a lista de itens
        print_checklist_list($items, $courseid, $mode, $id);
    }
    echo '</div>';
}


// --- FUNÇÃO AUXILIAR DE RENDERIZAÇÃO DA LISTA ---
function print_checklist_list($items, $courseid, $mode, $listid) {
    global $OUTPUT; 

    echo '<ul class="list-group block_teacher_checklist" id="list-'.$listid.'">';
    
    $count = 1; 

    foreach ($items as $item) {
        // Define o ID correto para o checkbox (se manual usa id, se auto usa docid)
        $docid = ($item['type'] === 'manual') ? $item['id'] : (($item['docid']) ?? 0);
        
        $data_attrs = 'data-type="'.$item['type'].'" data-subtype="'.$item['subtype'].'" data-docid="'.$docid.'"';

        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        
        // --- COLUNA ESQUERDA ---
        echo '<div class="d-flex align-items-center">';
        
        // Checkbox para seleção em massa
        echo '<input class="form-check-input me-3 item-checkbox" type="checkbox" '.$data_attrs.'>';
        
        // Número + Ícone + Texto
        echo '<span class="text-dark me-2 fw-bold">'.$count.'.</span>';
        echo '<img src="'.$item['icon'].'" class="icon me-2" alt="" />';
        
        if ($item['url'] && $item['url'] != '#') {
            echo '<a href="'.$item['url'].'" target="_blank" class="text-break">'.$item['title'].'</a>';
        } else {
            echo '<span class="text-break">'.$item['title'].'</span>';
        }
        echo '</div>';
        
        // --- COLUNA DIREITA (BOTÕES INDIVIDUAIS) ---
        echo '<div class="actions" style="min-width: 140px; text-align: right;">';
        
        if ($mode != 'pending') {
             echo '<a href="#" class="btn btn-sm btn-outline-secondary me-1" 
                    data-action="toggle-status" 
                    data-courseid="'.$courseid.'" 
                    '.$data_attrs.' 
                    data-status="0" 
                    title="'.get_string('restore', 'block_teacher_checklist').'">↺</a> ';
        }
        
        if ($mode == 'pending' && $item['type'] === 'manual') {
            echo '<a href="#" class="btn btn-sm btn-outline-success me-1" 
                    data-action="toggle-status" 
                    data-courseid="'.$courseid.'" 
                    '.$data_attrs.' 
                    data-status="1" 
                    title="'.get_string('mark_done', 'block_teacher_checklist').'">✔</a> ';
        }

        if ($mode == 'pending') {
             echo '<a href="#" class="btn btn-sm btn-outline-danger" 
                    data-action="toggle-status" 
                    data-courseid="'.$courseid.'" 
                    '.$data_attrs.' 
                    data-status="2" 
                    title="'.get_string('ignore', 'block_teacher_checklist').'">✖</a>';
        }

        echo '</div>';
        echo '</li>';
        
        $count++;
    }
    echo '</ul>';
}
?>
