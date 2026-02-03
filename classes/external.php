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

declare(strict_types=1);

namespace block_teacher_checklist;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_course;
use stdClass;

/**
 * External API to handle status toggles via AJAX.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {
    /**
     * Describes the parameters for toggle_item_status.
     *
     * @return external_function_parameters
     */
    public static function toggle_item_status_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'type'     => new external_value(PARAM_ALPHA, 'Item type: auto or manual'),
            'subtype'  => new external_value(PARAM_TEXT, 'Subtype (e.g. mod_assign)', VALUE_DEFAULT, ''),
            'docid'    => new external_value(PARAM_INT, 'Document ID or Instance ID', VALUE_DEFAULT, 0),
            'status'   => new external_value(PARAM_INT, 'Status: 0=Pending, 1=Done, 2=Ignored'),
        ]);
    }

    /**
     * Toggles the status of a checklist item.
     *
     * @param int $courseid
     * @param string $type
     * @param string $subtype
     * @param int $docid
     * @param int $status
     * @return array
     */
    public static function toggle_item_status(int $courseid, string $type, string $subtype, int $docid, int $status): array {
        global $DB, $USER;

        // 1. Validate parameters.
        $params = self::validate_parameters(self::toggle_item_status_parameters(), [
            'courseid' => $courseid,
            'type'     => $type,
            'subtype'  => $subtype,
            'docid'    => $docid,
            'status'   => $status,
        ]);

        // 2. Security Check.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        // 3. Update Logic.

        // CASE 1: MANUAL ITEM
        // For manual items, 'docid' passed from JS acts as the record 'id'.
        if ($params['type'] === 'manual') {
            $record = $DB->get_record('block_teacher_checklist', ['id' => $params['docid'], 'courseid' => $params['courseid']]);
            if ($record) {
                $record->status = $params['status'];
                $record->userid = $USER->id;
                $record->timemodified = time();
                $DB->update_record('block_teacher_checklist', $record);
            }
            return ['success' => true];
        }

        // CASE 2: AUTO ITEM (Or Config)
        // For auto items, we search by keys.
        $conditions = [
            'courseid' => $params['courseid'],
            'type'     => $params['type'],
            'subtype'  => $params['subtype'],
            'docid'    => $params['docid'],
        ];

        $record = $DB->get_record('block_teacher_checklist', $conditions);

        if ($record) {
            $record->status = $params['status'];
            $record->userid = $USER->id;
            $record->timemodified = time();
            $DB->update_record('block_teacher_checklist', $record);
        } else {
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
     * Describes the return structure.
     *
     * @return external_single_structure
     */
    public static function toggle_item_status_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Status of the operation'),
        ]);
    }
}
