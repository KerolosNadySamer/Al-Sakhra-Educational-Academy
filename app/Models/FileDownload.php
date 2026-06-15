<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileDownload extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'lesson_file_id',
        'download_count',
        'first_download_at',
        'last_download_at',
    ];

    protected $casts = [
        'first_download_at' => 'datetime',
        'last_download_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function lessonFile(): BelongsTo
    {
        return $this->belongsTo(LessonFile::class);
    }
}
