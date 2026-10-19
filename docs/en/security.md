# 🔐 Security & Compliance

* **Capability-based access:** the block, the dashboard and the status web service all require `moodle/course:update` in the course context.
* **Course isolation:** a manual item is always looked up together with the course it was requested for, so editing one course never reaches another course's items.
* **CSRF protection:** the dashboard form is processed only with a valid `sesskey`.
* **Typed input and safe output:** titles are read as text parameters and rendered through `format_string()`; the status accepts only the three allowed values.
* **Moodle External API:** the AJAX actions use a standard web service through `core/ajax`.
* **Full Privacy API:** metadata, export and deletion for the checklist items, including user lists.
* **Clean-up on course deletion:** an event observer removes a course's items when the course is deleted.
* **Backup and restore:** manual items and the automatic-scan switch are included; user ids are remapped on restore.
* **No external services and no third-party libraries:** the block never contacts an outside service.
