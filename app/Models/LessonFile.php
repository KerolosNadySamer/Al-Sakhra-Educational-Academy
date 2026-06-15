<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'file_path',
        'allow_download',
        'max_downloads',
        'download_expiry_days',
        'download_limit',
        'expiry_days',
    ];

    protected $casts = [
        'allow_download' => 'boolean',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(FileDownload::class);
    }
}
