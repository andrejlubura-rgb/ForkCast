<?php

// Client for TheMealDB; the only class that makes the external HTTP calls, so routes and other services never do.
class MealDbService
{
    // Friendly message used for every failure of the external service.
    private const UNAVAILABLE_MESSAGE = 'TheMealDB is unavailable right now. You can still add your own recipes.';

    // Filter types accepted by filter(), mapped to the query parameter TheMealDB expects.
    private const FILTER_PARAMETERS = [
        'category' => 'c',
        'area' => 'a',
        'ingredient' => 'i',
    ];

    // Base URL from config, always ending with a slash.
    private string $baseUrl;

    // API key from config.
    private string $apiKey;

    // Request timeout in seconds from config.
    private int $timeout;

    // Reads the TheMealDB settings from the uncommitted config file.
    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';
        $mealdb = $config['mealdb'] ?? [];

        $this->baseUrl = rtrim((string) ($mealdb['base_url'] ?? ''), '/') . '/';
        $this->apiKey = (string) ($mealdb['api_key'] ?? '');
        $this->timeout = max(1, (int) ($mealdb['timeout'] ?? 5));
    }

    // Searches meals by name and returns simplified meals with their ingredients.
    public function search(string $query): array
    {
        $decoded = $this->get('search.php', ['s' => $query]);
        $meals = [];

        foreach ($this->meals($decoded) as $meal) {
            $meals[] = $this->mapMeal($meal);
        }

        return $meals;
    }

    // Filters meals by category, area or ingredient and returns only id, name and thumbnail.
    public function filter(string $type, string $value): array
    {
        if (!isset(self::FILTER_PARAMETERS[$type])) {
            throw ApiException::badRequest('The filter type must be category, area or ingredient.');
        }

        $decoded = $this->get('filter.php', [self::FILTER_PARAMETERS[$type] => $value]);
        $meals = [];

        foreach ($this->meals($decoded) as $meal) {
            $meals[] = [
                'id' => $this->text($meal['idMeal'] ?? null),
                'name' => $this->text($meal['strMeal'] ?? null),
                'thumbnail' => $this->textOrNull($meal['strMealThumb'] ?? null),
            ];
        }

        return $meals;
    }

    // Returns one random meal as a simplified meal.
    public function random(): array
    {
        $decoded = $this->get('random.php', []);
        $meals = $this->meals($decoded);

        // The random endpoint has no 404 in docs/API.md, so an empty answer counts as unavailable.
        if (empty($meals)) {
            Logger::error('TheMealDB random.php: the response contains no meal');
            throw ApiException::unavailable(self::UNAVAILABLE_MESSAGE);
        }

        return $this->mapMeal($meals[0]);
    }

    // Returns one meal as a simplified meal, or throws 404 when TheMealDB does not know the id.
    public function lookup(string $id): array
    {
        $decoded = $this->get('lookup.php', ['i' => $id]);
        $meals = $this->meals($decoded);

        if (empty($meals)) {
            throw ApiException::notFound('Meal not found');
        }

        return $this->mapMeal($meals[0]);
    }

    // Calls one TheMealDB endpoint and returns the decoded JSON; every transport failure becomes a 503.
    private function get(string $endpoint, array $params): array
    {
        $url = $this->baseUrl . rawurlencode($this->apiKey) . '/' . $endpoint;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        $handle = curl_init($url);

        if ($handle === false) {
            Logger::error('TheMealDB ' . $endpoint . ': cURL could not be initialised');
            throw ApiException::unavailable(self::UNAVAILABLE_MESSAGE);
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $body = curl_exec($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($body === false || $error !== '') {
            Logger::error('TheMealDB ' . $endpoint . ': cURL error: ' . ($error !== '' ? $error : 'no response'));
            throw ApiException::unavailable(self::UNAVAILABLE_MESSAGE);
        }

        if ($status !== 200) {
            Logger::error('TheMealDB ' . $endpoint . ': unexpected HTTP status ' . $status);
            throw ApiException::unavailable(self::UNAVAILABLE_MESSAGE);
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            Logger::error('TheMealDB ' . $endpoint . ': the response is not valid JSON');
            throw ApiException::unavailable(self::UNAVAILABLE_MESSAGE);
        }

        $meals = $decoded['meals'] ?? null;
        Logger::info('TheMealDB ' . $endpoint . ': ' . (is_array($meals) ? count($meals) : 0) . ' result(s)');

        return $decoded;
    }

    // Returns the meal rows of a decoded response; "meals": null or a missing key becomes an empty list.
    private function meals(array $decoded): array
    {
        $meals = $decoded['meals'] ?? null;

        if (!is_array($meals)) {
            return [];
        }

        return array_values(array_filter($meals, 'is_array'));
    }

    // Converts one TheMealDB meal into the simplified meal object from docs/API.md.
    private function mapMeal(array $meal): array
    {
        return [
            'id' => $this->text($meal['idMeal'] ?? null),
            'name' => $this->text($meal['strMeal'] ?? null),
            'category' => $this->textOrNull($meal['strCategory'] ?? null),
            'area' => $this->textOrNull($meal['strArea'] ?? null),
            'instructions' => $this->textOrNull($meal['strInstructions'] ?? null),
            'thumbnail' => $this->textOrNull($meal['strMealThumb'] ?? null),
            'tags' => $this->mapTags($meal['strTags'] ?? null),
            'ingredients' => $this->mapIngredients($meal),
        ];
    }

    // Reads strIngredient1..20 with strMeasure1..20 and skips the pairs without an ingredient name.
    private function mapIngredients(array $meal): array
    {
        $ingredients = [];

        for ($index = 1; $index <= 20; $index++) {
            $name = $this->text($meal['strIngredient' . $index] ?? null);

            if ($name === '') {
                continue;
            }

            $measure = $this->text($meal['strMeasure' . $index] ?? null);

            $ingredients[] = [
                'name' => $name,
                'measure' => $measure === '' ? null : $measure,
            ];
        }

        return $ingredients;
    }

    // Splits the comma separated strTags value into a list of trimmed tags.
    private function mapTags($value): array
    {
        if (!is_string($value)) {
            return [];
        }

        $tags = [];

        foreach (explode(',', $value) as $tag) {
            $tag = trim($tag);

            if ($tag !== '') {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    // Returns a trimmed string for a value from the API, or an empty string when it is missing.
    private function text($value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    // Returns a trimmed string, or null when the value is missing or empty.
    private function textOrNull($value): ?string
    {
        $text = $this->text($value);

        return $text === '' ? null : $text;
    }
}
