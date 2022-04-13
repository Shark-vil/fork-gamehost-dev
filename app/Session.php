<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $hidden = [
        'id',
        'game_id',
        // 'password',
    ];
}
