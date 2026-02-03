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

namespace block_teacher_checklist\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Privacy provider implementation for block_teacher_checklist.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Declares what data this plugin stores (Metadata).
     *
     * @param collection $collection The collection object.
     * @return collection The updated collection object.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'block_teacher_checklist',
            [
                'userid' => 'privacy:metadata:userid',
                'title' => 'privacy:metadata:title',
                'status' => 'privacy:metadata:status',
                'timecreated' => 'privacy:metadata:timecreated',
            ],
            'privacy:metadata:tableexplanation'
        );

        return $collection;
    }

    /**
     * Finds all contexts (courses) where a user has data.
     *
     * @param int $userid The user ID.
     * @return contextlist The context list object.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {block_teacher_checklist} b
                  JOIN {context} ctx ON ctx.instanceid = b.courseid AND ctx.contextlevel = :contextlevel
                 WHERE b.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Finds all users who have data in a specific context.
     *
     * @param userlist $userlist The userlist object.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $sql = "SELECT userid
                  FROM {block_teacher_checklist}
                 WHERE courseid = ?";

        $userlist->add_from_sql('userid', $sql, [$context->instanceid]);
    }

    /**
     * Exports user data.
     *
     * @param approved_contextlist $contextlist The approved context list object.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            $records = $DB->get_records('block_teacher_checklist', [
                'courseid' => $context->instanceid,
                'userid' => $user->id,
            ]);

            if (empty($records)) {
                continue;
            }

            $data = [];
            foreach ($records as $record) {
                $data[] = (object) [
                    'type' => $record->type,
                    'subtype' => $record->subtype,
                    'title' => format_string($record->title),
                    'status' => self::get_status_name($record->status),
                    'timecreated' => transform::datetime($record->timecreated),
                    'timemodified' => transform::datetime($record->timemodified),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'block_teacher_checklist')],
                (object) ['items' => $data]
            );
        }
    }

    /**
     * Deletes all data for a context.
     *
     * @param \context $context The context object.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $DB->delete_records('block_teacher_checklist', ['courseid' => $context->instanceid]);
    }

    /**
     * Deletes data for a specific user (ContextList version).
     *
     * @param approved_contextlist $contextlist The approved context list object.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }

            $DB->delete_records('block_teacher_checklist', [
                'courseid' => $context->instanceid,
                'userid' => $userid,
            ]);
        }
    }

    /**
     * Deletes data for multiple users in a context (UserList version).
     *
     * @param approved_userlist $userlist The approved user list object.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $inparams['courseid'] = $context->instanceid;

        $DB->delete_records_select(
            'block_teacher_checklist',
            "courseid = :courseid AND userid $insql",
            $inparams
        );
    }

    /**
     * Helper to translate the status.
     *
     * @param int $status The status code.
     * @return string The status name.
     */
    private static function get_status_name($status) {
        switch ($status) {
            case 1:
                return 'Done';
            case 2:
                return 'Ignored';
            default:
                return 'Pending';
        }
    }
}
