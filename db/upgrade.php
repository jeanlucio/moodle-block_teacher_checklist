<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Função de atualização do bloco.
 *
 * @param int $oldversion A versão antiga instalada no banco.
 * @return bool True se o upgrade for bem-sucedido.
 */
function xmldb_block_teacher_checklist_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Moodle v4.0.0 release upgrade line.
    // Put any upgrade step following this.

    // Exemplo de como seria um upgrade futuro (apenas ilustrativo):
    /*
    if ($oldversion < 2026020100) {
        // Define field XYZ to be added to block_teacher_checklist.
        $table = new xmldb_table('block_teacher_checklist');
        $field = new xmldb_field('new_column', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'status');

        // Conditionally launch add field XYZ.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Checklist savepoint reached.
        upgrade_block_savepoint(true, 2026020100, 'teacher_checklist');
    }
    */

    return true;
}
