<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiagSubcat extends Model
{
    protected $table = 'ref_diagsubcat';
    protected $fillable = [
        'diagmcat',
        'diagsubcat',
        'diagscatdesc',
        'updated_at',
        'created_at',
        'void'
    ];
}
