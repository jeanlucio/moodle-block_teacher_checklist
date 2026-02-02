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

namespace block_teacher_checklist\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use moodle_url;

/**
 * Main renderer class for the block_teacher_checklist plugin.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Renders the block content (sidebar summary).
     *
     * @param array $issues List of pending issues.
     * @param int $courseid The current course ID.
     * @return string HTML for the block content.
     */
    public function render_block_summary(array $issues, int $courseid): string {
        $limit = 5;
        $displayissues = array_slice($issues, 0, $limit);
        $formattedissues = [];

        foreach ($displayissues as $issue) {
            $formattedissues[] = $this->prepare_issue_for_template($issue, $courseid, 'pending');
        }

        $data = [
            'hasissues' => !empty($issues),
            'issues' => $formattedissues,
            'moreissues' => count($issues) > $limit ? ['count' => count($issues) - $limit] : false,
            'fullreporturl' => (new moodle_url('/blocks/teacher_checklist/view.php', ['id' => $courseid]))->out(false),
        ];

        return $this->render_from_template('block_teacher_checklist/block_summary', $data);
    }

    /**
     * Helper to format a raw issue array into template context.
     *
     * @param array $issue Raw issue data from scanner/DB.
     * @param int $courseid Course ID.
     * @param string $mode 'pending', 'ignored', or 'done'.
     * @return array Context for checklist_item template.
     */
    public function prepare_issue_for_template(array $issue, int $courseid, string $mode): array {
        // Determine ID to use (DB id for manual, docid for auto).
        $docid = ($issue['type'] === 'manual') ? (int) $issue['id'] : (int) ($issue['docid'] ?? 0);

        // Security check for icon.
        $icon = $issue['icon'];
        if ($icon instanceof moodle_url) {
            $icon = $icon->out(false);
        }

        // Prepare context.
        $context = [
            'id' => $issue['id'] ?? uniqid(),
            'isdone' => ($mode === 'done'),
            'bulkable' => true,
            'type' => $issue['type'],
            'subtype' => $issue['subtype'],
            'docid' => $docid,
            'icon' => $icon,
            'title' => format_string($issue['title']), // Safe HTML output.
            'url' => ($issue['url'] && $issue['url'] != '#') ? $issue['url'] : false,
            'severity_high' => (isset($issue['severity']) && $issue['severity'] === 'high'),
            'courseid' => $courseid,
            'can_mark_done' => ($mode === 'pending' && $issue['type'] === 'manual'),
            'can_restore' => ($mode !== 'pending'),
            'can_ignore' => ($mode === 'pending'),
        ];

        return $context;
    }
}
