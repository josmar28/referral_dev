<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiagMain extends Model
{
    protected $table = 'ref_daigmaincategory';
    protected $fillable = [
        'diagcat',
        'catdesc',
        'updated_at',
        'created_at',
        'void'
    ];
}
