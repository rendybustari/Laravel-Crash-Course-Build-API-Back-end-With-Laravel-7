<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BookingTickets extends Model
{
    protected $table = 'booking_tickets';

    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function ticket(){
        return $this->belongsTo(Tickets::class,'ticket_id','id');
    }
}
