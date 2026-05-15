<?php

namespace App\Services;

use App\Models\GstChangeNotification;
use App\Models\HsnCode;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HsnCatalogSyncService
{
    public function sync(array $sources = []): array
    {
        $sources = array_values(array_filter($sources));

        $created = 0;
        $updated = 0;
        $failedSources = [];

        foreach ($sources as $source) {
            try {
                [$new, $changed] = $this->syncSource($source);
                $created += $new;
                $updated += $changed;
            } catch (\Throwable $e) {
                $failedSources[] = [
                    'source' => $source,
                    'error' => $e->getMessage(),
                ];
            }
        }

        [$productCreated, $productUpdated] = $this->syncMissingFromProducts();
        $created += $productCreated;
        $updated += $productUpdated;

        $this->flushCaches();

        return [
            'created' => $created,
            'updated' => $updated,
            'failed_sources' => $failedSources,
        ];
    }

    private function syncSource(string $source): array
    {
        $response = Http::timeout(30)->get($source);
        $response->throw();

        $body = (string) $response->body();
        $contentType = strtolower((string) $response->header('content-type'));

        $rows = str_contains($contentType, 'json') || $this->looksLikeJson($body)
            ? $this->parseJsonRows($body)
            : $this->parseCsvRows($body);

        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $normalized = $this->normalizeRow($row);
            if (! $normalized) {
                continue;
            }

            $existing = $this->findActiveRow($normalized['hsn_code']);
            if (! $existing) {
                HsnCode::create($normalized);
                $created++;
                continue;
            }

            $hasRateChanged = (float) $existing->gst_rate !== (float) $normalized['gst_rate'];
            $hasTextChanged = (string) $existing->description !== (string) $normalized['description']
                || (string) $existing->category !== (string) $normalized['category'];

            if (! $hasRateChanged && ! $hasTextChanged) {
                continue;
            }

            if ($hasRateChanged) {
                $existing->update([
                    'effective_to' => now()->toDateString(),
                    'status' => 'inactive',
                ]);

                HsnCode::create($normalized);
                $updated++;

                GstChangeNotification::create([
                    'type' => 'hsn_rate_change',
                    'reference_key' => $normalized['hsn_code'],
                    'message' => sprintf(
                        'GST rate for HSN %s updated from %s%% to %s%%',
                        $normalized['hsn_code'],
                        $existing->gst_rate,
                        $normalized['gst_rate']
                    ),
                    'old_value' => ['gst_rate' => (float) $existing->gst_rate],
                    'new_value' => ['gst_rate' => (float) $normalized['gst_rate']],
                    'effective_from' => $normalized['effective_from'],
                    'meta' => ['source' => $source],
                    'is_read' => false,
                ]);
                continue;
            }

            $existing->update([
                'description' => $normalized['description'],
                'category' => $normalized['category'],
                'effective_from' => $normalized['effective_from'],
                'effective_to' => null,
                'status' => 'active',
            ]);
            $updated++;
        }

        return [$created, $updated];
    }

    private function parseJsonRows(string $body): array
    {
        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            return [];
        }

        if (isset($decoded['data']) && is_array($decoded['data'])) {
            return $decoded['data'];
        }

        return array_is_list($decoded) ? $decoded : [];
    }

    private function parseCsvRows(string $body): array
    {
        $lines = preg_split('/\r\n|\n|\r/', trim($body)) ?: [];
        if (count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines) ?: '');
        $headers = array_map(fn ($header) => Str::lower(trim((string) $header)), $headers);

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line);
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $values[$index] ?? null;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function normalizeRow(array $row): ?array
    {
        $hsnCode = $this->pick($row, ['hsn_code', 'hsn', 'code', 'sac_code']);
        $description = $this->pick($row, ['description', 'item', 'name', 'goods_description']);

        if (! $hsnCode || ! $description) {
            return null;
        }

        $cleanCode = preg_replace('/\D+/', '', (string) $hsnCode);
        if (! $cleanCode) {
            return null;
        }

        $gstRateRaw = $this->pick($row, ['gst_rate', 'rate', 'tax_rate']);
        $gstRate = is_numeric($gstRateRaw) ? (float) $gstRateRaw : 18.0;

        $category = $this->pick($row, ['category', 'chapter', 'segment']) ?: $this->inferCategoryFromDescription((string) $description);
        $effectiveDate = $this->pick($row, ['effective_date', 'date']) ?: '2017-07-01';
        $status = $this->pick($row, ['status']) ?: 'active';

        return [
            'hsn_code' => substr($cleanCode, 0, 8),
            'description' => trim((string) $description),
            'category' => trim((string) $category),
            'gst_rate' => $gstRate,
            'effective_date' => $effectiveDate,
            'effective_from' => $effectiveDate,
            'effective_to' => null,
            'status' => trim((string) $status),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function syncMissingFromProducts(): array
    {
        $created = 0;
        $updated = 0;

        $products = Product::query()->get(['hsn_code', 'product_name', 'category', 'gst_rate']);
        foreach ($products as $product) {
            $hsnCode = preg_replace('/\D+/', '', (string) $product->hsn_code);
            if (! $hsnCode) {
                continue;
            }

            $existing = HsnCode::query()->where('hsn_code', $hsnCode)->first();
            if (! $existing) {
                HsnCode::create([
                    'hsn_code' => substr($hsnCode, 0, 8),
                    'description' => (string) ($product->product_name ?: 'Product mapped from catalog'),
                    'category' => (string) ($product->category ?: 'General'),
                    'gst_rate' => (float) ($product->gst_rate ?: 18),
                    'effective_date' => '2017-07-01',
                    'effective_from' => '2017-07-01',
                    'effective_to' => null,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
                continue;
            }

            $dirty = false;
            if (! $existing->category && $product->category) {
                $existing->category = $product->category;
                $dirty = true;
            }
            if ((! $existing->description || $existing->description === 'Product mapped from catalog') && $product->product_name) {
                $existing->description = $product->product_name;
                $dirty = true;
            }
            if ($dirty) {
                $existing->save();
                $updated++;
            }
        }

        return [$created, $updated];
    }

    public function resolveCurrentRateForHsn(string $hsnCode, ?float $fallback = null): ?float
    {
        $hsnCode = preg_replace('/\D+/', '', $hsnCode);
        if (! $hsnCode) {
            return $fallback;
        }

        $cacheKey = 'gst:hsn:rate:'.$hsnCode.':'.now()->toDateString();

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($hsnCode, $fallback) {
            $row = HsnCode::query()
                ->where('hsn_code', $hsnCode)
                ->where('status', 'active')
                ->orderByDesc('effective_from')
                ->first();

            if (! $row) {
                return $fallback;
            }

            return (float) $row->gst_rate;
        });
    }

    public function matchHsnByDescription(string $query): array
    {
        $cacheKey = 'hsn_match_'.md5($query);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query) {
            return HsnCode::query()
                ->where('description', 'like', "%{$query}%")
                ->orWhere('hsn_code', 'like', "{$query}%")
                ->where('status', 'active')
                ->limit(10)
                ->get()
                ->toArray();
        });
    }

    public function auditProductRates(): array
    {
        $auditResults = [];
        $products = Product::all();

        foreach ($products as $product) {
            $currentRate = $this->resolveCurrentRateForHsn($product->hsn_code);

            if ($currentRate !== null && (float) $product->gst_rate !== (float) $currentRate) {
                $auditResults[] = [
                    'product_id' => $product->_id,
                    'product_name' => $product->product_name,
                    'hsn_code' => $product->hsn_code,
                    'stored_rate' => $product->gst_rate,
                    'current_rate' => $currentRate,
                ];
            }
        }

        return $auditResults;
    }

    private function inferCategoryFromDescription(string $description): string
    {
        $value = Str::lower($description);

        return match (true) {
            str_contains($value, 'service') => 'Services',
            str_contains($value, 'medical'), str_contains($value, 'pharma') => 'Healthcare',
            str_contains($value, 'bread'), str_contains($value, 'food') => 'Food',
            str_contains($value, 'software'), str_contains($value, 'computer') => 'Technology',
            default => 'General',
        };
    }

    private function pick(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            foreach ($row as $rowKey => $value) {
                if (Str::lower((string) $rowKey) === Str::lower($key) && filled($value)) {
                    return trim((string) $value);
                }
            }
        }

        return null;
    }

    private function looksLikeJson(string $body): bool
    {
        $trimmed = ltrim($body);

        return str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[');
    }

    private function flushCaches(): void
    {
        Cache::flush();
    }

    private function findActiveRow(string $hsnCode): ?HsnCode
    {
        return HsnCode::query()
            ->where('hsn_code', $hsnCode)
            ->where('status', 'active')
            ->orderByDesc('effective_from')
            ->first();
    }
}
