<?php

namespace App\Services;

use Illuminate\Support\Str;

class HsnNormalizationService
{
    private const STOP_WORDS = [
        'for', 'and', 'with', 'the', 'a', 'an', 'of', 'to', 'in', 'by', 'pack', 'piece', 'pcs', 'gst', 'hsn',
    ];

    private const SYNONYMS = [
        'paneer' => ['panir', 'cottage cheese', 'fresh cheese', 'milk cheese'],
        'ghee' => ['ghi', 'desi ghee', 'clarified butter', 'cow ghee', 'milk fat', 'butter oil'],
        'butter' => ['makhan', 'dairy fat', 'milk fat'],
        'biscuit' => ['biscits', 'biskit', 'cookies', 'cracker'],
        'laptop' => ['notebook', 'portable computer'],
        'mobile' => ['cell phone', 'smartphone', 'phone'],
        'charger' => ['adapter', 'power adapter', 'charging adapter'],
        'mobile charger' => ['phone charger', 'usb charger', 'mobile adapter'],
        'namkeen' => ['savory snack', 'mixture', 'snack'],
        'atta' => ['wheat flour', 'flour'],
        'dahi' => ['curd', 'yogurt', 'yoghurt'],
    ];

    public function normalizeQuery(string $query): array
    {
        $normalized = $this->normalizeText($query);
        $tokens = $this->tokenize($normalized);

        return [
            'raw' => trim($query),
            'normalized' => $normalized,
            'tokens' => $tokens,
            'expanded_tokens' => $this->expandTokens($tokens),
        ];
    }

    public function normalizeText(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }

    public function tokenize(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return collect(explode(' ', $value))
            ->map(fn (string $token) => trim($token))
            ->filter(fn (string $token) => $token !== '' && ! in_array($token, self::STOP_WORDS, true))
            ->unique()
            ->values()
            ->all();
    }

    public function expandTokens(array $tokens): array
    {
        $expanded = collect($tokens);

        foreach ($tokens as $token) {
            foreach (self::SYNONYMS as $canonical => $synonyms) {
                if ($token === $canonical || in_array($token, $synonyms, true)) {
                    $expanded->push($canonical);
                    foreach ($synonyms as $synonym) {
                        $expanded->push($this->normalizeText($synonym));
                    }
                }
            }
        }

        return $expanded->filter()->unique()->values()->all();
    }

    public function parseDelimitedList(array|string|null $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($v) => $this->normalizeText((string) $v))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(preg_split('/[,;|]/', $value) ?: [])
            ->map(fn (string $v) => $this->normalizeText($v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function enrichAliases(string $primaryName, array $aliases = [], array $keywords = []): array
    {
        $normalizedPrimary = $this->normalizeText($primaryName);
        $bag = collect([$normalizedPrimary])->merge($aliases)->merge($keywords);

        foreach (self::SYNONYMS as $canonical => $synonyms) {
            $shouldExpand = $bag->contains(fn ($item) => str_contains((string) $item, $canonical))
                || $bag->contains(fn ($item) => in_array((string) $item, $synonyms, true));

            if ($shouldExpand) {
                $bag->push($canonical);
                foreach ($synonyms as $synonym) {
                    $bag->push($synonym);
                }
            }
        }

        return $bag
            ->map(fn ($item) => $this->normalizeText((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function suggestionHints(string $query): array
    {
        $normalized = $this->normalizeText($query);
        if ($normalized === '') {
            return [];
        }

        $suggestions = collect();
        foreach (self::SYNONYMS as $canonical => $synonyms) {
            if (str_contains($canonical, $normalized) || in_array($normalized, $synonyms, true)) {
                $suggestions->push($canonical);
            }
        }

        return $suggestions->unique()->take(5)->values()->all();
    }
}

