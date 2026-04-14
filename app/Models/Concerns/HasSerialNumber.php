<?php

namespace App\Models\Concerns;

trait HasSerialNumber
{
    public function generateNextSerialNumber(): string
    {
        $parts = [];

        foreach (['prefix', 'number', 'suffix'] as $index => $part) {
            // Check if this part is enabled
            $showValue = $this->getAttribute($part . '_show');
            if ($showValue !== null && !$showValue) {
                continue;
            }

            $value = trim($this->getPartValue($part));

            if ($value === '') {
                continue;
            }

            $parts[] = $value;

            if ($part !== 'suffix') {
                $nextPart = ['prefix' => 'number', 'number' => 'suffix'][$part] ?? null;
                $nextValue = $nextPart ? trim($this->getPartValue($nextPart)) : '';
                $separatorField = $part . '_separator';
                $separator = $this->normalizeSeparator($this->{$separatorField} ?? 'none');

                if ($separator !== '' && $nextValue !== '') {
                    $parts[] = $separator;
                }
            }
        }

        return implode('', $parts);
    }

    protected function getPartValue(string $part): string
    {
        $type = $this->getPartType($part);
        $val = $this->{$part . '_value'} ?? '';

        switch ($type) {
            case 'manual text':
            case 'value/number':
                return $val;
            case 'fixed value':
                return $val;
            case 'date':
                return now()->format('Y-m-d');
            case 'year':
                return now()->format('Y');
            case 'month-year':
                return now()->format('m-Y');
            case 'date-month':
                return now()->format('d-m');
            case 'auto increment':
                $nextNumber = $this->resolveNextAutoIncrementNumber($part);

                return $this->formatAutoIncrementNumber($nextNumber, $part);
            case 'auto generate':
                $length = $this->{$part . '_length'} ?? 4;
                return $this->generateAlphaNumeric($length);
            default:
                return $val;
        }
    }

    protected function getPartType(string $part): string
    {
        return $this->{$part . '_type'} ?? ($part === 'number' ? 'auto increment' : 'manual text');
    }

    protected function resolveConfiguredAutoIncrementStart(string $part): int
    {
        $start = $this->{$part . '_value'} ?? null;

        $start = (int) ($start ?: 1);

        return max(1, $start);
    }

    protected function resolveNextAutoIncrementNumber(string $part): int
    {
        $start = $this->resolveConfiguredAutoIncrementStart($part);
        $highestKnown = null;

        if (method_exists($this, 'getLastAutoIncrementValueForPart')) {
            $lastValue = $this->getLastAutoIncrementValueForPart($part);

            if (is_numeric($lastValue)) {
                $highestKnown = (int) $lastValue;
            }
        }

        if ($highestKnown !== null) {
            return max($start, $highestKnown >= $start ? $highestKnown + 1 : $start);
        }

        return $start;
    }

    protected function formatAutoIncrementNumber(int $number, string $part = 'number'): string
    {
        $length = $this->{$part . '_length'} ?? null;
        $value = (string) $number;

        if ($length && is_numeric($length) && (int) $length > strlen($value)) {
            return str_pad($value, (int) $length, '0', STR_PAD_LEFT);
        }

        return $value;
    }

    protected function normalizeSeparator(?string $separator): string
    {
        return $separator && $separator !== 'none' ? $separator : '';
    }

    public function extractAutoIncrementNumber(string $serial): ?int
    {
        return $this->extractAutoIncrementNumberForPart($serial, 'number');
    }

    protected function extractAutoIncrementNumberForPart(string $serial, string $targetPart): ?int
    {
        $pattern = $this->buildSerialMatchingPattern($targetPart);

        if (! preg_match($pattern, $serial, $matches)) {
            return null;
        }

        return isset($matches['target']) ? (int) $matches['target'] : null;
    }

    protected function buildSerialMatchingPattern(string $targetPart): string
    {
        $pattern = '^';
        $parts = ['prefix', 'number', 'suffix'];

        foreach ($parts as $index => $part) {
            $type = $this->getPartType($part);
            $hasValue = $this->partHasOutput($part);

            if (! $hasValue) {
                continue;
            }

            $pattern .= $this->buildPartPattern($part, $type, $part === $targetPart);

            if ($part !== 'suffix') {
                $nextPart = $parts[$index + 1] ?? null;

                if ($nextPart && $this->partHasOutput($nextPart)) {
                    $pattern .= preg_quote($this->normalizeSeparator($this->{$part . '_separator'} ?? null), '/');
                }
            }
        }

        return '/' . $pattern . '$/';
    }

    protected function buildPartPattern(string $part, string $type, bool $isTarget): string
    {
        return match ($type) {
            'auto increment' => $isTarget ? '(?<target>\d+)' : '\d+',
            'auto generate' => '[A-Z0-9]{' . max(1, (int) ($this->{$part . '_length'} ?? 4)) . '}',
            default => preg_quote($this->resolveStaticPartValue($part, $type), '/'),
        };
    }

    protected function resolveStaticPartValue(string $part, ?string $type = null): string
    {
        $type = $type ?? $this->getPartType($part);
        $val = $this->{$part . '_value'} ?? '';

        return match ($type) {
            'manual text', 'value/number', 'fixed value' => (string) $val,
            'date' => now()->format('Y-m-d'),
            'year' => now()->format('Y'),
            'month-year' => now()->format('m-Y'),
            'date-month' => now()->format('d-m'),
            default => '',
        };
    }

    protected function partHasOutput(string $part): bool
    {
        // Check if this part is enabled via show field
        $showField = $part . '_show';
        $showValue = $this->getAttribute($showField);
        if ($showValue !== null && !$showValue) {
            return false;
        }

        $type = $this->getPartType($part);

        if (in_array($type, ['auto increment', 'auto generate'], true)) {
            return true;
        }

        return trim($this->resolveStaticPartValue($part, $type)) !== '';
    }

    protected function incrementString(string $str): string
    {
        // Simple string increment: find trailing numbers and increment them
        return preg_replace_callback('/(\d+)$/', function ($matches) {
            $num = $matches[1];
            $inc = (int)$num + 1;
            return str_pad($inc, strlen($num), '0', STR_PAD_LEFT);
        }, $str);
    }

    protected function getCurrentSerialCount(): int
    {
        // Override in model to return count of existing records for the FY
        return 0;
    }

    protected function generateAlphaNumeric(int $length = 4): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No I,0,O for readability
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $result;
    }

    public function getSerialPreviewAttribute(): string
    {
        return $this->generateNextSerialNumber();
    }
}
