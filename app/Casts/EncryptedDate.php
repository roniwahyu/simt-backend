<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Carbon;

class EncryptedDate implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);
            return $decrypted ? Carbon::parse($decrypted) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            $dateString = $value->toDateString();
        } else {
            try {
                $dateString = Carbon::parse($value)->toDateString();
            } catch (\Exception $e) {
                $dateString = $value;
            }
        }

        return Crypt::encryptString($dateString);
    }
}
