<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login','AuthController@login');

Route::post('/register', 'AuthController@registration');

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/user','memberController@user');

    Route::get('/profile','MemberController@profile');
    Route::post('/profile','MemberController@updateProfile');

    Route::get('/ticket','MemberController@ticket');
    Route::get('/ticket/{ticket_id}','MemberController@ticketDetail');
    Route::post('/ticketboo/{ticket_id}','MemberController@bookingTicket');

    Route::get('/booking/','MemberController@listBookingTicket');
    Route::get('/booking/{booking_id}','MemberController@detailBookingTicket');
    Route::put('/booking/cancel/{booking_id}','MemberController@cancelBookingTicket');


});
