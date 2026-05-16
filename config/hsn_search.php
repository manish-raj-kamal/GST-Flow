<?php

return [
    'cache_ttl_seconds' => 300,
    'debounce_ms' => 300,
    'atlas' => [
        'enabled' => env('MONGODB_ATLAS_SEARCH_ENABLED', true),
        'index' => env('MONGODB_ATLAS_SEARCH_INDEX', 'hsn_products_search'),
        'synonyms' => env('MONGODB_ATLAS_SEARCH_SYNONYMS', 'hsn_product_synonyms'),
    ],
    'atlas_index_definition' => [
        'mappings' => [
            'dynamic' => false,
            'fields' => [
                'hsn_code' => [
                    'type' => 'string',
                ],
                'primary_name' => [
                    'type' => 'autocomplete',
                    'tokenization' => 'edgeGram',
                    'minGrams' => 2,
                    'maxGrams' => 20,
                    'foldDiacritics' => true,
                ],
                'aliases' => [
                    'type' => 'autocomplete',
                    'tokenization' => 'edgeGram',
                    'minGrams' => 2,
                    'maxGrams' => 20,
                    'foldDiacritics' => true,
                ],
                'keywords' => [
                    'type' => 'string',
                ],
                'category' => [
                    'type' => 'string',
                ],
                'subcategory' => [
                    'type' => 'string',
                ],
                'official_description' => [
                    'type' => 'string',
                ],
                'status' => [
                    'type' => 'string',
                ],
                'search_weight' => [
                    'type' => 'number',
                ],
            ],
        ],
        'synonyms' => [
            [
                'name' => 'hsn_product_synonyms',
                'source' => [
                    'collection' => 'hsn_product_synonyms',
                ],
            ],
        ],
    ],
];
