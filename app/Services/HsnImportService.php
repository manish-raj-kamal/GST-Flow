<?php

namespace App\Services;

use App\Models\HsnProduct;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HsnImportService
{
    public function __construct(
        private readonly HsnNormalizationService $normalizationService,
        private readonly HsnSearchService $searchService,
    ) {
    }

    public function importUploadedFile(UploadedFile $file, string $source = 'manual'): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $rows = match ($extension) {
            'csv' => $this->readCsv($file->getRealPath() ?: ''),
            'xls', 'xlsx' => $this->readSpreadsheet($file->getRealPath() ?: ''),
            default => throw ValidationException::withMessages([
                'file' => 'Only CSV, XLS and XLSX files are supported.',
            ]),
        };

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $line => $row) {
            try {
                $payload = $this->normalizeRow($row, $source);
                if ($payload === null) {
                    $skipped++;
                    continue;
                }

                $existing = HsnProduct::query()->where('hsn_code', $payload['hsn_code'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    HsnProduct::create($payload);
                    $created++;
                }
            } catch (\Throwable $exception) {
                $errors[] = [
                    'line' => $line,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        $this->searchService->ensureIndexes();

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 30),
        ];
    }

    private function normalizeRow(array $row, string $source): ?array
    {
        $hsnCode = preg_replace('/\D+/', '', (string) $this->pick($row, ['hsn_code', 'code', 'hsn', 'sac_code']));
        $name = (string) $this->pick($row, ['primary_name', 'product_name', 'name', 'item']);
        $description = (string) $this->pick($row, ['official_description', 'description', 'details']);

        if ($hsnCode === '' || $name === '') {
            return null;
        }

        $aliases = $this->normalizationService->parseDelimitedList($this->pick($row, ['aliases', 'synonyms']));
        $keywords = $this->normalizationService->parseDelimitedList($this->pick($row, ['keywords', 'search_keywords']));
        $enrichedAliases = $this->normalizationService->enrichAliases($name, $aliases, $keywords);
        $allKeywords = collect($keywords)
            ->merge($this->normalizationService->tokenize($this->normalizationService->normalizeText($description)))
            ->merge($this->normalizationService->tokenize($this->normalizationService->normalizeText($name)))
            ->unique()
            ->values()
            ->all();

        return [
            'hsn_code' => substr($hsnCode, 0, 8),
            'primary_name' => $name,
            'aliases' => $enrichedAliases,
            'category' => $this->pick($row, ['category']) ?: 'General',
            'subcategory' => $this->pick($row, ['subcategory']) ?: '',
            'official_description' => $description !== '' ? $description : $name,
            'keywords' => $allKeywords,
            'gst_rate' => (float) ($this->pick($row, ['gst_rate', 'rate', 'tax_rate']) ?: 18),
            'search_weight' => (int) ($this->pick($row, ['search_weight']) ?: 90),
            'status' => strtolower((string) ($this->pick($row, ['status']) ?: 'active')),
            'source' => (string) ($this->pick($row, ['source']) ?: $source),
        ];
    }

    private function readCsv(string $path): array
    {
        if ($path === '' || ! is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $rows = [];
        $headers = [];
        $line = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if ($line === 1) {
                $headers = array_map(fn ($v) => strtolower(trim((string) $v)), $data);
                continue;
            }

            if (empty(array_filter($data, fn ($v) => trim((string) $v) !== ''))) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $data[$index] ?? '';
            }
            $rows[$line] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readSpreadsheet(string $path): array
    {
        if (! class_exists(IOFactory::class)) {
            throw ValidationException::withMessages([
                'file' => 'Spreadsheet import requires phpoffice/phpspreadsheet.',
            ]);
        }

        $sheet = IOFactory::load($path)->getActiveSheet();
        $records = $sheet->toArray(null, true, true, true);
        if (empty($records)) {
            return [];
        }

        $headerRow = array_shift($records) ?: [];
        $headers = collect($headerRow)->map(fn ($v) => strtolower(trim((string) $v)))->values()->all();

        $rows = [];
        $line = 1;
        foreach ($records as $record) {
            $line++;
            $flatValues = array_values($record);
            if (empty(array_filter($flatValues, fn ($v) => trim((string) $v) !== ''))) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $flatValues[$index] ?? '';
            }
            $rows[$line] = $row;
        }

        return $rows;
    }

    private function pick(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            foreach ($row as $rowKey => $value) {
                if (strtolower((string) $rowKey) === strtolower($key) && filled($value)) {
                    return trim((string) $value);
                }
            }
        }

        return null;
    }
}

