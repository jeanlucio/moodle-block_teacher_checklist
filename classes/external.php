<?php
namespace block_teacher_checklist;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class external extends \external_api {

    /**
     * Define os parâmetros que o Javascript vai enviar
     */
    public static function toggle_item_status_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'ID do curso'),
            'type'     => new \external_value(PARAM_TEXT, 'auto ou manual'),
            'subtype'  => new \external_value(PARAM_TEXT, 'ex: mod_assign', VALUE_DEFAULT, ''),
            'docid'    => new \external_value(PARAM_INT, 'ID da instancia', VALUE_DEFAULT, 0),
            'status'   => new \external_value(PARAM_INT, '1=Concluido, 2=Ignorado, 0=Pendente')
        ]);
    }

    /**
     * A função que executa a gravação no banco
     */
    public static function toggle_item_status($courseid, $type, $subtype, $docid, $status) {
        global $DB, $USER;

        // 1. Validação dos parâmetros
        $params = self::validate_parameters(self::toggle_item_status_parameters(), [
            'courseid' => $courseid,
            'type'     => $type,
            'subtype'  => $subtype,
            'docid'    => $docid,
            'status'   => $status
        ]);

        // 2. Verificação de Segurança (Contexto)
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        
        // Só professor/editor pode fazer isso
        require_capability('moodle/course:update', $context);

        // 3. Lógica de "Upsert" (Update ou Insert)

        // CASO 1: ITEM MANUAL
        // Para manuais, o 'docid' que vem do JS é o ID real da linha no banco de dados.
        if ($params['type'] === 'manual') {
            $record = $DB->get_record('block_teacher_checklist', ['id' => $params['docid']]);
            
            if ($record) {
                $record->status = $params['status'];
                $record->userid = $USER->id; // Quem alterou
                $record->timemodified = time();
                $DB->update_record('block_teacher_checklist', $record);
            }
            // Se não achar o registro, não faz nada (evita erro)
            return ['success' => true];
        }

        // CASO 2: ITEM AUTOMÁTICO
        // Para automáticos, buscamos pela combinação de chaves
        $conditions = [
            'courseid' => $params['courseid'],
            'type'     => $params['type'],
            'subtype'  => $params['subtype'],
            'docid'    => $params['docid']
        ];

        $record = $DB->get_record('block_teacher_checklist', $conditions);

        if ($record) {
            // Se já existe (ex: era ignorado e virou pendente), atualiza
            $record->status = $params['status'];
            $record->userid = $USER->id;
            $record->timemodified = time();
            $DB->update_record('block_teacher_checklist', $record);
        } else {
            // Se não existe (primeira interação), cria
            $newrecord = (object) $conditions;
            $newrecord->userid = $USER->id;
            $newrecord->status = $params['status'];
            $newrecord->timecreated = time();
            $newrecord->timemodified = time();
            $DB->insert_record('block_teacher_checklist', $newrecord);
        }

        return ['success' => true];
    }

    /**
     * Define o retorno da função
     */
    public static function toggle_item_status_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Se funcionou')
        ]);
    }
}
