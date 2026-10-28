<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

#[Fillable(['key', 'value', 'type', 'group', 'label'])]
class SystemSetting extends Model
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('system_settings', function (): array {
            if (! Schema::hasTable('system_settings')) {
                return [];
            }

            return static::all()->mapWithKeys(fn (self $setting) => [$setting->key => $setting->castValue()])->all();
        });

        return $all[$key] ?? $default;
    }

    public static function flush(): void
    {
        Cache::forget('system_settings');
    }

    public function castValue(): mixed
    {
        return match ($this->type) {
            'bool' => (bool) $this->value,
            'int' => (int) $this->value,
            default => $this->value,
        };
    }
}
