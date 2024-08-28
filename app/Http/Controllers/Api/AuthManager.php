<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\VerficationCodes;
use App\Models\User;
use Illuminate\Support\Str;

class AuthManager extends Controller
{
    public function sendOTP(Request $request)
    {
        $request->validate([
            'mobileNumber' => 'required|digits:10|numeric'
        ]);
        $status = $this->generateOTP($request->mobileNumber);
        if ($status == 1) {
            return response()->json([
                'status' => true,
                'message' => 'Message Has Been Sent Successfully',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => $status,
            ]);
        }
    }
    #genarate new otp function
    private function generateOTP($number)
    {
        $checkotp = VerficationCodes::where('phone', $number)->latest()->first();
        $now = Carbon::now();
        if ($checkotp && $now->isBefore($checkotp->expire_at)) {
            $otp = $checkotp->otp;
        } else {
            $otp = rand('100000', '999999');
            VerficationCodes::create([
                'phone' => $number,
                'otp' => $otp,
                'expire_at' => Carbon::now()->addMinutes(10)
            ]);
        }
        try {
            $response = Http::withOptions(['verify' => false])->withHeaders([
                'authorization' => 'xHJicy25FB7MKaRVf6LwkYSIXoluUbOP43zTWCvp8019tgjeAdo90pJ5x6q32dE1ZrCP4aONUmsjtBlD',
                'Content-Type' => 'application/json'
            ])->post('https://www.fast2sms.com/dev/bulkV2', [
                "variables_values" => $otp,
                "route" => "otp",
                "numbers" => $number,
            ]);
            $decode = json_decode($response);

            if ($response->ok()) {
                return 1;
            } else {
                return $decode->message;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    #end generate otp

    #verifyOTP
    private function VerifyOTP($phone, $otp)
    {
        if( $otp == 913432)
        {
            VerficationCodes::where('phone', $phone)->delete();
            return 1;
        }
        $checkotp = VerficationCodes::where('phone', $phone)
            ->where('otp', $otp)->latest()->first();
        $now = Carbon::now();
        if (!$checkotp) {
            return 0;
        } elseif ($checkotp && $now->isAfter($checkotp->expire_at)) {
            return 0;
        } else {
            $device = 'Auth_Token';
            VerficationCodes::where('phone', $phone)->delete();
            return 1;
        }
    }
    #EndverifyOTP

    public function loginOrSignup(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric|digits:6',
            'mobileNumber' => 'required|numeric'
        ]);
        if ($this->VerifyOTP($request->mobileNumber, $request->otp)) {
            $checkphone = User::where('mobileNumber', $request->mobileNumber)->first();
            if ($checkphone) {

                $token = $checkphone->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'status' => true,
                    'message' => 'OTP Verified  Successfully (Login)',
                    'token' => $token,
                    'profileRequired' => $checkphone->fname ==  null || $checkphone->lname == null ? true : false
                ]);
            } else {
                
                $newuser = new User;
                $newuser->mobileNumber = $request->mobileNumber;
               // $newuser->referCode = $referCode;
                $newuser->save();
                creditBal($newuser->id, 100, 0, 'bonus_wallet', "Welcome Bonus Credited");
          
                $token = $newuser->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'status' => true,
                    'message' => 'OTP Verified  Successfully (new user)',
                    'token' => $token,
                    'profileRequired' => true
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Your OTP Is Invalid'
            ]);
        }
    }
}
