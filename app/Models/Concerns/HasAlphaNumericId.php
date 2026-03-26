<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasAlphaNumericId
{
    public static function bootHasAlphaNumericId(): void
    {
        static::creating(function (Model $model): void {
            $keyName = $model->getKeyName();

            if (! empty($model->{$keyName})) {
                return;
            }

            $model->{$keyName} = static::generateUniqueAlphaId($model);
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function getRouteKeyName()
    {
        return $this->getKeyName();
    }

protected static function generateUniqueAlphaId(Model $model): string
    {
        $attempts = 0;
        $length = method_exists($model, 'idLength') ? $model->idLength() : 6;

        do {
            $id = strtoupper(Str::random($length));
            $attempts++;
        } while ($model->newQuery()->whereKey($id)->exists() && $attempts < 20);

        if ($attempts >= 20 && $model->newQuery()->whereKey($id)->exists()) {
            throw new \RuntimeException('Unable to generate a unique ' . $length . '-character alphanumeric ID.');
        }

        return $id;
    }
}
