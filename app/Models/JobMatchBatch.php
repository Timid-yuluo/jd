<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class JobMatchBatch extends Model
{
    protected $fillable = ['user_id', 'resume_id', 'total', 'completed', 'failed', 'status', 'completed_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(JobMatchAnalysis::class, 'batch_id');
    }
}
