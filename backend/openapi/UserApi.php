<?php

// OpenAPI documentation for the user endpoints in docs/API.md.
// The methods are documentation only: swagger-php reads the attributes and never calls them.
use OpenApi\Attributes as OA;

class UserApi
{
    // GET /users: one page of users; admins only.
    #[OA\Get(
        path: '/users',
        tags: ['Users'],
        summary: 'List users',
        description: 'Returns one page of users without password hashes. Admins only.',
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
                description: 'Number of users to skip. Default 0.',
                schema: new OA\Schema(type: 'integer', default: 0, minimum: 0)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'One page of users.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/User')
                )
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function listUsers(): void
    {
    }

    // GET /users/{id}: one user for that same user or an admin.
    #[OA\Get(
        path: '/users/{id}',
        tags: ['Users'],
        summary: 'Get a user',
        description: 'Returns one user without the password hash. The user with that id or an admin only.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'User id.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The user.',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function getUser(): void
    {
    }

    // POST /users: public registration; the created user always gets the role "user".
    #[OA\Post(
        path: '/users',
        tags: ['Users'],
        summary: 'Register a user',
        description: 'Public registration. The created user always gets the role "user"; the client cannot choose a role.',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The new user.',
            content: new OA\JsonContent(ref: '#/components/schemas/UserInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'The created user.',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
        ]
    )]
    public function createUser(): void
    {
    }

    // PUT /users/{id}: updates a user; that same user or an admin.
    #[OA\Put(
        path: '/users/{id}',
        tags: ['Users'],
        summary: 'Update a user',
        description: 'Updates a user. The user with that id or an admin only. The password is optional; only an admin may change a role.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'User id.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The fields to update.',
            content: new OA\JsonContent(ref: '#/components/schemas/UserInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated user.',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function updateUser(): void
    {
    }

    // DELETE /users/{id}: deletes a user; admins only.
    #[OA\Delete(
        path: '/users/{id}',
        tags: ['Users'],
        summary: 'Delete a user',
        description: 'Deletes a user. Admins only. Returns 400 when a database constraint blocks it, for example when the user\'s recipes are used in other users\' plans.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'User id.',
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
    public function deleteUser(): void
    {
    }
}
