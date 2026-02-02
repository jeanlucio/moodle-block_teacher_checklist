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

defined('MOODLE_INTERNAL') || die();

/**
 * Teacher Checklist Block.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_teacher_checklist extends block_base {

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_teacher_checklist');
    }

    /**
     * Generate the block content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $COURSE, $DB, $OUTPUT; // Adicionado $DB e $OUTPUT

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        // 1. Permission Check.
        $context = context_course::instance($COURSE->id);
        if (!has_capability('moodle/course:update', $context)) {
            return $this->content;
        }

        // 2. Fetch Auto Issues via Scanner.
        $scanner = new \block_teacher_checklist\scanner($COURSE);
        $autoissues = $scanner->get_all_issues();

        // 3. Fetch Manual Pending Issues (Fix: Adicionando itens manuais ao bloco).
        $manualrecords = $DB->get_records('block_teacher_checklist', [
            'courseid' => $COURSE->id, 
            'type' => 'manual', 
            'status' => 0 // Apenas pendentes
        ]);

        $manualissues = [];
        foreach ($manualrecords as $rec) {
            $manualissues[] = [
                'id' => $rec->id,
                'type' => 'manual',
                'subtype' => '',
                'docid' => 0,
                'title' => $rec->title,
                'url' => '#',
                'icon' => $OUTPUT->image_url('t/edit'), // Ícone padrão de edição
                'status' => (int)$rec->status,
            ];
        }

        // 4. Merge and Filter.
        // Funde os dois arrays
        $allitems = array_merge($autoissues, $manualissues);

        // Garante que só temos pendentes (o scanner pode retornar outros status se não filtrado lá)
        $pendingissues = array_filter($allitems, function($item) {
            return (int)$item['status'] === 0;
        });

        // Opcional: Ordenar para manuais aparecerem primeiro ou misturados? 
        // Por padrão o array_merge coloca manuais no fim. Se quiser misturar, usaria usort aqui.

        // 5. Render Content.
        /** @var \block_teacher_checklist\output\renderer $renderer */
        $renderer = $this->page->get_renderer('block_teacher_checklist');
        
        // Renderiza (agora o renderer vai ocultar os checkboxes)
        $this->content->text = $renderer->render_block_summary($pendingissues, $COURSE->id);

        // Inject JavaScript.
        $this->page->requires->js_call_amd('block_teacher_checklist/actions', 'init');

        return $this->content;
    }

    /**
     * Allow the block to be added to course view.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['course-view' => true];
    }
}
