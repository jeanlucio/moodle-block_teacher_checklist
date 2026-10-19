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
 * @copyright  2026 Jean Lúcio
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
                'courseid' => 'privacy:metadata:courseid',
                'userid' => 'privacy:metadata:userid',
                'type' => 'privacy:metadata:type',
                'subtype' => 'privacy:metadata:subtype',
                'docid' => 'privacy:metadata:docid',
                'title' => 'privacy:metadata:title',
                'status' => 'privacy:metadata:status',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
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
        if (empty($contextlist->count())) {
            return;
        }

        $courseids = [];
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $courseids[] = $context->instanceid;
            }
        }

        self::erase_users_in_courses($courseids, [$contextlist->get_user()->id]);
    }

    /**
     * Deletes data for multiple users in a context (UserList version).
     *
     * @param approved_userlist $userlist The approved user list object.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        self::erase_users_in_courses([$context->instanceid], $userids);
    }

    /**
     * Erases what the given users own in the given courses without touching the state shared by other teachers.
     *
     * Manual items belong to the user who wrote them, so they are deleted. Rows of type auto (ignored or done
     * issues) and config (the automatic-scan switch) are course state that every teacher sees; they only
     * record who set them last, so the user is detached from them instead of deleting them.
     *
     * @param int[] $courseids The course ids.
     * @param int[] $userids The users to erase.
     */
    private static function erase_users_in_courses(array $courseids, array $userids): void {
        global $DB;

        if (empty($courseids) || empty($userids)) {
            return;
        }

        [$coursesql, $courseparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'user');
        $params = $courseparams + $userparams + ['manual' => 'manual'];

        $DB->delete_records_select(
            'block_teacher_checklist',
            "courseid $coursesql AND type = :manual AND userid $usersql",
            $params
        );
        $DB->set_field_select(
            'block_teacher_checklist',
            'userid',
            0,
            "courseid $coursesql AND type <> :manual AND userid $usersql",
            $params
        );
    }

    /**
     * Helper to translate the status.
     *
     * @param int $status The status code.
     * @return string The status name.
     */
    private static function get_status_name(int $status): string {
        switch ($status) {
            case 1:
                return get_string('status_done', 'block_teacher_checklist');
            case 2:
                return get_string('status_ignored', 'block_teacher_checklist');
            default:
                return get_string('status_pending', 'block_teacher_checklist');
        }
    }
}
