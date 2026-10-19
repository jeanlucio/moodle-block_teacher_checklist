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
 * PHPUnit tests for the block renderer.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_teacher_checklist\output;

use advanced_testcase;
use moodle_url;

/**
 * Tests for the block_teacher_checklist renderer.
 *
 * @package    block_teacher_checklist
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_teacher_checklist\output\renderer
 */
final class renderer_test extends advanced_testcase {
    /** @var renderer Renderer under test. */
    private $renderer;

    #[\Override]
    protected function setUp(): void {
        global $PAGE;

        parent::setUp();
        $this->resetAfterTest();
        $PAGE->set_url('/blocks/teacher_checklist/view.php');
        $this->renderer = $PAGE->get_renderer('block_teacher_checklist');
    }

    /**
     * Builds a raw issue the way the scanner and the block produce them.
     *
     * @param array $overrides Keys that replace the defaults.
     * @return array The issue.
     */
    private function make_issue(array $overrides = []): array {
        return $overrides + [
            'id' => 'auto_1',
            'type' => 'auto',
            'subtype' => 'mod_assign_nodate',
            'docid' => 15,
            'title' => 'Assignment without due date: Essay',
            'url' => new moodle_url('/mod/assign/view.php', ['id' => 15]),
            'icon' => 'i/hide',
            'status' => 0,
            'severity' => 'normal',
        ];
    }

    /**
     * Manual items are identified by their own record id, automatic ones by the activity id.
     */
    public function test_item_id_comes_from_the_record_for_manual_and_from_docid_for_auto(): void {
        $auto = $this->renderer->prepare_issue_for_template($this->make_issue(), 3, 'pending');
        $manual = $this->renderer->prepare_issue_for_template(
            $this->make_issue(['type' => 'manual', 'id' => 42, 'docid' => 0, 'url' => '#']),
            3,
            'pending'
        );

        $this->assertSame(15, $auto['docid']);
        $this->assertSame(42, $manual['docid']);
        $this->assertSame(3, $auto['courseid']);
    }

    /**
     * A moodle_url icon and a pix name both end up as a plain URL string.
     */
    public function test_icon_is_always_a_url_string(): void {
        $fromname = $this->renderer->prepare_issue_for_template($this->make_issue(), 3, 'pending');
        $fromurl = $this->renderer->prepare_issue_for_template(
            $this->make_issue(['icon' => new moodle_url('/pix/i/hide.svg')]),
            3,
            'pending'
        );

        $this->assertIsString($fromname['icon']);
        $this->assertStringContainsString('hide', $fromname['icon']);
        $this->assertIsString($fromurl['icon']);
        $this->assertStringContainsString('/pix/i/hide.svg', $fromurl['icon']);
    }

    /**
     * The placeholder '#' is not a link, a real URL is kept.
     */
    public function test_placeholder_url_is_not_rendered_as_link(): void {
        $manual = $this->renderer->prepare_issue_for_template($this->make_issue(['url' => '#']), 3, 'pending');
        $auto = $this->renderer->prepare_issue_for_template($this->make_issue(), 3, 'pending');

        $this->assertFalse($manual['url']);
        $this->assertNotFalse($auto['url']);
    }

    /**
     * Markup typed into a manual item title never reaches the page as markup.
     */
    public function test_title_is_sanitised(): void {
        $context = $this->renderer->prepare_issue_for_template(
            $this->make_issue(['title' => '<script>alert(1)</script><b>Grade</b> exams']),
            3,
            'pending'
        );

        $this->assertStringNotContainsString('<script', $context['title']);
        $this->assertStringNotContainsString('<b>', $context['title']);
        $this->assertStringContainsString('Grade', $context['title']);
    }

    /**
     * Each tab offers only the actions that make sense for its items.
     *
     * @dataProvider mode_provider
     * @param string $mode Tab the item is rendered in.
     * @param string $type Item type.
     * @param array $expected Expected flags.
     */
    public function test_actions_depend_on_mode_and_type(string $mode, string $type, array $expected): void {
        $context = $this->renderer->prepare_issue_for_template($this->make_issue(['type' => $type]), 3, $mode);

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $context[$key], $key);
        }
    }

    /**
     * Data provider for test_actions_depend_on_mode_and_type.
     *
     * @return array Test cases.
     */
    public static function mode_provider(): array {
        return [
            'pending manual' => ['pending', 'manual', ['can_mark_done' => true, 'can_ignore' => true,
                'can_restore' => false, 'isdone' => false]],
            'pending auto' => ['pending', 'auto', ['can_mark_done' => false, 'can_ignore' => true,
                'can_restore' => false, 'isdone' => false]],
            'ignored' => ['ignored', 'auto', ['can_mark_done' => false, 'can_ignore' => false,
                'can_restore' => true, 'isdone' => false]],
            'done' => ['done', 'manual', ['can_mark_done' => false, 'can_ignore' => false,
                'can_restore' => true, 'isdone' => true]],
        ];
    }

    /**
     * Only high severity issues get the high flag.
     */
    public function test_severity_flag(): void {
        $high = $this->renderer->prepare_issue_for_template($this->make_issue(['severity' => 'high']), 3, 'pending');
        $normal = $this->renderer->prepare_issue_for_template($this->make_issue(), 3, 'pending');
        $unset = $this->make_issue();
        unset($unset['severity']);
        $missing = $this->renderer->prepare_issue_for_template($unset, 3, 'pending');

        $this->assertTrue($high['severity_high']);
        $this->assertFalse($normal['severity_high']);
        $this->assertFalse($missing['severity_high']);
    }

    /**
     * The block summary shows the first five issues and hides the rest behind the counter.
     */
    public function test_block_summary_limits_visible_issues_to_five(): void {
        $issues = [];
        for ($i = 1; $i <= 7; $i++) {
            $issues[] = $this->make_issue(['id' => 'auto_' . $i, 'docid' => $i, 'title' => "Issue number {$i}"]);
        }

        $html = $this->renderer->render_block_summary($issues, 3, true);
        $fiveonly = $this->renderer->render_block_summary(array_slice($issues, 0, 5), 3, true);

        // The template itself uses d-none once, so the two extra issues are what the larger list adds.
        $this->assertSame(2, substr_count($html, 'd-none') - substr_count($fiveonly, 'd-none'));
        $this->assertStringContainsString('Issue number 7', $html);
        $this->assertStringContainsString(get_string('andmore', 'block_teacher_checklist', 2), $html);
        $this->assertStringContainsString('/blocks/teacher_checklist/view.php?id=3', $html);
    }

    /**
     * Without issues the block says there is nothing to do and offers no counter.
     */
    public function test_block_summary_without_issues(): void {
        $html = $this->renderer->render_block_summary([], 3, true);

        $this->assertStringContainsString(get_string('no_issues_found', 'block_teacher_checklist'), $html);
        $this->assertStringNotContainsString(get_string('andmore', 'block_teacher_checklist', 1), $html);
    }
}
