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
 * Define the backup structure steps for the teacher_checklist block.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_teacher_checklist_structure_step extends backup_block_structure_step {

protected function define_structure() {
        // 1. Define o elemento raiz
        $teacher_checklist = new backup_nested_element('teacher_checklist', ['id'], null);

        // 2. Define os itens
        $items = new backup_nested_element('checklist_items');

        // 3. Define os campos do item
        $item = new backup_nested_element('checklist_item', ['id'], [
            'userid', 'type', 'subtype', 'docid', 'title', 'status', 'timecreated', 'timemodified'
        ]);

        // 4. Monta a árvore
        $teacher_checklist->add_child($items);
        $items->add_child($item);

        // 5. Define a fonte de dados COM FILTRO
        // AQUI ESTÁ O PULO DO GATO: Filtramos apenas onde type = 'manual'
        // Assim, não levamos o "lixo" automático para o backup.
        $item->set_source_table('block_teacher_checklist', [
            'courseid' => backup::VAR_COURSEID,
            'type'     => backup_helper::is_sqlparam('manual') 
        ]);

        // 6. Anota ids de usuário
        $item->annotate_ids('user', 'userid');

        // 7. Retorna a estrutura
        return $this->prepare_block_structure($teacher_checklist);
    }
}
