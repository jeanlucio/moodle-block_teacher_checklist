# 🧩 For developers

Other plugins can seed **manual items** into a course through a small public API. The items look like ordinary manual items for the teacher, but they are tagged with the **source** component, so the owning plugin can replace its own set at any time without touching the items the teacher added by hand or items provisioned by other plugins.

```php
\block_teacher_checklist\local\external_tasks::replace(
    $course->id,
    'local_myplugin',
    ['Review the weekly schedule', 'Publish the first activity']
);
```

* `replace(int $courseid, string $source, array $titles)` removes the items previously provisioned under the same `$source` and recreates them from `$titles`, in order. Blank titles are skipped. An empty array clears the source.
* The method performs destructive writes and does **no access control of its own**: the caller **must** check `moodle/course:update` on the course context first.
* Guard the call with `class_exists(\block_teacher_checklist\local\external_tasks::class)` to keep the integration optional.
