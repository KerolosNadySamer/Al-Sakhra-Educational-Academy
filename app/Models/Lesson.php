<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'description', 'order_number', 'is_free'];

    protected $casts = [
        'is_free' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function video(): HasOne
    {
        return $this->hasOne(Video::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(LessonFile::class);
    }
}
