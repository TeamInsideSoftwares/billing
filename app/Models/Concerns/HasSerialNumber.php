<?php

namespace App\Models\Concerns;

trait HasSerialNumber
{
    public function generateNextSerialNumber(): string
    {
        if (! $this->use_auto_generate) {
            return $this->serial_number ?: 'MANUAL';
        }

        $prefix = $this->prefix ?? '';
        $suffix = $this->suffix ?? '';

        $middle = '';

        if (($this->serial_mode ?? 'alphanumeric') === 'sequential') {
            $currentCount = $this->getCurrentSerialCount();
            $padLength = strlen((string) $this->auto_increment_start ?? 1) + 2;
            $middle = str_pad(($this->auto_increment_start ?? 1) + $currentCount, $padLength, '0', STR_PAD_LEFT);
        } else {
            $length = $this->alphanumeric_length ?? 4;
            $middle = $this->generateAlphaNumeric($length);
        }

        return $prefix . $middle . $suffix;
    }

    protected function getCurrentSerialCount(): int
    {
        // Override in model
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

