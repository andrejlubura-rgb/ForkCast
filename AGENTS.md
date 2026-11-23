# AGENTS.md

## Project
ForkCast: a meal planner where users save recipes (their own or imported from TheMealDB), plan their week and get an automatic shopping list.
Roles: admin, user. Entities (6): users, categories, recipes, recipe_ingredients, meal_plans, planned_meals.

## Technology stack
- Frontend: HTML, CSS, JavaScript, jQuery 3.7.1, Bootstrap 5.3.3 (CDN), single-page application (SPA). Only HTML, CSS and JS: no PHP files in frontend/.
- Backend: pure PHP 7.4+ (no PHP 8-only syntax), PDO, MySQL (MariaDB in XAMPP). FlightPHP (REST API) from Milestone 3, JWT authentication from Milestone 4. The backend returns only JSON (no HTML output).
- External API: TheMealDB (search, lookup, random, filter, categories), called only from the backend (Milestone 3).
- Libraries: only Bootstrap 5.3.3 and jQuery 3.7.1 for now. Planned: Composer packages for FlightPHP and JWT (Milestones 3-4), DataTables (admin tables) and Highcharts (optional statistics) in Milestone 4. Any other library: ask me first.
- Local server: XAMPP. App URL: http://localhost/forkcast/ForkCast/frontend/

## Folder structure
- frontend/: index.html, css/style.css, js/ (app.js, router.js, home.js, navbar.js, services/), views/ (one HTML fragment per view), assets/images/
- backend/: index.php (entry point, from S2), .htaccess, composer.json and composer.lock (Composer packages; vendor/ is generated and not committed), bootstrap.php (autoloader), dao/ (Database, BaseDao and one DAO per entity), services/ (one service per entity, plus Validator, Access and MealDbService), exceptions/ (ApiException), routes/ (FlightPHP route files and helpers.php), middleware/ (CurrentUser and Logger for now), openapi/ (OpenAPI attribute classes), swagger/ (index.html and the generated openapi.json), database/forkcast.sql (schema and sample data), tests/ (dao_smoke_test.php, api_smoke_test.php), config/ (config.example.php), logs/
- docs/: AI_LOG.md, ERD.md, ERD.png, API.md (specification of every endpoint)   
- AI files in the repository root: AGENTS.md, opencode.json, .opencode/, .agents/, .github/

