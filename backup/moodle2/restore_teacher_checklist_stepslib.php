<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Define the restore structure steps for the teacher_checklist block.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_teacher_checklist_structure_step extends restore_structure_step {

    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('checklist_item', '/block/teacher_checklist/checklist_items/checklist_item');
        return $paths;
    }

    public function process_checklist_item($data) {
        global $DB, $USER;

        $data = (object)$data;
        $data->courseid = $this->get_courseid();

        // Como alteramos o backup para trazer SÓ manuais, 
        // qualquer coisa diferente disso é anomalia e ignoramos.
        if ($data->type !== 'manual') {
            return;
        }

        // 1. MAPEAMENTO DE USUÁRIO
        $newuserid = $this->get_mappingid('user', $data->userid);
        $data->userid = $newuserid ? $newuserid : $USER->id;

        // 2. LIMPEZA DE DADOS
        // Manuais não usam docid, garantimos que seja 0
        $data->docid = 0;
        
        // OPCIONAL: Se você quiser que TODAS as manuais voltem como "Pendentes" (Status 0)
        // descomente a linha abaixo. Caso contrário, ele mantém o status original (Feito/Ignorado).
        // $data->status = 0; 

        // 3. GRAVAÇÃO SEGURA (Prevenção de SQL Text Error)
        // Usamos sql_compare_text pois o título é TEXT
        $compare_title = $DB->sql_compare_text('title');
        
        $sql = "SELECT id 
                FROM {block_teacher_checklist} 
                WHERE courseid = ? 
                  AND type = 'manual' 
                  AND $compare_title = ?";
                  
        $exists = $DB->record_exists_sql($sql, [$data->courseid, $data->title]);

        if (!$exists) {
            $DB->insert_record('block_teacher_checklist', $data);
        }
    }
}
