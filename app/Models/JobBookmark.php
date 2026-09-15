<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class JobBookmark extends Model
{
    protected $fillable = ['user_id', 'title', 'company', 'job_description', 'external_recruitment_id', 'note', 'tags'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function externalRecruitment(): BelongsTo
    {
        return $this->belongsTo(ExternalRecruitment::class);
    }
}
