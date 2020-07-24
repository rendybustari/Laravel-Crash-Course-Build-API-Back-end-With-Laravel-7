<?php

namespace App\Http\Controllers;

use App\BookingTickets;
use App\Tickets;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;

class MemberController extends Controller
{

    public function user(){
        $data = User::all();
        return response()->json([
            'message' => 'Data user',
            'data' => $data
            ],200);
    }

    public function profile(Request $request){

        $user = request()->user();

        $profile = $user->profile()->first();

        $data = ['name' => $user->name,
                 'email' => $user->email,
                 'fullname' => $profile->fullname,
                 'address' => $profile->address,
                 'identity' => asset('storage/identity/'.$profile->identity_file)];

        $token = $user->createToken('member',['accessProfile'])->plainTextToken;

        return response()->json([
            'message' => 'Profile user',
            'data' => $profile,
            'token' => $token
            ],200);
    }

    public function updateProfile(Request $request){
        $user = request()->user();

        $tokenAccess = $request->header('token');

        $model = Sanctum::$personalAccessTokenModel;
        $token = $model::findToken($tokenAccess);
        if($token == null){
            return response()->json([
                'message' => 'Token kadaluarsa',
                'data' => null
            ],401);
        }
        if($token->abilities[0] == 'accessProfile'){
            $token->delete();
            $profile = $user->profile()->first();

            $validate = Validator::make($request->only('fullname','address','identity'),[
                'fullname' => 'required',
                'address' => 'required',
                'file'  => 'required|mimes:png,jgp'
            ]);

            if($validate->errors()->has('fullname')){
                return response()->json([
                    'message' => 'Nama Lengkap Harus diisi',
                ],400);
            }

            if($validate->errors()->has('address')){
                return response()->json([
                    'message' => 'Alamat Harus diisi'
                ],400);
            }

            if($validate->errors()->has('file')){
                return response()->json([
                    'message' => 'Foto Identitas harus diupload sesuai dengan tipe yang diperbolehkan'
                ],400);
            }

            $profile->fullname = $request->fullname;
            $profile->address = $request->address;

            if($request->file('file')){
                $file = $request->file('file')->store('identity', 'public');
                $profile->identity_file = $file;
                $profile->save();
            }
            $profile->save();

            return response()->json([
                'message' => 'Update data profile berhasil',
                'data' => null,
            ],200);
        }
    }

    public function ticket(){
        $ticket = Tickets::all();

        return response()->json([
          'message' => 'Data Tiket',
          'data' => $ticket
        ],200);
    }

    public function ticketDetail($ticket_id){
        $user = request()->user();

        $ticket = Tickets::find($ticket_id);
        if(!$ticket){
            return response()->json([
                'message' => 'Data tiket tidak ada',
                'data' => null
            ],400);
        }

        $token = $user->createToken('member',['accessBookingTicket'])->plainTextToken;

        return response()->json([
            'message' => 'Data tiket',
            'data' => $ticket,
            'token' => $token
        ],200);

    }

    public function bookingTicket(Request $request, $ticket_id){

        $user = request()->user();

        $tokenAccess = $request->header('token');

        $model = Sanctum::$personalAccessTokenModel;
        $token = $model::findToken($tokenAccess);

        if($token == null){
            return response()->json([
                'status' => 'Token expired',
                'data' => null
            ],401);
        }
        if($token->abilities[0] == 'accessBookingTicket'){
            $token->delete();
            // check ticket by id
            $ticket = Tickets::find($ticket_id);
            if(!$ticket){
                return response()->json([
                    'message' => 'Tiket tidak ada',
                    'data' => null
                ],404);
            }

            // check seat available
            if($ticket->seat < 1){
                return response()->json([
                    'message' => 'Kursi sudah habis',
                    'data' => null
                ],400);
            }

            try{
                DB::beginTransaction();
                $bookingTicket = new BookingTickets();
                $bookingTicket->user_id = $user->id;
                $bookingTicket->ticket_id = $ticket->id;
                $bookingTicket->status = 0;
                $bookingTicket->save();

                $ticket->seat -= 1;
                $ticket->save();

                DB::commit();

                $status = true;
            } catch (Exception $e) {
                DB::rollback();
                $status = false;
            }

            if ($status == true) {
                return response()->json([
                    'message' => 'Tiket berhasil dipesan'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Tiket gagal dipesan'
                ], 200);
            }
        }
    }

    public function listBookingTicket(){
        $data = BookingTickets::get()
                ->map(function ($q){
                    $user = $q->user()->first();
                    $profile = $user->profile()->first();
                    $ticket = $q->ticket()->first();

                    $status = [
                        0 => 'Booking',
                        1 => 'Cancel',
                    ];

                    return [
                        'fullname' => $profile->fullname,
                        'email' => $user->email,
                        'to' => $ticket->to,
                        'price' => $ticket->price,
                        'status' => $status[$q->status]
                    ];

                });

        return response()->json([
            'message' => 'List Pemesanan Tiket',
            'data' => $data
        ],200);
    }

    public function detailBookingTicket($ticket_id){
        $user = request()->user();
        $data = BookingTickets::where(['user_id' => $user->id, 'ticket_id' => $ticket_id])->first();
        if(!$data){
            return response()->json([
                'message' => 'Tiket tidak ditemukan',
                'data' => null
            ],404);
        }

        $token = $user->createToken('member',['accessModifyingTicket'])->plainTextToken;

        $profile = $user->profile()->first();
        $ticket = $data->ticket()->first();

        $status = [
            0 => 'Booking',
            1 => 'Cancel',
        ];

        return response()->json([
            'message' => 'Data Pemesanan Ticket',
            'data' => [
                'fullname' => $profile->fullname,
                'email' => $user->email,
                'to' => $ticket->to,
                'price' => $ticket->price,
                'status' => $status[$data->status]
            ],
            'token' => $token
        ],200);
    }

    public function cancelBookingTicket($ticket_id){
        $user = request()->user();
        $data = BookingTickets::where(['user_id' => $user->id, 'ticket_id' => $ticket_id])->first();
        if(!$data){
            return response()->json([
                'message' => 'Tiket tidak ditemukan',
                'data' => null
            ],404);
        }

        $ticket = $data->ticket()->first();

        try{
            DB::beginTransaction();
            $ticket->seat += 1;
            $ticket->save();

            $data->status = 1;
            $data->save();

            DB::commit();
            $status = true;
        } catch (exception $e){
            DB::rollback();
            $status = false;
        }

        if($status == false){
            return response()->json([
                'message' => 'Tiket gagal dibatalkan',
                'data' => null,
            ],400);
        } else {
            return response()->json([
                'message' => 'Tiket berhasil dibatalkan',
                'data' => null,
            ],201);
        }


    }

}
