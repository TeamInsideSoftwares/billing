<?php

namespace App\Models\Concerns;

trait HasSerialNumber
{
    public function generateNextSerialNumber(): string
    {
        $prefix = $this->getPartValue('prefix');
        $number = $this->getPartValue('number');
        $suffix = $this->getPartValue('suffix');

        return $prefix . '-' . $number . '-' . $suffix;
    }

    protected function getPartValue(string $part): string
    {
        $type = $this->{$part . '_type'} ?? ($part == 'number' ? 'auto increment' : 'manual text');
        $val = $this->{$part . '_value'} ?? '';

        switch ($type) {
            case 'manual text':
            case 'value/number':
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
                $currentCount = $this->getCurrentSerialCount();
                $start = is_numeric($val) ? (int)$val : 1;
                return (string)($start + $currentCount);
            case 'auto generate':
                $length = $this->{$part . '_length'} ?? 4;
                return $this->generateAlphaNumeric($length);
            default:
                return $val;
        }
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
