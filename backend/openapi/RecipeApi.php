<?php

// OpenAPI documentation for the recipe endpoints in docs/API.md.
// The methods are documentation only: swagger-php reads the attributes and never calls them.
use OpenApi\Attributes as OA;

class RecipeApi
{
    // GET /recipes: one page of recipes for an identified user, with optional filters.
    #[OA\Get(
        path: '/recipes',
        tags: ['Recipes'],
        summary: 'List recipes',
        description: 'Returns one page of recipes. Any identified user. Optional filters can narrow the list by title, category and owner.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Case-insensitive search on the recipe title.',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'category_id',
                in: 'query',
                required: false,
                description: 'Only recipes in this category.',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'mine',
                in: 'query',
                required: false,
                description: 'When true, only the recipes of the identified user are returned.',
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Page size. Default 20, maximum 100.',
                schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: 'offset',
                in: 'query',
                required: false,
                description: 'Number of recipes to skip. Default 0.',
                schema: new OA\Schema(type: 'integer', default: 0, minimum: 0)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'One page of recipes, without ingredient lines.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Recipe')
                )
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function listRecipes(): void
    {
    }

    // GET /recipes/{id}: returns one recipe with its ingredients for an identified user.
    #[OA\Get(
        path: '/recipes/{id}',
        tags: ['Recipes'],
        summary: 'Get a recipe',
        description: 'Returns one recipe with its ingredient lines. Any identified user.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Recipe id.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The recipe with its ingredients.',
                content: new OA\JsonContent(ref: '#/components/schemas/Recipe')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function getRecipe(): void
    {
    }

    // POST /recipes: creates a recipe with its ingredients for the identified user.
    #[OA\Post(
        path: '/recipes',
        tags: ['Recipes'],
        summary: 'Create a recipe',
        description: 'Creates a recipe with at least one ingredient in one transaction. The caller is the owner and source is "own".',
        security: [['tempUser' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The new recipe.',
            content: new OA\JsonContent(ref: '#/components/schemas/RecipeInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'The created recipe with its ingredients.',
                content: new OA\JsonContent(ref: '#/components/schemas/Recipe')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function createRecipe(): void
    {
    }

    // PUT /recipes/{id}: updates a recipe; the owner of the recipe or an admin.
    #[OA\Put(
        path: '/recipes/{id}',
        tags: ['Recipes'],
        summary: 'Update a recipe',
        description: 'Updates a recipe. The owner of the recipe or an admin only. When ingredients are sent, they replace all existing ingredients in one transaction.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Recipe id.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The fields to update.',
            content: new OA\JsonContent(ref: '#/components/schemas/RecipeInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated recipe with its ingredients.',
                content: new OA\JsonContent(ref: '#/components/schemas/Recipe')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function updateRecipe(): void
    {
    }

    // DELETE /recipes/{id}: deletes a recipe; the owner of the recipe or an admin.
    #[OA\Delete(
        path: '/recipes/{id}',
        tags: ['Recipes'],
        summary: 'Delete a recipe',
        description: 'Deletes a recipe. The owner of the recipe or an admin only. Returns 400 when the recipe is used in a plan.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Recipe id.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Deleted.',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['message'],
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted'),
                    ]
                )
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function deleteRecipe(): void
    {
    }

    // POST /recipes/import/{externalId}: imports a TheMealDB meal for the identified user.
    #[OA\Post(
        path: '/recipes/import/{externalId}',
        tags: ['Recipes'],
        summary: 'Import a TheMealDB meal',
        description: 'Looks up a TheMealDB meal and saves it as a recipe owned by the caller with source "mealdb". Importing the same meal twice returns the existing recipe with 200.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'externalId',
                in: 'path',
                required: true,
                description: 'TheMealDB meal id, at most 20 characters.',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'The newly imported recipe.',
                content: new OA\JsonContent(ref: '#/components/schemas/Recipe')
            ),
            new OA\Response(
                response: 200,
                description: 'The recipe that was already imported by this user.',
                content: new OA\JsonContent(ref: '#/components/schemas/Recipe')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(
                response: 503,
                description: 'TheMealDB is unavailable. Returns the friendly message from docs/API.md.',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function importRecipe(): void
    {
    }
}
