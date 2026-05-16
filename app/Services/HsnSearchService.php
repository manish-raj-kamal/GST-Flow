<?php

namespace App\Services;

use App\Models\HsnCode;
use App\Models\HsnProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HsnSearchService
{
    public function __construct(
        private readonly HsnNormalizationService $normalizationService,
    ) {
    }

    public function search(string $query, int $limit = 8, ?string $category = null): array
    {
        $normalized = $this->normalizationService->normalizeQuery($query);
        $limit = max(1, min(20, $limit));
        $category = $category ? $this->normalizationService->normalizeText($category) : null;

        $cacheKey = 'hsn:smart-search:'.md5(json_encode([$normalized, $limit, $category]));

        return Cache::remember($cacheKey, now()->addSeconds((int) config('hsn_search.cache_ttl_seconds', 300)), function () use ($normalized, $limit, $category) {
            $startedAt = microtime(true);
            $this->ensureIndexes();

            $results = [];
            if (config('hsn_search.atlas.enabled', true)) {
                try {
                    $results = $this->atlasSearch($normalized, $limit, $category);
                } catch (\Throwable) {
                    $results = [];
                }
            }

            if (empty($results)) {
                $results = $this->fallbackSearch($normalized, $limit, $category);
            }

            $latency = (int) round((microtime(true) - $startedAt) * 1000);

            return [
                'results' => $results,
                'meta' => [
                    'latency_ms' => $latency,
                    'total' => count($results),
                    'suggestions' => count($results) > 0 ? [] : $this->normalizationService->suggestionHints($normalized['normalized']),
                ],
            ];
        });
    }

    public function recordSelection(string $query, string $hsnCode): void
    {
        $normalized = $this->normalizationService->normalizeText($query);
        $hsnCode = preg_replace('/\D+/', '', $hsnCode) ?: '';

        if ($normalized === '' || $hsnCode === '') {
            return;
        }

        $product = HsnProduct::query()->where('hsn_code', $hsnCode)->first();
        if (! $product) {
            return;
        }

        $weight = (int) ($product->search_weight ?? 50);
        $product->search_weight = min(120, $weight + 2);
        $product->save();
    }

    public function ensureIndexes(): void
    {
        if (! Cache::add('hsn:search:indexes:ensured', 1, now()->addDay())) {
            return;
        }

        HsnProduct::raw(function ($collection) {
            $collection->createIndexes([
                ['key' => ['hsn_code' => 1], 'name' => 'hsn_code_idx'],
                ['key' => ['primary_name' => 1], 'name' => 'primary_name_idx'],
                ['key' => ['aliases' => 1], 'name' => 'aliases_idx'],
                ['key' => ['keywords' => 1], 'name' => 'keywords_idx'],
                ['key' => ['category' => 1], 'name' => 'category_idx'],
                ['key' => ['official_description' => 1], 'name' => 'official_description_idx'],
                ['key' => ['status' => 1, 'search_weight' => -1], 'name' => 'status_weight_idx'],
            ]);
        });
    }

    private function atlasSearch(array $normalized, int $limit, ?string $category): array
    {
        $query = $normalized['normalized'];
        if ($query === '') {
            return [];
        }

        $tokens = $normalized['expanded_tokens'];
        $numericQuery = preg_replace('/\D+/', '', $query) ?: '';

        $shouldClauses = [
                [
                    'autocomplete' => [
                        'query' => $query,
                        'path' => 'primary_name',
                        'fuzzy' => ['maxEdits' => 2, 'prefixLength' => 1],
                        'score' => ['boost' => ['value' => 12]],
                    ],
                ],
                [
                    'text' => [
                        'query' => $query,
                        'path' => ['aliases', 'primary_name'],
                        'fuzzy' => ['maxEdits' => 2, 'prefixLength' => 1],
                        'synonyms' => config('hsn_search.atlas.synonyms'),
                        'score' => ['boost' => ['value' => 10]],
                    ],
                ],
                [
                    'text' => [
                        'query' => $tokens,
                        'path' => ['keywords', 'official_description', 'category', 'subcategory'],
                        'fuzzy' => ['maxEdits' => 1, 'prefixLength' => 1],
                        'score' => ['boost' => ['value' => 7]],
                    ],
                ],
        ];
        if ($numericQuery !== '') {
            $shouldClauses[] = [
                    'text' => [
                        'query' => $numericQuery,
                        'path' => 'hsn_code',
                        'score' => ['boost' => ['value' => 14]],
                    ],
                ];
        }

        $compound = [
            'should' => $shouldClauses,
            'minimumShouldMatch' => 1,
        ];

        $pipeline = [
            [
                '$search' => [
                    'index' => config('hsn_search.atlas.index', 'hsn_products_search'),
                    'compound' => $compound,
                ],
            ],
            [
                '$match' => array_filter([
                    'status' => 'active',
                    'category' => $category ? new \MongoDB\BSON\Regex('^'.preg_quote($category, '/').'$','i') : null,
                ]),
            ],
            [
                '$limit' => $limit,
            ],
            [
                '$project' => [
                    '_id' => 0,
                    'hsn_code' => 1,
                    'name' => '$primary_name',
                    'aliases' => 1,
                    'matched_alias' => ['$arrayElemAt' => ['$aliases', 0]],
                    'gst_rate' => 1,
                    'category' => 1,
                    'subcategory' => 1,
                    'official_description' => 1,
                    'search_weight' => 1,
                    'score' => ['$meta' => 'searchScore'],
                ],
            ],
        ];

        $cursor = HsnProduct::raw(fn ($collection) => $collection->aggregate($pipeline));
        $rows = $this->cursorToArray($cursor);

        return collect($rows)->map(function (array $row): array {
            $score = (float) ($row['score'] ?? 0);
            $confidence = max(1, min(99, (int) round(($score * 12) + 20)));

            return [
                'hsn_code' => (string) ($row['hsn_code'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
                'matched_alias' => (string) ($row['matched_alias'] ?? ''),
                'gst_rate' => (float) ($row['gst_rate'] ?? 0),
                'category' => (string) ($row['category'] ?? 'General'),
                'subcategory' => (string) ($row['subcategory'] ?? ''),
                'official_description' => (string) ($row['official_description'] ?? ''),
                'aliases' => collect($row['aliases'] ?? [])->values()->all(),
                'confidence' => $confidence,
                'score' => $score,
            ];
        })->values()->all();
    }

    private function fallbackSearch(array $normalized, int $limit, ?string $category): array
    {
        $query = $normalized['normalized'];
        if ($query === '') {
            return [];
        }

        $tokens = $normalized['expanded_tokens'];
        $products = $this->fallbackCandidatesFromHsnProducts($tokens, $category);
        if (empty($products)) {
            $products = $this->fallbackCandidatesFromLegacyCatalog($tokens, $category);
        }
        if (empty($products)) {
            $products = $this->builtInCandidates($tokens, $category);
        }

        $scored = collect($products)
            ->map(fn (array $candidate) => $this->scoreCandidate($candidate, $normalized))
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->unique('hsn_code')
            ->take($limit)
            ->values()
            ->all();

        return array_map(function (array $row): array {
            unset($row['score']);

            return $row;
        }, $scored);
    }

    private function fallbackCandidatesFromHsnProducts(array $tokens, ?string $category): array
    {
        if (empty($tokens)) {
            return [];
        }

        $or = [];
        foreach ($tokens as $token) {
            $safe = preg_quote((string) $token, '/');
            $regex = new \MongoDB\BSON\Regex($safe, 'i');
            $or[] = ['primary_name' => $regex];
            $or[] = ['aliases' => $regex];
            $or[] = ['keywords' => $regex];
            $or[] = ['official_description' => $regex];
            $or[] = ['hsn_code' => $regex];
        }

        $match = ['status' => 'active', '$or' => $or];
        if ($category) {
            $match['category'] = new \MongoDB\BSON\Regex('^'.preg_quote($category, '/').'$', 'i');
        }

        $pipeline = [
            ['$match' => $match],
            ['$limit' => 250],
            ['$project' => [
                '_id' => 0,
                'hsn_code' => 1,
                'primary_name' => 1,
                'aliases' => 1,
                'keywords' => 1,
                'category' => 1,
                'subcategory' => 1,
                'official_description' => 1,
                'gst_rate' => 1,
                'search_weight' => 1,
            ]],
        ];

        $cursor = HsnProduct::raw(fn ($collection) => $collection->aggregate($pipeline));

        return $this->cursorToArray($cursor);
    }

    private function fallbackCandidatesFromLegacyCatalog(array $tokens, ?string $category): array
    {
        $rows = HsnCode::query()
            ->where('status', 'active')
            ->get()
            ->filter(function (HsnCode $row) use ($tokens, $category): bool {
                if ($category && ! str_contains(Str::lower((string) $row->category), $category)) {
                    return false;
                }

                $haystack = Str::lower(trim($row->hsn_code.' '.$row->description.' '.$row->category));
                foreach ($tokens as $token) {
                    if (str_contains($haystack, (string) $token)) {
                        return true;
                    }
                }

                return false;
            })
            ->take(200)
            ->map(function (HsnCode $row): array {
                return [
                    'hsn_code' => (string) $row->hsn_code,
                    'primary_name' => (string) $row->description,
                    'aliases' => [],
                    'keywords' => [],
                    'category' => (string) $row->category,
                    'subcategory' => '',
                    'official_description' => (string) $row->description,
                    'gst_rate' => (float) $row->gst_rate,
                    'search_weight' => 70,
                ];
            })
            ->values()
            ->all();

        return $rows;
    }

    private function builtInCandidates(array $tokens, ?string $category): array
    {
        $catalog = [
            [
                'hsn_code' => '04061000',
                'primary_name' => 'Paneer',
                'aliases' => ['fresh cheese', 'cottage cheese', 'curd cheese', 'panir'],
                'keywords' => ['dairy', 'milk product', 'curd', 'cheese'],
                'category' => 'Dairy Products',
                'subcategory' => 'Fresh Cheese',
                'official_description' => 'Fresh cheese and curd based products',
                'gst_rate' => 5.0,
                'search_weight' => 92,
            ],
            [
                'hsn_code' => '04059020',
                'primary_name' => 'Ghee',
                'aliases' => ['clarified butter', 'desi ghee', 'cow ghee', 'milk fat'],
                'keywords' => ['dairy fat', 'butter oil', 'milk product'],
                'category' => 'Dairy Products',
                'subcategory' => 'Milk Fats',
                'official_description' => 'Milk fats and oils derived from milk',
                'gst_rate' => 12.0,
                'search_weight' => 95,
            ],
            [
                'hsn_code' => '04051000',
                'primary_name' => 'Butter',
                'aliases' => ['makhan', 'dairy butter'],
                'keywords' => ['milk fat', 'dairy'],
                'category' => 'Dairy Products',
                'subcategory' => 'Butter',
                'official_description' => 'Butter and related dairy fat products',
                'gst_rate' => 12.0,
                'search_weight' => 88,
            ],
            [
                'hsn_code' => '19053100',
                'primary_name' => 'Biscuits',
                'aliases' => ['biscuit', 'biscits', 'cookies', 'cracker'],
                'keywords' => ['snacks', 'bakery'],
                'category' => 'Food Products',
                'subcategory' => 'Bakery',
                'official_description' => 'Sweet biscuits and similar bakery products',
                'gst_rate' => 18.0,
                'search_weight' => 84,
            ],
            [
                'hsn_code' => '84713010',
                'primary_name' => 'Laptop',
                'aliases' => ['notebook', 'portable computer'],
                'keywords' => ['computer', 'electronics'],
                'category' => 'Electronics',
                'subcategory' => 'Computers',
                'official_description' => 'Portable automatic data processing machine',
                'gst_rate' => 18.0,
                'search_weight' => 90,
            ],
            [
                'hsn_code' => '85044030',
                'primary_name' => 'Mobile Charger',
                'aliases' => ['phone charger', 'usb charger', 'adapter'],
                'keywords' => ['mobile accessory', 'electrical accessory'],
                'category' => 'Electronics',
                'subcategory' => 'Accessories',
                'official_description' => 'Power adapters and chargers for mobile devices',
                'gst_rate' => 18.0,
                'search_weight' => 86,
            ],
        ];

        $normalizedCategory = $category ? Str::lower($category) : null;

        return collect($catalog)
            ->filter(function (array $item) use ($tokens, $normalizedCategory): bool {
                if ($normalizedCategory && ! str_contains(Str::lower((string) $item['category']), $normalizedCategory)) {
                    return false;
                }

                if (empty($tokens)) {
                    return true;
                }

                $haystack = Str::lower(
                    implode(' ', [
                        $item['hsn_code'],
                        $item['primary_name'],
                        implode(' ', $item['aliases'] ?? []),
                        implode(' ', $item['keywords'] ?? []),
                        $item['official_description'],
                        $item['category'],
                        $item['subcategory'],
                    ])
                );

                foreach ($tokens as $token) {
                    if (str_contains($haystack, Str::lower((string) $token))) {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();
    }

    private function scoreCandidate(array $candidate, array $normalized): array
    {
        $query = $normalized['normalized'];
        $tokens = $normalized['expanded_tokens'];
        $primary = $this->normalizationService->normalizeText((string) ($candidate['primary_name'] ?? ''));
        $aliases = collect($candidate['aliases'] ?? [])->map(fn ($v) => $this->normalizationService->normalizeText((string) $v))->filter()->values()->all();
        $keywords = collect($candidate['keywords'] ?? [])->map(fn ($v) => $this->normalizationService->normalizeText((string) $v))->filter()->values()->all();
        $category = $this->normalizationService->normalizeText((string) ($candidate['category'] ?? ''));
        $subcategory = $this->normalizationService->normalizeText((string) ($candidate['subcategory'] ?? ''));
        $description = $this->normalizationService->normalizeText((string) ($candidate['official_description'] ?? ''));
        $hsnCode = (string) ($candidate['hsn_code'] ?? '');

        $score = 0.0;
        $matchedAlias = '';

        if ($hsnCode !== '' && preg_replace('/\D+/', '', $query) === preg_replace('/\D+/', '', $hsnCode)) {
            $score += 140;
        }
        if ($primary === $query) {
            $score += 120;
        } elseif (str_contains($primary, $query)) {
            $score += 90;
        }

        foreach ($aliases as $alias) {
            if ($alias === $query) {
                $score += 95;
                $matchedAlias = $alias;
                break;
            }
            if ($query !== '' && str_contains($alias, $query)) {
                $score += 70;
                $matchedAlias = $alias;
            }
        }

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            if (str_contains($primary, $token)) {
                $score += 26;
            }
            if ($this->containsAny($aliases, $token)) {
                $score += 20;
            }
            if ($this->containsAny($keywords, $token)) {
                $score += 16;
            }
            if (str_contains($description, $token)) {
                $score += 12;
            }
            if (str_contains($category, $token) || str_contains($subcategory, $token)) {
                $score += 11;
            }
            $score += $this->fuzzyTokenScore($token, $primary, $aliases);
        }

        $weight = (int) ($candidate['search_weight'] ?? 50);
        $score += min(24, max(0, $weight / 5));
        $confidence = max(1, min(99, (int) round($score / 2.6)));

        return [
            'hsn_code' => $hsnCode,
            'name' => (string) ($candidate['primary_name'] ?? ''),
            'matched_alias' => $matchedAlias,
            'gst_rate' => (float) ($candidate['gst_rate'] ?? 0),
            'category' => (string) ($candidate['category'] ?? 'General'),
            'subcategory' => (string) ($candidate['subcategory'] ?? ''),
            'official_description' => (string) ($candidate['official_description'] ?? ''),
            'aliases' => array_values(array_filter($aliases)),
            'confidence' => $confidence,
            'score' => $score,
        ];
    }

    private function fuzzyTokenScore(string $token, string $primary, array $aliases): float
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 3) {
            return 0;
        }

        $best = 0.0;
        $targets = array_merge([$primary], $aliases);
        foreach ($targets as $target) {
            foreach (preg_split('/\s+/', (string) $target) ?: [] as $word) {
                $word = trim($word);
                if ($word === '') {
                    continue;
                }
                $distance = levenshtein($token, $word);
                $len = max(strlen($token), strlen($word));
                if ($len === 0) {
                    continue;
                }
                $ratio = 1 - ($distance / $len);
                if ($ratio >= 0.7) {
                    $best = max($best, $ratio * 24);
                }
            }
        }

        return $best;
    }

    private function containsAny(array $haystack, string $needle): bool
    {
        foreach ($haystack as $item) {
            if (str_contains((string) $item, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function cursorToArray(mixed $cursor): array
    {
        if (is_array($cursor)) {
            return array_map(fn ($row) => json_decode(json_encode($row), true) ?: [], $cursor);
        }

        if ($cursor instanceof \Traversable) {
            $rows = [];
            foreach ($cursor as $item) {
                $rows[] = json_decode(json_encode($item), true) ?: [];
            }

            return $rows;
        }

        return [];
    }
}

