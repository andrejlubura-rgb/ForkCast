<?php

// OpenAPI documentation for the recipe ingredient endpoints in docs/API.md.
// The methods are documentation only: swagger-php reads the attributes and never calls them.
use OpenApi\Attributes as OA;

class IngredientApi
{
    // GET /recipes/{id}/ingredients: returns every ingredient line of a recipe.
    #[OA\Get(
        path: '/recipes/{id}/ingredients',
        tags: ['Ingredients'],
        summary: 'List recipe ingredients',
        description: 'Returns every ingredient line of a recipe. Any identified user.',
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
                description: 'The ingredient lines of the recipe.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Ingredient')
                )
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function listRecipeIngredients(): void
    {
    }

    // POST /recipes/{id}/ingredients: adds an ingredient line to a recipe.
    #[OA\Post(
        path: '/recipes/{id}/ingredients',
        tags: ['Ingredients'],
        summary: 'Add a recipe ingredient',
        description: 'Adds an ingredient line to a recipe. The owner of the recipe or an admin only.',
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
            description: 'The new ingredient line.',
            content: new OA\JsonContent(ref: '#/components/schemas/IngredientInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'The created ingredient line.',
                content: new OA\JsonContent(ref: '#/components/schemas/Ingredient')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function createIngredient(): void
    {
    }

    // PUT /ingredients/{id}: updates one ingredient line; the owner of its recipe or an admin.
    #[OA\Put(
        path: '/ingredients/{id}',
        tags: ['Ingredients'],
        summary: 'Update a recipe ingredient',
        description: 'Updates one ingredient line. The owner of its recipe or an admin only.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Ingredient id.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The fields to update.',
            content: new OA\JsonContent(ref: '#/components/schemas/IngredientInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated ingredient line.',
                content: new OA\JsonContent(ref: '#/components/schemas/Ingredient')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function updateIngredient(): void
    {
    }

    // DELETE /ingredients/{id}: deletes an ingredient line; never the last one of a recipe.
    #[OA\Delete(
        path: '/ingredients/{id}',
        tags: ['Ingredients'],
        summary: 'Delete a recipe ingredient',
        description: 'Deletes an ingredient line. The owner of its recipe or an admin only. Returns 400 for the last ingredient of a recipe.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Ingredient id.',
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
    public function deleteIngredient(): void
    {
    }
}
