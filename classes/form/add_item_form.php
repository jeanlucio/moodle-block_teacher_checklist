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

namespace block_teacher_checklist\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to add manual items to the checklist.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_item_form extends \moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {
        $mform = $this->_form;

        // Hidden field for Course ID.
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        // Inline group for the text input and button.
        $group = [];
        $group[] = $mform->createElement(
            'text',
            'manual_title',
            '',
            [
                'placeholder' => get_string('placeholder_manual', 'block_teacher_checklist'),
                'class' => 'form-control',
            ]
        );
        $group[] = $mform->createElement(
            'submit',
            'submitbutton',
            get_string('add_btn', 'block_teacher_checklist')
        );

        $mform->addGroup($group, 'group1', get_string('new_manual_item', 'block_teacher_checklist'), ' ', false);

        // Input type definition.
        $mform->setType('manual_title', PARAM_TEXT);

        // NOTA: Removemos o addRule aqui para evitar o erro do PEAR/QuickForm em grupos.
        // A validação agora é feita na função validation() abaixo.
    }

    /**
     * Server-side validation.
     *
     * @param array $data The submitted data.
     * @param array $files The submitted files.
     * @return array Errors list.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Verifica manualmente se o campo do título está vazio.
        // Isso contorna o problema de validar campos dentro de grupos.
        if (empty($data['manual_title']) || trim($data['manual_title']) == '') {
            $errors['group1'] = get_string('required');
        }

        return $errors;
    }
}
