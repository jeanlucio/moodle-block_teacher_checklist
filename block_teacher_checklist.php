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

defined('MOODLE_INTERNAL') || die();

/**
 * Teacher Checklist Block.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio <jeanlucio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_teacher_checklist extends block_base {

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_teacher_checklist');
    }

    /**
     * Generate the block content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        // 1. Permission Check.
        $context = context_course::instance($COURSE->id);
        if (!has_capability('moodle/course:update', $context)) {
            // If user cannot edit course, show nothing.
            return $this->content;
        }

        // 2. Fetch Issues via Scanner.
        $scanner = new \block_teacher_checklist\scanner($COURSE);
        $allissues = $scanner->get_all_issues();

        // Filter only pending issues for the block view (status = 0).
        $pendingissues = array_filter($allissues, function($issue) {
            return (int)$issue['status'] === 0;
        });

        // 3. Render Content.
        /** @var \block_teacher_checklist\output\renderer $renderer */
        $renderer = $this->page->get_renderer('block_teacher_checklist');
        
        $this->content->text = $renderer->render_block_summary($pendingissues, $COURSE->id);

        // Inject JavaScript.
        $this->page->requires->js_call_amd('block_teacher_checklist/actions', 'init');

        return $this->content;
    }

    /**
     * Allow the block to be added to course view.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['course-view' => true];
    }
}
