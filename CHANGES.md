# Changelog — Teacher Checklist

All notable changes to this project will be documented in this file.

---

## [v1.0.0] — 2026-04-26

First public release.

### Automatic checks (9 total)

1. **Course visibility** — flags courses hidden from students.
2. **Course summary** — flags courses with no summary or description.
3. **Course end date** — flags courses with no end date configured.
4. **Gradebook** — flags courses with no grade items configured.
5. **Assignments** — flags missing due date, missing description, and submissions awaiting grading.
6. **Quizzes** — flags quizzes with no questions, no time limit or close date, and attempts awaiting manual grading.
7. **Forums** — flags forums with no discussion topics and forums with no description. The built-in Announcements forum is excluded from both checks.
8. **Completion tracking** — flags visible activities with completion tracking disabled.
9. **Empty sections** — flags visible course sections with no content.

### Manual items

- Teachers can add custom tasks via a free-text field on the dashboard.
- If the task title matches an existing activity name, a link is created automatically.
- Manual items are preserved during course backup and restore.

### Status management

- Each item (automatic or manual) can be marked as **Done**, **Ignored**, or restored to **Pending**.
- Bulk actions allow updating multiple items at once.
- Automatic scanning can be toggled off to use the block as a pure manual checklist.

### Technical

- Full Privacy API implementation (data export and deletion).
- Backup and restore support for manual items.
- AMD JavaScript module (`block_teacher_checklist/actions`).
- Moodle External API for status toggling via AJAX.
- PHPUnit test suite covering all automatic checks.
- CI pipeline tested against Moodle 4.5, 5.0, 5.1, and 5.2.
