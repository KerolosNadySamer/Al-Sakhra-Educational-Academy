<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_commission',
        'live_commission',
        'book_commission',
        'exam_commission',
    ];

    protected $casts = [
        'course_commission' => 'decimal:2',
        'live_commission' => 'decimal:2',
        'book_commission' => 'decimal:2',
        'exam_commission' => 'decimal:2',
    ];
}
