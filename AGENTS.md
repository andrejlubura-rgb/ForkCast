# AGENTS.md

## Project
ForkCast: a meal planner where users save recipes (their own or imported from TheMealDB), plan their week and get an automatic shopping list.
Roles: admin, user. Entities: users, categories, recipes, recipe_ingredients, meal_plans, planned_meals.

## Technology stack
- Frontend: HTML, CSS, JavaScript, jQuery 3.7.1, Bootstrap 5.3.3 (CDN), single-page application (SPA)
- Backend: PHP with FlightPHP (REST API), PDO, MySQL, JWT for authentication
- External API: TheMealDB (search, lookup, random, filter, categories), called only from the backend
- Allowed libraries: Bootstrap 5.3.3, jQuery 3.7.1. Planned from Milestone 4: DataTables (admin tables) and Highcharts (optional statistics). Any other library: ask me first.
- Frontend contains only HTML, CSS and JavaScript (no PHP files). Backend is pure PHP and returns only JSON (no HTML output).
- Local server: XAMPP. App URL: http://localhost/forkcast/ForkCast/frontend/

## Folder structure
- frontend/: index.html, css/, js/ (router.js, app.js, services/), views/ (one HTML fragment per view), assets/
- backend/: routes/, services/, dao/, middleware/, config/, logs/
- docs/: AI_LOG.md, ERD.md and other documentation

## Frontend rules
- index.html is the only full HTML page. Each view is an HTML fragment (no html/head/body tags) in frontend/views/, loaded once by js/router.js.
- Navigation uses hash links (#recipes, #planner, ...).
- All views share one DOM: prefix every id with the view name (recipes-search, planner-add-modal).
- Use relative paths (css/style.css, views/login.html), never a leading slash.
- Bootstrap 5 classes only. Never use Bootstrap 4 syntax (ml-/mr-, text-left, float-left, data-toggle, form-group, badge-*). Avoid inline styles.
- No images from external sites and no icon libraries; use placeholder boxes and unicode characters.
- Static sample data is wrapped in <!-- STATIC-SAMPLE --> comments so it can be removed in Milestone 4.
- Views contain no AJAX calls. Only js/services/*.js talk to the backend (from Milestone 4).
- Every view must be mobile friendly: it works at 375px width (Bootstrap grid, table-responsive for wide tables).
- Hard-coded data, including dropdown options for categories and recipes, is wrapped in <!-- STATIC-SAMPLE --> and replaced by API data in Milestone 4. Fixed lists (weekdays, meal types) may stay static.

## Backend rules (from Milestone 2)
- Requests go: route -> service -> DAO -> database. Only services call DAO classes.
- All SQL uses PDO prepared statements. Routes return JSON with correct HTTP status codes.
- Authentication: passwords are hashed with password_hash(). A JWT is issued at login and verified by authentication middleware on protected routes (401 if missing, invalid or expired).
- Reusable FlightPHP middleware: authentication (401), authorization role check (403), request logging, error handling (JSON error message, no stack traces, SQL or file paths).
- Logging: info log and error log in backend/logs/ (info.log, error.log), as described in the Project Requirements.
- Every endpoint is documented with OpenAPI; a Swagger UI page is provided (Milestone 3).
- TheMealDB: 5 second timeout, handle {"meals": null} and errors, log failures, return a friendly message.
- Database credentials, the JWT secret and the TheMealDB key live only in backend/config/config.php (not committed). Commit config.example.php instead.
- Business rules: only the owner or an admin edits a recipe; a recipe used in a plan cannot be deleted; a category with recipes cannot be deleted; one meal per plan slot; importing the same TheMealDB meal twice returns the existing recipe.


## Conventions
- PHP classes: PascalCase (UserService, UserDao). Files are named after the class.
- JavaScript and PHP variables: camelCase. Database tables and columns: snake_case.

## Rules for AI assistants
- Do not add new libraries or frameworks without asking me first.
- Do not change the database schema without asking me first.
- Never put passwords, keys or secrets in code; they belong in backend/config/config.php, which is not committed. Update config.example.php instead.
- Do not read or edit backend/config/config.php, and do not edit files in backend/logs/.
- Do not run git commit or git push; I do that myself.
- Make small changes. At the end, list the changed files with one line each. Do not paste full code into the chat.
- Do not test anything yourselves unless I state that.

## Current milestone
Milestone 1: project structure, static SPA frontend, ERD draft, README draft. No backend code yet.