<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserProfiles extends Model
{
    protected $table = 'user_profiles';
    protected $primaryKey = 'user_id';
}
