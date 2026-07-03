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

/**
 * Event observer for block_teacher_checklist.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_teacher_checklist;

use block_teacher_checklist\privacy\provider;
use core\event\course_deleted;

/**
 * Class observer
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Deletes all checklist items belonging to a course that has just been deleted.
     *
     * Checklist rows are keyed by courseid, not by block instance, so removing the block from
     * a page must not delete them, but Moodle never cascades this table on its own when the
     * whole course is removed. Reuses the privacy provider's context-scoped delete so both
     * code paths stay in sync.
     *
     * @param course_deleted $event
     * @return void
     */
    public static function course_deleted(course_deleted $event): void {
        provider::delete_data_for_all_users_in_context($event->get_context());
    }
}
