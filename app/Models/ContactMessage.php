<?php

namespace App\Models;

use App\Support\Casts\Mobile;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $guarded = [];

    protected $casts = ['mobile' => Mobile::class];
}
