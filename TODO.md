# TODO

## Edit permission view fixes
- [ ] Fix malformed Blade class attribute in `resources/views/dashboard/permissions/edit.blade.php` (currently contains an extra newline/quote sequence around `@endif`).
- [ ] Eager-load role permissions (optional performance/safety) in `PermissionController@edit` so the view can safely call `$role->permissions->count()`.
- [ ] Run a quick Laravel/ PHP lint check (if available) or at least ensure no syntax errors in the changed files.
- [ ] Re-test `/dashboard/permissions/{id}/edit` flow: page renders, checkboxes pre-selected, presets, and update submission works.

