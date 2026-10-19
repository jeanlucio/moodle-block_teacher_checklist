<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Step definitions and page resolvers for block_teacher_checklist.
 *
 * @package    block_teacher_checklist
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Page resolvers for block_teacher_checklist.
 *
 * @package    block_teacher_checklist
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_teacher_checklist extends behat_base {
    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype  | name meaning      | description                                  |
     * | dashboard | Course short name | The full checklist page of the course (view.php) |
     *
     * @param string $type Identifies which type of page this is, e.g. 'dashboard'.
     * @param string $identifier Identifies the particular page, e.g. the course short name.
     * @return moodle_url The corresponding URL.
     * @throws Exception With a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch (strtolower($type)) {
            case 'dashboard':
                return new moodle_url('/blocks/teacher_checklist/view.php', ['id' => $this->get_course_id($identifier)]);

            default:
                throw new Exception('Unrecognised teacher_checklist page type "' . $type . '."');
        }
    }
}
