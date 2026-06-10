# TODO

## Vite + Dashboard/API separation
- [ ] Fix Vite manifest error by adding `resources/js/home.js` to `vite.config.js` input list.
- [ ] Implement WEB CRUD in existing controllers:
  - [ ] `app/Http/Controllers/BooksController.php`
  - [ ] `app/Http/Controllers/CategoryController.php`
  - [ ] `app/Http/Controllers/ImageController.php`
- [ ] Add API CRUD controllers (JSON only):
  - [ ] `app/Http/Controllers/Api/BooksController.php`
  - [ ] `app/Http/Controllers/Api/CategoryController.php`
  - [ ] `app/Http/Controllers/Api/ImageController.php`
- [ ] Create separate Blade dashboard views under `resources/views/dashboard/...` for books/categories/images (index/create/edit/show).
- [ ] Wire routes:
  - [ ] `routes/web.php` resource routes to WEB controllers under `/dashboard/...`
  - [ ] `routes/api.php` routes under `/api/v1/...` to API controllers.
- [ ] Verify `/home` loads without Vite manifest error.
- [ ] Verify web dashboard CRUD pages render.
- [ ] Verify API CRUD endpoints return JSON.

