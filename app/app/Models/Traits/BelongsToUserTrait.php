<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Support\Facades\Auth;

trait BelongsToUserTrait
{
    public static function bootBelongsToUserTrait(): void
    {
        static::creating(function ($model) {
            $user = Auth::user();
            if ($user && ! isset($model->user_id)) {
                $model->user_id = $user->id;
            }
        });
    }
}