## Frontend rules
- index.html is the only full HTML page. Each view is an HTML fragment (no html/head/body tags) in frontend/views/, loaded once by js/router.js.
- Navigation uses hash links (#recipes, #planner, ...).
- All views share one DOM: prefix every id with the view name (recipes-search, planner-add-modal).
- Use relative paths (css/style.css, views/login.html), never a leading slash.
- Bootstrap 5 classes only. Never use Bootstrap 4 syntax (ml-/mr-, text-left, float-left, data-toggle, form-group, badge-*). Avoid inline styles.
- No images from external sites and no icon libraries; use placeholder boxes and unicode characters.
- Every view must be mobile friendly: it works at 375px width (Bootstrap grid, table-responsive for wide tables).
- Hard-coded data (sample rows, cards, dropdown options for categories and recipes) is wrapped in <!-- STATIC-SAMPLE --> comments and replaced by API data in Milestone 4. Fixed lists (weekdays, meal types) may stay static.
- Views contain no AJAX calls. Only js/services/*.js talk to the backend (from Milestone 4).

## Backend rules
Architecture
- Requests go: route -> service -> DAO -> database. Only services call DAO classes; routes never do.
- Routes parse input, call a service and return JSON with correct HTTP status codes. Services hold validation and business rules.

Database and DAO layer (Milestone 2)
- The database is `forkcast` (InnoDB, utf8mb4), defined in backend/database/forkcast.sql. Foreign keys are named fk_<table>_<column>.
- Unique keys: users.email, categories.name, (recipes.user_id, external_id), (meal_plans.user_id, week_start_date), (planned_meals.plan_id, day_of_week, meal_type).
- ON DELETE CASCADE: recipes.user_id, recipe_ingredients.recipe_id, meal_plans.user_id, planned_meals.plan_id. ON DELETE RESTRICT: recipes.category_id, planned_meals.recipe_id.
- Allowed values: role admin/user; recipes.source own/mealdb; meal_type breakfast/lunch/dinner/snack; day_of_week 1-7; prep_time_minutes 1-600; servings 1-20.
- backend/bootstrap.php registers an autoloader for backend/dao, backend/services, backend/middleware and backend/exceptions. Classes are never loaded with require inside other classes.
- Database (backend/dao/Database.php) creates one shared PDO connection from backend/config/config.php (ERRMODE_EXCEPTION, FETCH_ASSOC, EMULATE_PREPARES off).
- Every DAO extends BaseDao (getAll, getById, insert, update, delete). Each DAO declares a whitelist of columns that insert() and update() may write.
- update() returns false only when there is nothing valid to write, and true otherwise, even if no row changed. delete() returns true if a row was deleted. Checking that a row exists is the service's job.
- DAO classes contain only SQL: no validation, no business rules, no HTTP, no echo. They return arrays.
- Every value goes into the query as a bound parameter. Never concatenate values into SQL. Use a different named placeholder for each use of a value in one query. Bind LIMIT and OFFSET as integers.
- Never select password_hash in joins or list queries unless the method is only for authentication.

Configuration and secrets
- Database credentials, the JWT secret and the TheMealDB key live only in backend/config/config.php (not committed). Commit config.example.php instead.
- Passwords in sample data and documentation are demo passwords only. Never add a real password anywhere.

Business rules (implemented in services from Milestone 3)
- Only the owner or an admin edits or deletes a recipe.
- A recipe used in a plan cannot be deleted. A category with recipes cannot be deleted.
- One meal per plan slot (day and meal type).
- Importing the same TheMealDB meal twice returns the existing recipe.
- TheMealDB: 5 second timeout, handle {"meals": null} and errors, log failures, return a friendly message.

Planned later (do not implement before the milestone)
- Milestone 3: services, FlightPHP routes, OpenAPI documentation with a Swagger UI page, the TheMealDB service and endpoint.
- Milestone 4: JWT authentication (passwords hashed with password_hash(), 401 if the token is missing, invalid or expired), authorization middleware (403), request logging middleware, error handling middleware (JSON message, no stack traces, SQL or file paths), info and error logs in backend/logs/, connected frontend.
- Milestone 5: frontend services layer, client and server validation, deployment.

## Conventions
- PHP classes: PascalCase (UserService, UserDao). Files are named after the class.
- JavaScript and PHP variables: camelCase. Database tables and columns: snake_case.

## Rules for AI assistants
- Do not add new libraries or frameworks without asking me first.
- Do not change the database schema without asking me first.
- Never put passwords, keys or secrets in code; they belong in backend/config/config.php, which is not committed. Update config.example.php instead.
- Do not read or edit backend/config/config.php, and do not edit files in backend/logs/.
- Do not run git commit or git push; I do that myself.
- Do not test, run or open the project yourself unless I ask you to.
- Make small changes. At the end, list the changed files with one line each. Do not paste full code into the chat.

## Backend rules (Milestone 3 additions)
- backend/index.php is the only entry point (FlightPHP installed with Composer in backend/). backend/.htaccess sends every request to it. Route files in backend/routes/ are loaded by index.php; shared helpers are in routes/helpers.php.
- Routes contain no SQL and never touch DAOs: identify the user (CurrentUser), read the JSON body (jsonBody()), call one service method, return Flight::json(data, status).
- Services: one per entity, the only layer that calls DAOs. Validation (Validator), access rules (Access) and business rules live in services. They throw ApiException (400, 401, 403, 404, 503); they never echo and never call Flight.
- The error handler in index.php turns ApiException into a JSON error. Any other exception is written to backend/logs/error.log and answered with a generic 500. Responses never contain stack traces, SQL or file paths.
- docs/API.md specifies every endpoint and rule. Implement what it says and do not add endpoints.
- TEMPORARY until Milestone 4: CurrentUser identifies the caller by the X-User-Id header. Milestone 4 replaces it with JWT middleware.
- OpenAPI attributes live in backend/openapi/ (not in the route files). The generated spec is backend/swagger/openapi.json and the Swagger UI page is backend/swagger/index.html.
- Composer packages: flightphp/core and zircote/swagger-php (dev). Nothing else without asking. backend/openapi/ may use PHP 8 attributes; the other backend code stays PHP 7.4 compatible.

## Current milestone
Milestone 3: services, FlightPHP routes, OpenAPI documentation with Swagger UI, and the TheMealDB external API. Milestones 1 and 2 are complete. No JWT or middleware yet (Milestone 4). The frontend stays static.