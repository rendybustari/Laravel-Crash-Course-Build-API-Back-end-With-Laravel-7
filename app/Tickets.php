<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tickets extends Model
{
    protected $table = 'tickets';

    public function booking(){
        return $this->hasMany(BookingTickets::class,'ticket_id','id');
    }
}
