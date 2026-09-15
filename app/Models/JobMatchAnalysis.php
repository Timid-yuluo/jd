<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class JobMatchAnalysis extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'resume_id',
        'job_description',
        'result',
        'match_score',
        'level',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'result' => 'array',
            'match_score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(JobMatchAuditLog::class)->latest('id');
    }

    public function maskedJobDescriptionPreview(int $limit = 160): string
    {
        return self::maskJobDescriptionPreview((string) $this->job_description, $limit);
    }

    public static function maskJobDescriptionPreview(string $jobDescription, int $limit = 160): string
    {
        $text = trim($jobDescription);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/[\r\n\t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', '[email]', $text) ?? $text;
        $text = preg_replace('/(?<!\d)(1[3-9]\d{9})(?!\d)/u', '[phone]', $text) ?? $text;
        $text = preg_replace('/https?:\/\/[^\s]+/iu', '[url]', $text) ?? $text;

        return Str::limit($text, $limit, '...');
    }
}
