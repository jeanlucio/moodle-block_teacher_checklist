# 🔍 Automatic checks

Each check runs on the activities that are **visible to students**; hidden activities are not reported. Every issue can be marked as Ignored, and an ignored issue stays ignored until restored.

| # | Check | What it detects |
|---|-------|----------------|
| 1 | Course visibility | Course is hidden from students |
| 2 | Course summary | Course has no summary or description |
| 3 | Course end date | Course has no end date configured |
| 4 | Gradebook | No grade items configured |
| 5 | Assignments | Missing due date, missing description, submissions awaiting grading |
| 6 | Quizzes | No questions, no close date and no time limit, attempts awaiting manual grading |
| 7 | Forums | No discussion topics; no description (the Announcements forum is excluded) |
| 8 | Completion tracking | Visible activities with completion tracking disabled, when the course has completion enabled |
| 9 | Empty sections | Visible course sections with no activities and no summary |

### How "awaiting grading" is counted

* **Assignments:** only the **latest attempt** of a participant with an **active enrolment** counts. It is pending when it has no grade yet, or when it was edited after being graded. Team assignments are counted through the members' own submissions, so the shared team row never keeps the alert alive after everyone is graded.
* **Quizzes:** finished attempts whose total mark is still empty (typically because of an essay question). The teacher's own **preview** attempts are not counted.
