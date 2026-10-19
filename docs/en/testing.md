# 🧪 Automated Tests

Every CI push runs the PHPUnit and Behat suites against the supported Moodle versions.

### PHPUnit — Unit & Integration Tests

| Test file | Tests | What is covered |
|-----------|------:|----------------|
| `tests/scanner_test.php` | 29 | The scan switch (default on, on, off); the automatic checks with their negative cases — hidden course, summary, end date, gradebook, assignments, forums (Announcements excluded), empty quizzes, completion tracking; ignored status; hidden activities not reported |
| `tests/scanner_grading_test.php` | 7 | The grading backlog, built through the real `mod_assign` and `mod_quiz` APIs: pending until graded, team submissions, resubmission after grading, suspended students, essay attempts, teacher previews, hidden quizzes |
| `tests/renderer_test.php` | 8 | Item ids, icon handling, the `#` placeholder never rendered as a link, title sanitising, actions per tab, severity flag, the five-item limit of the block summary, the empty state |
| `tests/checklist_block_test.php` | 5 | The block shows pending manual items to a teacher and nothing to a student, content cached per block object, no automatic issues when the scan is off, allowed page formats |
| `tests/external_test.php` | 9 | The status web service: invalid status rejected, capability required, auto items created and updated, manual items updated and keeping their author, unknown manual item |
| `tests/external_tasks_test.php` | 5 | The public API: insert, idempotency, other sources and teacher items preserved, blank titles skipped, clearing a source |
| `tests/observer_test.php` | 1 | Deleting a course removes only that course's items |
| `tests/backup_restore_test.php` | 3 | A real course backup and restore: scan switch and manual items travel, automatic items do not, the destination keeps its own switch |
| `tests/privacy/privacy_test.php` | 10 | Metadata declaring every column of the table, context and user discovery, localised export, deletion per user, per context and for a user list, and the erasure keeping the course-wide state and the items of other teachers |
| **Total** | **77** | |

### Behat — Acceptance Tests

| Feature | Scenarios | What is covered |
|---------|----------:|----------------|
| `tests/behat/manual_items.feature` | 3 | Adding a manual item in the dashboard, seeing it in the course block, markup typed into a title shown as plain text |
| `tests/behat/access.feature` | 2 | A student does not see the block; a teacher does |
