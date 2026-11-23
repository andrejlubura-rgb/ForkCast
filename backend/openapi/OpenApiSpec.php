<?php

// OpenAPI metadata, tags and reusable components for the ForkCast API.
// Only the swagger-php CLI reads this folder (composer docs); no request loads these classes.
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'ForkCast API',
    version: '1.0.0',
    description: 'REST API for ForkCast, a meal planner where users save recipes (their own or imported from TheMealDB), plan their week and get an automatic shopping list. Requests and responses are JSON. Until JWT authentication arrives in Milestone 4 the caller is identified by the temporary X-User-Id header.'
)]
#[OA\Server(
    url: 'http://localhost/forkcast/ForkCast/backend',
    description: 'This server'
)]
#[OA\SecurityScheme(
    securityScheme: 'tempUser',
    type: 'apiKey',
    in: 'header',
    name: 'X-User-Id',
    description: 'Temporary identification until JWT (Milestone 4). Use 1 for the admin, 2 for a user.'
)]
#[OA\Tag(
    name: 'Users',
    description: 'Registration and user accounts. An admin lists and deletes users; a user or an admin reads and updates one account.'
)]
#[OA\Tag(
    name: 'Categories',
    description: 'Recipe categories. Any identified user reads them; only an admin creates, updates or deletes one.'
)]
#[OA\Tag(
    name: 'Recipes',
    description: 'Recipes owned by users, including recipes imported from TheMealDB.'
)]
#[OA\Tag(
    name: 'Ingredients',
    description: 'The ingredients of a recipe.'
)]
#[OA\Tag(
    name: 'Meal plans',
    description: 'Weekly meal plans and their shopping lists.'
)]
#[OA\Tag(
    name: 'Planned meals',
    description: 'Meals placed in a plan slot (day and meal type).'
)]
#[OA\Tag(
    name: 'External',
    description: 'Read-only access to TheMealDB for searching, looking up and picking random meals.'
)]
#[OA\Schema(
    schema: 'Error',
    description: 'Standard error response. Every error has an "error" message.',
    type: 'object',
    required: ['error'],
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'Category not found'),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    description: 'Validation error: the standard error object plus one message per invalid field.',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Error'),
        new OA\Schema(
            type: 'object',
            required: ['errors'],
            properties: [
                new OA\Property(
                    property: 'errors',
                    description: 'One message per invalid field, keyed by field name.',
                    type: 'object',
                    additionalProperties: new OA\AdditionalProperties(type: 'string'),
                    example: ['name' => 'The name must be between 2 and 50 characters.']
                ),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'User',
    description: 'A user without the password hash.',
    type: 'object',
    required: ['id', 'first_name', 'last_name', 'email', 'role', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'first_name', type: 'string', minLength: 1, maxLength: 50, example: 'Una'),
        new OA\Property(property: 'last_name', type: 'string', minLength: 1, maxLength: 50, example: 'User'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 100, example: 'user1@forkcast.test'),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'user'], example: 'user'),
        new OA\Property(
            property: 'created_at',
            description: 'Creation timestamp as stored by MySQL.',
            type: 'string',
            example: '2026-11-01 16:31:33'
        ),
    ]
)]
#[OA\Schema(
    schema: 'UserInput',
    description: 'User data for registration (POST) and update (PUT). The password is required on POST and optional on PUT.',
    type: 'object',
    required: ['first_name', 'last_name', 'email'],
    properties: [
        new OA\Property(property: 'first_name', type: 'string', minLength: 1, maxLength: 50, example: 'Una'),
        new OA\Property(property: 'last_name', type: 'string', minLength: 1, maxLength: 50, example: 'User'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 100, example: 'user1@forkcast.test'),
        new OA\Property(
            property: 'password',
            description: 'Required on POST, optional on PUT. Stored with password_hash(); never returned.',
            type: 'string',
            minLength: 8,
            maxLength: 72
        ),
        new OA\Property(
            property: 'role',
            description: 'Only an admin may change a role. On POST the role is always "user".',
            type: 'string',
            enum: ['admin', 'user'],
            example: 'user'
        ),
    ]
)]
#[OA\Schema(
    schema: 'Category',
    description: 'A recipe category.',
    type: 'object',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 4),
        new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 50, example: 'Dessert'),
        new OA\Property(
            property: 'description',
            description: 'Optional; may be null.',
            type: 'string',
            maxLength: 255,
            example: 'Sweet treats and baked desserts.'
        ),
    ]
)]
#[OA\Schema(
    schema: 'CategoryInput',
    description: 'Category data for POST and PUT.',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 50, example: 'Dessert'),
        new OA\Property(
            property: 'description',
            description: 'Optional; may be omitted.',
            type: 'string',
            maxLength: 255,
            example: 'Sweet treats and baked desserts.'
        ),
    ]
)]
#[OA\Schema(
    schema: 'MealPlan',
    description: 'A weekly meal plan. GET /plans/{id}, POST /plans and PUT /plans/{id} also include the planned meals in the "meals" field.',
    type: 'object',
    required: ['id', 'user_id', 'name', 'week_start_date', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 2),
        new OA\Property(property: 'name', type: 'string', minLength: 1, maxLength: 100, example: 'Week of 26 Oct'),
        new OA\Property(
            property: 'week_start_date',
            description: 'Start of the week (a Monday) in YYYY-MM-DD format.',
            type: 'string',
            format: 'date',
            example: '2026-10-26'
        ),
        new OA\Property(
            property: 'created_at',
            description: 'Creation timestamp as stored by MySQL.',
            type: 'string',
            example: '2026-11-01 16:31:33'
        ),
        new OA\Property(
            property: 'meals',
            description: 'The planned meals of this plan.',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PlannedMeal')
        ),
    ]
)]
#[OA\Schema(
    schema: 'MealPlanInput',
    description: 'Meal plan data for POST and PUT. user_id is always the caller and cannot be set by the client.',
    type: 'object',
    required: ['name', 'week_start_date'],
    properties: [
        new OA\Property(property: 'name', type: 'string', minLength: 1, maxLength: 100, example: 'Week of 26 Oct'),
        new OA\Property(
            property: 'week_start_date',
            description: 'A valid date that is a Monday, in YYYY-MM-DD format. Only one plan per user per week.',
            type: 'string',
            format: 'date',
            example: '2026-10-26'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PlannedMeal',
    description: 'A meal placed in one slot of a plan. List responses also include the recipe title and image.',
    type: 'object',
    required: ['id', 'plan_id', 'recipe_id', 'day_of_week', 'meal_type', 'servings'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'plan_id', type: 'integer', example: 1),
        new OA\Property(property: 'recipe_id', type: 'integer', example: 4),
        new OA\Property(
            property: 'day_of_week',
            description: 'Day of the week: 1 (Monday) to 7 (Sunday).',
            type: 'integer',
            minimum: 1,
            maximum: 7,
            example: 2
        ),
        new OA\Property(
            property: 'meal_type',
            type: 'string',
            enum: ['breakfast', 'lunch', 'dinner', 'snack'],
            example: 'breakfast'
        ),
        new OA\Property(
            property: 'servings',
            description: 'Number of servings; defaults to 1.',
            type: 'integer',
            minimum: 1,
            maximum: 20,
            example: 2
        ),
        new OA\Property(
            property: 'recipe_title',
            description: 'Included in list responses only.',
            type: 'string',
            example: 'Pancakes'
        ),
        new OA\Property(
            property: 'recipe_image_url',
            description: 'Included in list responses only; may be null.',
            type: 'string',
            example: 'https://example.com/pancakes.jpg'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PlannedMealInput',
    description: 'Planned meal data for POST and PUT. plan_id comes from the route and cannot be set by the client. One meal per slot (day and meal type).',
    type: 'object',
    required: ['recipe_id', 'day_of_week', 'meal_type'],
    properties: [
        new OA\Property(
            property: 'recipe_id',
            description: 'Id of an existing recipe.',
            type: 'integer',
            example: 4
        ),
        new OA\Property(
            property: 'day_of_week',
            description: '1 (Monday) to 7 (Sunday).',
            type: 'integer',
            minimum: 1,
            maximum: 7,
            example: 2
        ),
        new OA\Property(
            property: 'meal_type',
            type: 'string',
            enum: ['breakfast', 'lunch', 'dinner', 'snack'],
            example: 'breakfast'
        ),
        new OA\Property(
            property: 'servings',
            description: 'Optional; defaults to 1.',
            type: 'integer',
            minimum: 1,
            maximum: 20,
            default: 1,
            example: 2
        ),
    ]
)]
#[OA\Schema(
    schema: 'ShoppingItem',
    description: 'One grouped ingredient of a plan shopping list.',
    type: 'object',
    required: ['name', 'measures', 'times_used'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Eggs'),
        new OA\Property(
            property: 'measures',
            description: 'Distinct measures for this ingredient joined with ", ". May be null when no measures were recorded.',
            type: 'string',
            example: '2 pcs, 1 cup'
        ),
        new OA\Property(
            property: 'times_used',
            description: 'How many planned meals use this ingredient.',
            type: 'integer',
            minimum: 1,
            example: 3
        ),
    ]
)]
#[OA\Schema(
    schema: 'Recipe',
    description: 'A recipe stored by a user. Single-recipe responses include the ingredient lines in the "ingredients" field; list responses do not.',
    type: 'object',
    required: ['id', 'user_id', 'category_id', 'title', 'instructions', 'prep_time_minutes', 'servings', 'source', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 2),
        new OA\Property(property: 'category_id', type: 'integer', example: 8),
        new OA\Property(property: 'title', type: 'string', minLength: 3, maxLength: 100, example: 'Spaghetti Carbonara'),
        new OA\Property(
            property: 'instructions',
            type: 'string',
            example: 'Bring a large pot of salted water to the boil and cook the spaghetti until al dente.'
        ),
        new OA\Property(property: 'prep_time_minutes', type: 'integer', minimum: 1, maximum: 600, example: 25),
        new OA\Property(property: 'servings', type: 'integer', minimum: 1, maximum: 20, example: 4),
        new OA\Property(
            property: 'image_url',
            description: 'Optional valid URL; may be null.',
            type: 'string',
            format: 'uri',
            maxLength: 500,
            example: 'https://example.com/carbonara.jpg'
        ),
        new OA\Property(
            property: 'source',
            type: 'string',
            enum: ['own', 'mealdb'],
            example: 'own'
        ),
        new OA\Property(
            property: 'external_id',
            description: 'TheMealDB id, present on imported recipes; may be null.',
            type: 'string',
            maxLength: 20,
            example: '52893'
        ),
        new OA\Property(
            property: 'created_at',
            description: 'Creation timestamp as stored by MySQL.',
            type: 'string',
            example: '2026-11-01 16:31:33'
        ),
        new OA\Property(
            property: 'category_name',
            description: 'Name of the category.',
            type: 'string',
            example: 'Pasta'
        ),
        new OA\Property(
            property: 'author_first_name',
            description: 'First name of the recipe owner.',
            type: 'string',
            example: 'Una'
        ),
        new OA\Property(
            property: 'author_last_name',
            description: 'Last name of the recipe owner.',
            type: 'string',
            example: 'User'
        ),
        new OA\Property(
            property: 'ingredients',
            description: 'The ingredient lines of the recipe.',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Ingredient')
        ),
    ]
)]
#[OA\Schema(
    schema: 'RecipeInput',
    description: 'Recipe data for POST and PUT. user_id, source and external_id are set by the backend and cannot be sent by the client.',
    type: 'object',
    required: ['category_id', 'title', 'instructions', 'prep_time_minutes', 'servings'],
    properties: [
        new OA\Property(
            property: 'category_id',
            description: 'Id of an existing category.',
            type: 'integer',
            example: 8
        ),
        new OA\Property(property: 'title', type: 'string', minLength: 3, maxLength: 100, example: 'Spaghetti Carbonara'),
        new OA\Property(
            property: 'instructions',
            type: 'string',
            minLength: 10,
            maxLength: 20000,
            example: 'Cook the pasta until al dente and serve it with the sauce.'
        ),
        new OA\Property(property: 'prep_time_minutes', type: 'integer', minimum: 1, maximum: 600, example: 25),
        new OA\Property(property: 'servings', type: 'integer', minimum: 1, maximum: 20, example: 4),
        new OA\Property(
            property: 'image_url',
            description: 'Optional valid URL, at most 500 characters.',
            type: 'string',
            format: 'uri',
            maxLength: 500
        ),
        new OA\Property(
            property: 'ingredients',
            description: 'At least one ingredient. Required on POST; optional on PUT, where a list replaces all existing ingredients.',
            type: 'array',
            minItems: 1,
            items: new OA\Items(ref: '#/components/schemas/IngredientInput')
        ),
    ]
)]
#[OA\Schema(
    schema: 'Ingredient',
    description: 'One ingredient line of a recipe.',
    type: 'object',
    required: ['id', 'recipe_id', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'recipe_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', minLength: 1, maxLength: 100, example: 'Spaghetti'),
        new OA\Property(
            property: 'measure',
            description: 'Optional; may be null.',
            type: 'string',
            maxLength: 50,
            example: '200 g'
        ),
    ]
)]
#[OA\Schema(
    schema: 'IngredientInput',
    description: 'Ingredient data for POST and PUT. recipe_id comes from the route for POST /recipes/{id}/ingredients.',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', minLength: 1, maxLength: 100, example: 'Spaghetti'),
        new OA\Property(
            property: 'measure',
            description: 'Optional; may be omitted.',
            type: 'string',
            maxLength: 50,
            example: '200 g'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ExternalMeal',
    description: 'A simplified meal from TheMealDB, returned by name search, random and lookup.',
    type: 'object',
    required: ['id', 'name', 'tags', 'ingredients'],
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '52893'),
        new OA\Property(property: 'name', type: 'string', example: 'Spaghetti Carbonara'),
        new OA\Property(
            property: 'category',
            description: 'May be null.',
            type: 'string',
            example: 'Pasta'
        ),
        new OA\Property(
            property: 'area',
            description: 'May be null.',
            type: 'string',
            example: 'Italian'
        ),
        new OA\Property(
            property: 'instructions',
            description: 'May be null.',
            type: 'string'
        ),
        new OA\Property(
            property: 'thumbnail',
            description: 'Image URL from TheMealDB; may be null.',
            type: 'string',
            format: 'uri'
        ),
        new OA\Property(
            property: 'tags',
            description: 'Tags split from the comma separated TheMealDB value; empty when there are none.',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['Pasta', 'Dinner']
        ),
        new OA\Property(
            property: 'ingredients',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Spaghetti'),
                    new OA\Property(
                        property: 'measure',
                        description: 'May be null.',
                        type: 'string',
                        example: '200 g'
                    ),
                ]
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'ExternalMealSummary',
    description: 'A compact TheMealDB meal returned by the category, area and ingredient filters.',
    type: 'object',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '52893'),
        new OA\Property(property: 'name', type: 'string', example: 'Spaghetti Carbonara'),
        new OA\Property(
            property: 'thumbnail',
            description: 'Image URL from TheMealDB; may be null.',
            type: 'string',
            format: 'uri'
        ),
    ]
)]
#[OA\Response(
    response: 'BadRequest',
    description: 'Invalid input or a business rule is violated.',
    content: new OA\JsonContent(ref: '#/components/schemas/Error')
)]
#[OA\Response(
    response: 'Unauthorized',
    description: 'No identified user: the X-User-Id header is missing or does not match an existing user.',
    content: new OA\JsonContent(ref: '#/components/schemas/Error')
)]
#[OA\Response(
    response: 'Forbidden',
    description: 'The caller is not allowed to perform this action.',
    content: new OA\JsonContent(ref: '#/components/schemas/Error')
)]
#[OA\Response(
    response: 'NotFound',
    description: 'The requested resource does not exist.',
    content: new OA\JsonContent(ref: '#/components/schemas/Error')
)]
#[OA\Response(
    response: 'ServerError',
    description: 'Unexpected error. The message is generic and never contains details.',
    content: new OA\JsonContent(ref: '#/components/schemas/Error')
)]
class OpenApiSpec
{
}
