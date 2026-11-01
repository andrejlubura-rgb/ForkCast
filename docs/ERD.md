# ForkCast: Entity-Relationship Diagram

```mermaid
erDiagram
    USERS ||--o{ RECIPES : writes
    CATEGORIES ||--o{ RECIPES : classifies
    RECIPES ||--|{ RECIPE_INGREDIENTS : contains
    USERS ||--o{ MEAL_PLANS : owns
    MEAL_PLANS ||--o{ PLANNED_MEALS : schedules
    RECIPES ||--o{ PLANNED_MEALS : "is planned as"

    USERS {
        int id PK
        string first_name
        string last_name
        string email UK
        string password_hash
        string role
        datetime created_at
    }
    CATEGORIES {
        int id PK
        string name UK
        string description
    }
    RECIPES {
        int id PK
        int user_id FK
        int category_id FK
        string title
        text instructions
        int prep_time_minutes
        int servings
        string image_url
        string source
        string external_id
        datetime created_at
    }
    RECIPE_INGREDIENTS {
        int id PK
        int recipe_id FK
        string name
        string measure
    }
    MEAL_PLANS {
        int id PK
        int user_id FK
        string name
        date week_start_date
        datetime created_at
    }
    PLANNED_MEALS {
        int id PK
        int plan_id FK
        int recipe_id FK
        int day_of_week
        string meal_type
        int servings
    }
```

## Relationships

| Relationship | Meaning |
|---|---|
| users 1—N recipes | A user writes many recipes; each recipe has one author |
| categories 1—N recipes | A category contains many recipes; each recipe has one category |
| recipes 1—N recipe_ingredients | A recipe has one or more ingredients |
| users 1—N meal_plans | A user owns many weekly plans |
| meal_plans 1—N planned_meals | A plan contains many planned meals |
| recipes 1—N planned_meals | A recipe can be planned many times. Through `planned_meals`, plans and recipes form a many-to-many relationship. |

## Constraints

- `users.email` and `categories.name` are unique.
- `recipes`: unique on (`user_id`, `external_id`), so a user cannot import the same TheMealDB meal twice. `external_id` is empty for own recipes.
- `meal_plans`: unique on (`user_id`, `week_start_date`).
- `planned_meals`: unique on (`plan_id`, `day_of_week`, `meal_type`), so each slot holds one meal.
- Allowed values: `role` is `admin` or `user`; `source` is `own` or `mealdb`; `meal_type` is `breakfast`, `lunch`, `dinner` or `snack`; `day_of_week` is 1 to 7.
- Ranges: `prep_time_minutes` 1 to 600, `servings` 1 to 20.

## Deletion rules

- Deleting a user deletes their recipes and plans (cascade).
- Deleting a recipe deletes its ingredients (cascade).
- Deleting a plan deletes its planned meals (cascade).
- A recipe used in a plan cannot be deleted (restrict).
- A category that has recipes cannot be deleted (restrict).