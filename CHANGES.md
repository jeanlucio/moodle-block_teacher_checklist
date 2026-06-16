# Changelog — Teacher Checklist

All notable changes to this project will be documented in this file.

---

## [v1.3.0] — 2026-06-16

### Added

- **Public API for seeding manual items from other plugins** —
  `block_teacher_checklist\local\external_tasks::replace()` lets a companion
  plugin provision a list of manual checklist items into a course. Items are
  tagged with the requesting component in the `subtype` column, so the source
  plugin can replace its own set without touching items the teacher added by
  hand. Used by `local_virtuallab` to push a shared task list into every lab.

---

## [v1.2.3] — 2026-06-16

### Fixed

- **PHP 8.1+ deprecation on courses with no summary** — `strip_tags()` was
  called directly on `course->summary`, which is `null` for courses created
  without a summary (a valid value per the core schema). The value is now
  cast to string before stripping tags.

---

## [v1.2.2] — 2026-05-17

### Fixed

- **All-clear alert visible alongside pending items** — the alert had a
  hardcoded `d-flex` and a conditional `d-none` on the same element; in both
  Bootstrap 4 and 5, `d-flex` wins because it appears later in the compiled
  stylesheet. Only one display utility is now applied at a time.

---

## [v1.2.1] — 2026-05-14

### Added

- Plugin icon (`pix/icon.svg`).

---

## [v1.2.0] — 2026-05-14

### Improved

- **Block compact view — instant item removal** — ignoring or marking an item
  as done in the block sidebar now fades it out immediately, without briefly
  showing the restore button first.
- **Block compact view — dynamic list refill** — when an item is removed, the
  next hidden item slides into the visible area automatically.
- **Block compact view — live counter** — the "and N more…" label decrements
  as items are dismissed; it hides itself when the count reaches zero. When
  all items are resolved the all-clear message appears without a page reload.

### Fixed

- **Hidden items on first render** — `array_filter` preserved non-contiguous
  array keys from the merged auto+manual issues list, causing the renderer to
  apply `d-none` to visible items instead of only the overflow ones.

---

## [v1.1.0] — 2026-05-03

### Fixed

- **Bootstrap 4 / Moodle 4.5 compatibility** — toggle switch and bulk
  checkboxes now use `position-static m-0` to neutralise Bootstrap 4's
  `position: absolute` on `.form-check-input`, preventing them from
  overlapping adjacent text.
- **Tabs not switching in Moodle 4.5** — added `data-toggle` / `data-target`
  alongside `data-bs-toggle` / `data-bs-target` on all tab buttons and the
  collapse link so Bootstrap 4 and Bootstrap 5 both respond correctly.
- **Completion tracking false positives** — `scan_completion_disabled()` now
  returns no issues when the course has `enablecompletion = 0`; the teacher
  cannot configure per-activity completion in that case.
- **Bulk "Marcar como Feito" acting on auto items** — the action is now
  filtered to manual items only (`data-markable`); auto items are not sent
  to the server and are not removed from the screen.
- **Badge text colour** — explicit `text-white` on `bg-danger`/`bg-success`
  and `text-dark` on `bg-secondary` ensure correct contrast in both Bootstrap
  versions.

### Improved

- **Real-time tab updates** — checklist items now move instantly to the
  correct tab (Pending / Done / Ignored) after any action without requiring
  a page reload. Action buttons, `bg-light` styling, badge counts, and
  empty-state messages are all updated in-place.
- **Bulk action counts** — each bulk button now shows the number of items
  it will affect in parentheses, e.g. "Marcar como Feito (1) / Ignorar (3)".
  The "Marcar como Feito" button is hidden when no markable item is selected.
- **Plugin name (pt_BR)** — renamed from "Lista de Verificação do Professor"
  to "Checklist do Professor" for natural Brazilian usage.

### Tests

- `test_scan_completion_disabled_does_not_flag_news_forum` strengthened to
  explicitly enable course-level completion, ensuring the guard is the real
  reason the forum is not flagged.
- New: `test_scan_completion_issues_skipped_when_course_completion_off`
- New: `test_scan_detects_activity_without_completion_when_course_enabled`

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
