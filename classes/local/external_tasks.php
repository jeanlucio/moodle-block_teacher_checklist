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

namespace block_teacher_checklist\local;

/**
 * Public API for other plugins to seed manual checklist items into a course.
 *
 * Items provisioned through this API are stored as ordinary manual items but tagged
 * with a source key in the subtype column, so the owning plugin can replace its own
 * set without disturbing items the teacher added by hand (which carry an empty subtype).
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external_tasks {
    /**
     * Replaces the set of externally-provisioned manual items for a course.
     *
     * Existing items previously provisioned under the same source are removed and
     * recreated from the given titles. Items added by the teacher (empty subtype) and
     * items provisioned by other sources are left untouched.
     *
     * @param int      $courseid Course to seed the items into.
     * @param string   $source   Frankenstyle component requesting the seed (e.g. local_virtuallab).
     * @param string[] $titles   Item titles in display order; empty entries are skipped.
     */
    public static function replace(int $courseid, string $source, array $titles): void {
        global $DB, $USER;

        $conditions = [
            'courseid' => $courseid,
            'type'     => 'manual',
            'subtype'  => $source,
        ];
        $DB->delete_records('block_teacher_checklist', $conditions);

        $now  = time();
        $rows = [];
        foreach ($titles as $title) {
            $title = trim((string) $title);
            if ($title === '') {
                continue;
            }
            $rows[] = (object) [
                'courseid'     => $courseid,
                'userid'       => (int) ($USER->id ?? 0),
                'type'         => 'manual',
                'subtype'      => $source,
                'docid'        => 0,
                'title'        => $title,
                'status'       => 0,
                'timecreated'  => $now,
                'timemodified' => $now,
            ];
        }

        if ($rows) {
            $DB->insert_records('block_teacher_checklist', $rows);
        }
    }
}
