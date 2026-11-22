# ForkCast API specification (Milestone 3)

Base URL (local): http://localhost/forkcast/ForkCast/backend

## Conventions
- JSON requests and responses.
- Success: 200 for GET, PUT and DELETE; 201 for a POST that creates something. POST and PUT return the saved object. DELETE returns {"message": "Deleted"}.
- Errors always look like {"error": "message"}. Validation errors add "errors": {"field": "message"}.
- 400 invalid input or a business rule is violated; 401 no identified user; 403 the user is not allowed; 404 not found; 500 unexpected error (generic message, no details); 503 TheMealDB unavailable.
- Lists accept limit (default 20, max 100) and offset (default 0). Dates are YYYY-MM-DD. password_hash is never returned.
- TEMPORARY until Milestone 4: the caller is identified by the header X-User-Id (id of an existing user). Missing or unknown id = 401. Milestone 4 replaces this with JWT.
- Access: public, user (any identified user), admin, owner-or-admin, self-or-admin.

## Users
| Method | Path | Access | Success | Errors |
|---|---|---|---|---|
| GET | /users | admin | 200 list | 401, 403 |
| GET | /users/{id} | self-or-admin | 200 | 401, 403, 404 |
| POST | /users | public | 201 | 400 |
| PUT | /users/{id} | self-or-admin | 200 | 400, 401, 403, 404 |
| DELETE | /users/{id} | admin | 200 | 400, 401, 403, 404 |
Rules: first_name and last_name 1-50 chars; email valid, max 100, unique; password 8-72 chars (stored with password_hash(), optional on PUT); POST always creates role "user"; only an admin may change a role (otherwise 403). DELETE is 400 if a database constraint blocks it (the user's recipes are used in other users' plans).

## Categories
| Method | Path | Access | Success | Errors |
|---|---|---|---|---|
| GET | /categories | user | 200 list | 401 |
| GET | /categories/{id} | user | 200 | 401, 404 |
| POST | /categories | admin | 201 | 400, 401, 403 |
| PUT | /categories/{id} | admin | 200 | 400, 401, 403, 404 |
| DELETE | /categories/{id} | admin | 200 | 400, 401, 403, 404 |
Rules: name 2-50 chars and unique; description optional, max 255. DELETE is 400 if the category has recipes.

## Recipes
| Method | Path | Access | Success | Errors |
|---|---|---|---|---|
| GET | /recipes | user | 200 list | 401 |
| GET | /recipes/{id} | user | 200 (with ingredients) | 401, 404 |
| POST | /recipes | user | 201 | 400, 401 |
| PUT | /recipes/{id} | owner-or-admin | 200 | 400, 401, 403, 404 |
| DELETE | /recipes/{id} | owner-or-admin | 200 | 400, 401, 403, 404 |
| POST | /recipes/import/{externalId} | user | 201 new, 200 already imported | 400, 401, 404, 503 |
Query for GET /recipes: search (title), category_id, mine=true (only the caller's recipes), limit, offset.
Body for POST and PUT: category_id (must exist), title 3-100, instructions at least 10 chars, prep_time_minutes 1-600, servings 1-20, image_url optional valid URL max 500, ingredients: array of at least 1 {name 1-100, measure optional max 50}. On PUT, ingredients is optional; if present it replaces all ingredients. source is "own" and user_id is the caller; neither can be set or changed by the client.
Rules: a recipe used in any plan cannot be deleted (400). Create and replace of ingredients happen in one database transaction.

## Ingredients
| Method | Path | Access | Success | Errors |
|---|---|---|---|---|
| GET | /recipes/{id}/ingredients | user | 200 list | 401, 404 |
| POST | /recipes/{id}/ingredients | owner-or-admin of the recipe | 201 | 400, 401, 403, 404 |
| PUT | /ingredients/{id} | owner-or-admin of the recipe | 200 | 400, 401, 403, 404 |
| DELETE | /ingredients/{id} | owner-or-admin of the recipe | 200 | 400, 401, 403, 404 |
Rules: name 1-100, measure optional max 50. The last ingredient of a recipe cannot be deleted (400).

## Meal plans
| Method | Path | Access | Success | Errors |
|---|---|---|---|---|
| GET | /plans | user (own plans; admin sees all) | 200 list | 401 |
| GET | /plans/{id} | owner-or-admin | 200 (with its planned meals) | 401, 403, 404 |
| POST | /plans | user | 201 | 400, 401 |
| PUT | /plans/{id} | owner-or-admin | 200 | 400, 401, 403, 404 |
| DELETE | /plans/{id} | owner-or-admin | 200 | 401, 403, 404 |
| GET | /plans/{id}/shopping-list | owner-or-admin | 200 list of {name, measures, times_used} | 401, 403, 404 |
Rules: name 1-100; week_start_date a valid date that is a Monday; one plan per user per week (400 if it exists); user_id is the caller. Deleting a plan deletes its planned meals.

## Planned meals
| Method | Path | Access | Success | Errors |
|---|---|---|---|---|
| GET | /plans/{id}/meals | owner-or-admin of the plan | 200 list | 401, 403, 404 |
| POST | /plans/{id}/meals | owner-or-admin of the plan | 201 | 400, 401, 403, 404 |
| PUT | /planned-meals/{id} | owner-or-admin of the plan | 200 | 400, 401, 403, 404 |
| DELETE | /planned-meals/{id} | owner-or-admin of the plan | 200 | 401, 403, 404 |
Rules: recipe_id must exist; day_of_week 1-7; meal_type breakfast, lunch, dinner or snack; servings 1-20 (default 1); a plan slot (day + meal type) holds one meal (400 if taken; on PUT the meal's own slot does not count).

## External API: TheMealDB
| Method | Path | Access | Success | Errors |
|---|---|---|---|---|
| GET | /external/meals/search | user | 200 list (can be empty) | 400, 401, 503 |
| GET | /external/meals/random | user | 200 | 401, 503 |
| GET | /external/meals/{id} | user | 200 | 401, 404, 503 |
Query for search: exactly one of q (by name), category, area, ingredient; otherwise 400.
Simplified meal object: {id, name, category, area, instructions, thumbnail, tags, ingredients: [{name, measure}]}. Filter results (category, area, ingredient) only contain {id, name, thumbnail}.
TheMealDB: base URL and key from config (mealdb.base_url, mealdb.api_key, mealdb.timeout). Endpoints: search.php?s=, filter.php?c= or a= or i=, lookup.php?i=, random.php. A meal has strIngredient1..20 and strMeasure1..20; skip empty or null ones. "meals" can be null: empty list for searches, 404 for a lookup. Timeout 5 seconds, connect timeout 3. A cURL error, a non-200 status or invalid JSON: write to the error log and answer 503 "TheMealDB is unavailable right now. You can still add your own recipes."
Import (POST /recipes/import/{externalId}): look the meal up; if the caller already imported it, return the existing recipe with 200. Otherwise find the category by name (case-insensitive), create it if missing, and create the recipe with the caller as owner, source "mealdb", external_id, title strMeal, instructions strInstructions, thumbnail as image_url, prep_time_minutes 30 and servings 4 (TheMealDB has neither), plus all ingredients, in one transaction.