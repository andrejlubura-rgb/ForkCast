<?php

// OpenAPI documentation for the meal plan endpoints in docs/API.md.
// The methods are documentation only: swagger-php reads the attributes and never calls them.
use OpenApi\Attributes as OA;

class MealPlanApi
{
    // GET /plans: one page of plans; a user sees only their own plans, an admin sees every plan.
    #[OA\Get(
        path: '/plans',
        tags: ['Meal plans'],
        summary: 'List meal plans',
        description: 'Returns one page of plans. A user sees only their own plans; an admin sees every plan.',
        security: [['tempUser' => []]],
        parameters: [
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
                description: 'Number of plans to skip. Default 0.',
                schema: new OA\Schema(type: 'integer', default: 0, minimum: 0)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'One page of plans.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/MealPlan')
                )
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function listPlans(): void
    {
    }

    // GET /plans/{id}: returns one plan with its planned meals; the owner of the plan or an admin.
    #[OA\Get(
        path: '/plans/{id}',
        tags: ['Meal plans'],
        summary: 'Get a meal plan',
        description: 'Returns one plan with its planned meals. The owner of the plan or an admin only.',
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
                description: 'The plan with its planned meals.',
                content: new OA\JsonContent(ref: '#/components/schemas/MealPlan')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function getPlan(): void
    {
    }

    // POST /plans: creates a plan for the identified user.
    #[OA\Post(
        path: '/plans',
        tags: ['Meal plans'],
        summary: 'Create a meal plan',
        description: 'Creates a plan for the identified user. The week start date must be a Monday and the user may have only one plan per week.',
        security: [['tempUser' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The new meal plan.',
            content: new OA\JsonContent(ref: '#/components/schemas/MealPlanInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'The created plan with its planned meals.',
                content: new OA\JsonContent(ref: '#/components/schemas/MealPlan')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function createPlan(): void
    {
    }

    // PUT /plans/{id}: updates a plan; the owner of the plan or an admin.
    #[OA\Put(
        path: '/plans/{id}',
        tags: ['Meal plans'],
        summary: 'Update a meal plan',
        description: 'Updates a plan. The owner of the plan or an admin only. The week start date must be a Monday; the plan being updated does not count as an existing plan for that week.',
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
            description: 'The fields to update.',
            content: new OA\JsonContent(ref: '#/components/schemas/MealPlanInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated plan with its planned meals.',
                content: new OA\JsonContent(ref: '#/components/schemas/MealPlan')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function updatePlan(): void
    {
    }

    // DELETE /plans/{id}: deletes a plan and its planned meals; the owner of the plan or an admin.
    #[OA\Delete(
        path: '/plans/{id}',
        tags: ['Meal plans'],
        summary: 'Delete a meal plan',
        description: 'Deletes a plan together with its planned meals. The owner of the plan or an admin only.',
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
    public function deletePlan(): void
    {
    }

    // GET /plans/{id}/shopping-list: returns the grouped ingredients of a plan.
    #[OA\Get(
        path: '/plans/{id}/shopping-list',
        tags: ['Meal plans'],
        summary: 'Get a shopping list',
        description: 'Returns the grouped ingredients of all recipes in a plan. The owner of the plan or an admin only.',
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
                description: 'The shopping list items.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ShoppingItem')
                )
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function getShoppingList(): void
    {
    }
}
