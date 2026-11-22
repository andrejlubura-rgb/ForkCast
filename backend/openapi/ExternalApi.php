<?php

// OpenAPI documentation for the TheMealDB endpoints in docs/API.md.
// The methods are documentation only: swagger-php reads the attributes and never calls them.
use OpenApi\Attributes as OA;

class ExternalApi
{
    // GET /external/meals/search: searches TheMealDB by exactly one of q, category, area or ingredient.
    #[OA\Get(
        path: '/external/meals/search',
        tags: ['External'],
        summary: 'Search TheMealDB meals',
        description: 'Sends exactly one filter to TheMealDB. A name search returns full meals; category, area and ingredient filters return summaries. The result list can be empty.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: false,
                description: 'Search by meal name. Exactly one of q, category, area or ingredient must be sent.',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'category',
                in: 'query',
                required: false,
                description: 'Filter by category. Exactly one of q, category, area or ingredient must be sent.',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'area',
                in: 'query',
                required: false,
                description: 'Filter by area. Exactly one of q, category, area or ingredient must be sent.',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'ingredient',
                in: 'query',
                required: false,
                description: 'Filter by ingredient. Exactly one of q, category, area or ingredient must be sent.',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Matching meals: full meals for q, summaries for the other filters.',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ExternalMeal')
                        ),
                        new OA\Schema(
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ExternalMealSummary')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(
                response: 503,
                description: 'TheMealDB is unavailable. Returns the friendly message from docs/API.md.',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function searchMeals(): void
    {
    }

    // GET /external/meals/random: returns one random meal from TheMealDB.
    #[OA\Get(
        path: '/external/meals/random',
        tags: ['External'],
        summary: 'Get a random TheMealDB meal',
        description: 'Returns one simplified meal from TheMealDB.',
        security: [['tempUser' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A random simplified meal.',
                content: new OA\JsonContent(ref: '#/components/schemas/ExternalMeal')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(
                response: 503,
                description: 'TheMealDB is unavailable. Returns the friendly message from docs/API.md.',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function randomMeal(): void
    {
    }

    // GET /external/meals/{id}: returns one TheMealDB meal by its id.
    #[OA\Get(
        path: '/external/meals/{id}',
        tags: ['External'],
        summary: 'Get a TheMealDB meal by id',
        description: 'Returns one simplified meal from TheMealDB.',
        security: [['tempUser' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'TheMealDB meal id.',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The simplified meal.',
                content: new OA\JsonContent(ref: '#/components/schemas/ExternalMeal')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(
                response: 503,
                description: 'TheMealDB is unavailable. Returns the friendly message from docs/API.md.',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function getMeal(): void
    {
    }
}
