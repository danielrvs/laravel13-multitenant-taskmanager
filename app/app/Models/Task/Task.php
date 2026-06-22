<?php

declare(strict_types=1);

namespace App\Models\Task;

use App\Models\Task\Vo\TaskPriority;
use App\Models\Task\Vo\TaskStatus;
use App\Models\Traits\BelongsToTenantTrait;
use App\Models\Traits\BelongsToUserTrait;
use App\Models\User;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['tenant_id', 'user_id', 'title', 'description', 'status', 'priority', 'due_date', 'assigned_to'])]
class Task extends Model
{
    use BelongsToTenantTrait, BelongsToUserTrait, HasFactory;

    protected $casts = [
        'status' => TaskStatus::class,
        'priority' => TaskPriority::class,
        'due_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignTo(int $userId): void
    {
        $user = User::findOrFail($userId);
        if ($this->tenant_id !== $user->tenant_id) {
            throw ValidationException::withMessages([
                'assigned_to' => 'User does not belong to the same tenant.'
            ]);
        }

        if ($user->id === $this->user_id) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Cannot assign task to the creator.'
            ]);
        }

        $this->assigned_to = $userId;
        $this->save();
    }

    public function transitionStatus(string|TaskStatus $targetStatus): void
    {
        $this->status = $this->status->transitionTo($targetStatus);
        $this->save();
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->due_date->isPast() && !$this->isCompleted();
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                if (is_null($value) || trim($value) === '') {
                    throw ValidationException::withMessages([
                        'title' => 'Title cannot be empty'
                    ]);
                }

                if (strlen($value) > 255) {
                    throw ValidationException::withMessages([
                        'title' => 'Title cannot exceed 255 characters'
                    ]);
                }

                return $value;
            }
        );
    }
}
