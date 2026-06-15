<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'allow_multiple_devices',
        'max_devices',
        'watermark_enabled',
        'allow_pdf_download',
    ];

    protected $casts = [
        'allow_multiple_devices' => 'boolean',
        'watermark_enabled' => 'boolean',
        'allow_pdf_download' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
