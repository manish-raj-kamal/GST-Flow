<?php

namespace App\Services;

use App\Models\StateCode;

class GstinService
{
    private const GSTIN_PATTERN = '/^\d{2}[A-Z]{5}\d{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/';

    public function validate(string $gstin): array
    {
        return $this->inspect($gstin);
    }

    public function inspect(?string $gstin): array
    {
        $gstin = strtoupper(trim((string) $gstin));
        $parts = $this->extractParts($gstin);
        $stateCode = $parts['state_code'] ?? null;
        $stateName = $stateCode
            ? StateCode::query()->where('code', $stateCode)->value('state_name')
            : null;
        $validFormat = (bool) preg_match(self::GSTIN_PATTERN, $gstin);
        $validChecksum = $validFormat && $this->isChecksumValid($gstin);

        return [
            'gstin' => $gstin,
            'valid_format' => $validFormat,
            'valid_checksum' => $validChecksum,
            'is_valid' => $validFormat && $validChecksum,
            'state_code' => $stateCode,
            'state_name' => $stateName,
            'parts' => $parts,
        ];
    }

    public function extractParts(string $gstin): array
    {
        $value = strtoupper(trim($gstin));

        if (strlen($value) !== 15) {
            return [];
        }

        return [
            'state_code' => substr($value, 0, 2),
            'pan' => substr($value, 2, 10),
            'entity_number' => substr($value, 12, 1),
            'default_char' => substr($value, 13, 1),
            'checksum' => substr($value, 14, 1),
        ];
    }

    public function detectStateCode(string $gstin): ?string
    {
        $parts = $this->extractParts($gstin);

        return $parts['state_code'] ?? null;
    }

    public function detectStateName(string $gstin): ?string
    {
        $stateCode = $this->detectStateCode($gstin);

        if (! $stateCode) {
            return null;
        }

        return StateCode::query()->where('code', $stateCode)->value('state_name');
    }

    private function isChecksumValid(string $gstin): bool
    {
        $value = strtoupper(trim($gstin));

        if (! preg_match(self::GSTIN_PATTERN, $value)) {
            return false;
        }

        $base = substr($value, 0, 14);
        $provided = substr($value, 14, 1);

        return $this->generateChecksum($base) === $provided;
    }

    private function generateChecksum(string $input): string
    {
        $codePoints = str_split('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $factor = [1, 2];
        $mod = 36;
        $sum = 0;

        for ($i = strlen($input) - 1, $j = 0; $i >= 0; $i--, $j++) {
            $char = $input[$i];
            $codePoint = array_search($char, $codePoints, true);

            if ($codePoint === false) {
                return '0';
            }

            $digit = $factor[$j % 2] * $codePoint;
            $sum += intdiv($digit, $mod) + ($digit % $mod);
        }

        $checksum = ($mod - ($sum % $mod)) % $mod;

        return $codePoints[$checksum];
    }
}
