<?php
class block_teacher_checklist extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_teacher_checklist');
    }

    public function get_content() {
        global $COURSE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        
        // 1. Verifica permissão (Só professor vê)
        $context = context_course::instance($COURSE->id);
        if (!has_capability('moodle/course:update', $context)) {
            $this->content->text = '';
            return $this->content;
        }

        // 2. Instancia o Scanner
        // CORREÇÃO AQUI: Chamamos o metodo novo get_all_issues()
        $scanner = new \block_teacher_checklist\scanner($COURSE);
        $all_issues = $scanner->get_all_issues();

        // Vamos filtrar apenas os pendentes para exibir no bloco
        $pending_issues = [];
        foreach ($all_issues as $issue) {
            if ($issue['status'] == 0) { // 0 = Pendente
                $pending_issues[] = $issue;
            }
        }

        // 3. Renderiza a lista
        if (empty($pending_issues)) {
            $this->content->text = '<div class="alert alert-success">'.get_string('no_issues_found', 'block_teacher_checklist').'</div>';
        } else {
            $html = '<ul class="list-group list-group-flush block_teacher_checklist">';
            
            // Mostra apenas os 5 primeiros no bloco
            $limit = 5;
            $count = 0;
            
            foreach ($pending_issues as $issue) {
                if ($count >= $limit) break;
                
                $html .= '<li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 0.5rem 0;">';
                
                // Ícone + Link
                $html .= '<div style="max-width: 70%;">'; // Limita largura para não quebrar layout
                $html .= '<img src="'.$issue['icon'].'" class="icon" alt="" style="margin-right:4px;" /> ';
                
                // Se for item manual sem link, não poe o <a>
                if (isset($issue['url']) && $issue['url'] != '#') {
                    $html .= '<a href="'.$issue['url'].'">'.$issue['title'].'</a>';
                } else {
                    $html .= '<span>'.$issue['title'].'</span>';
                }
                $html .= '</div>';
                
                // --- BOTÕES DE AÇÃO ---
                $html .= '<div class="actions">';

                // Botão CONCLUIR (✔) - Apenas para itens MANUAIS
                if ($issue['type'] === 'manual') {
                    $docid = isset($issue['id']) ? $issue['id'] : 0; // Se for manual, o ID é importante
                    
                    $html .= '<a href="#" class="btn-action text-success" style="margin-right: 5px;" 
                                data-action="toggle-status" 
                                data-courseid="'.$COURSE->id.'" 
                                data-type="'.$issue['type'].'" 
                                data-subtype="'.$issue['subtype'].'" 
                                data-docid="'.$docid.'" 
                                data-status="1" 
                                title="Concluir">✔</a> ';
                }

                // Botão IGNORAR (✖) - Aparece para TODOS
                $html .= '<a href="#" class="btn-action text-danger" 
                            data-action="toggle-status" 
                            data-courseid="'.$COURSE->id.'" 
                            data-type="'.$issue['type'].'" 
                            data-subtype="'.$issue['subtype'].'" 
                            data-docid="'.$issue['docid'].'" 
                            data-status="2" 
                            title="Ignorar/Remover">✖</a>';

                $html .= '</div>';
                // --- FIM BOTÕES ---
                
                $html .= '</li>';
                $count++;
            }
            $html .= '</ul>';
            
            // Se tiver mais itens, avisa
            if (count($pending_issues) > $limit) {
                $html .= '<div class="text-center small text-muted">E mais '.(count($pending_issues) - $limit).' pendências...</div>';
            }

            $this->content->text = $html;
        }

        // Injeta o JavaScript para os cliques funcionarem
        $this->page->requires->js_call_amd('block_teacher_checklist/actions', 'init');

        // Rodapé com link para a página completa
        $url = new moodle_url('/blocks/teacher_checklist/view.php', ['id' => $COURSE->id]);
        $this->content->footer = html_writer::link($url, get_string('view_full_report', 'block_teacher_checklist'));

        return $this->content;
    }
    
    public function applicable_formats() {
        return array('course-view' => true);
    }
}
