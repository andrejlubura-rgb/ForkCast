<?php

// OpenAPI documentation for the planned meal endpoints in docs/API.md.
// The methods are documentation only: swagger-php reads the attributes and never calls them.
use OpenApi\Attributes as OA;

class PlannedMealApi
{
    // GET /plans/{id}/meals: returns every planned meal of a plan.
    #[OA\Get(
        path: '/plans/{id}/meals',
        tags: ['Planned meals'],
        summary: 'List planned meals',
        description: 'Returns every planned meal of a plan, including the recipe title and image. The owner of the plan or an admin only.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Meal plan id.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The planned meals of the plan.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PlannedMeal')
                )
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function listPlanMeals(): void
    {
    }

    // POST /plans/{id}/meals: adds a meal to a free slot of a plan.
    #[OA\Post(
        path: '/plans/{id}/meals',
        tags: ['Planned meals'],
        summary: 'Add a planned meal',
        description: 'Adds a meal to a free slot of a plan. The owner of the plan or an admin only. The recipe must exist and one slot (day and meal type) holds one meal.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Meal plan id.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The planned meal.',
            content: new OA\JsonContent(ref: '#/components/schemas/PlannedMealInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'The created planned meal.',
                content: new OA\JsonContent(ref: '#/components/schemas/PlannedMeal')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function createPlannedMeal(): void
    {
    }

    // PUT /planned-meals/{id}: updates a planned meal; the owner of its plan or an admin.
    #[OA\Put(
        path: '/planned-meals/{id}',
        tags: ['Planned meals'],
        summary: 'Update a planned meal',
        description: 'Updates a planned meal. The owner of its plan or an admin only. The meal being updated does not count as occupying its own slot.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Planned meal id.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The fields to update.',
            content: new OA\JsonContent(ref: '#/components/schemas/PlannedMealInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated planned meal.',
                content: new OA\JsonContent(ref: '#/components/schemas/PlannedMeal')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function updatePlannedMeal(): void
    {
    }

    // DELETE /planned-meals/{id}: deletes a planned meal; the owner of its plan or an admin.
    #[OA\Delete(
        path: '/planned-meals/{id}',
        tags: ['Planned meals'],
        summary: 'Delete a planned meal',
        description: 'Deletes a planned meal. The owner of its plan or an admin only.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Planned meal id.',
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
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function deletePlannedMeal(): void
    {
    }
}
