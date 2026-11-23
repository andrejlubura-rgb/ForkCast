<?php

// OpenAPI documentation for the category endpoints in docs/API.md.
// The methods are documentation only: swagger-php reads the attributes and never calls them.
use OpenApi\Attributes as OA;

class CategoryApi
{
    // GET /categories: returns every category for an identified user, without paging.
    #[OA\Get(
        path: '/categories',
        tags: ['Categories'],
        summary: 'List all categories',
        description: 'Returns every category without paging. Any identified user.',
        security: [['tempUser' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All categories.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Category')
                )
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function listCategories(): void
    {
    }

    // GET /categories/{id}: returns one category for an identified user.
    #[OA\Get(
        path: '/categories/{id}',
        tags: ['Categories'],
        summary: 'Get a category',
        description: 'Returns one category. Any identified user.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Category id.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The category.',
                content: new OA\JsonContent(ref: '#/components/schemas/Category')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function getCategory(): void
    {
    }

    // POST /categories: creates a category; admins only.
    #[OA\Post(
        path: '/categories',
        tags: ['Categories'],
        summary: 'Create a category',
        description: 'Creates a category. Admins only. The name must be unique.',
        security: [['tempUser' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The new category.',
            content: new OA\JsonContent(ref: '#/components/schemas/CategoryInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'The created category.',
                content: new OA\JsonContent(ref: '#/components/schemas/Category')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function createCategory(): void
    {
    }

    // PUT /categories/{id}: updates a category; admins only.
    #[OA\Put(
        path: '/categories/{id}',
        tags: ['Categories'],
        summary: 'Update a category',
        description: 'Updates a category. Admins only. The name must stay unique.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Category id.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The new category data.',
            content: new OA\JsonContent(ref: '#/components/schemas/CategoryInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated category.',
                content: new OA\JsonContent(ref: '#/components/schemas/Category')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function updateCategory(): void
    {
    }

    // DELETE /categories/{id}: deletes a category; admins only and only when no recipe uses it.
    #[OA\Delete(
        path: '/categories/{id}',
        tags: ['Categories'],
        summary: 'Delete a category',
        description: 'Deletes a category. Admins only. Returns 400 when the category still has recipes.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Category id.',
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
    public function deleteCategory(): void
    {
    }
}
