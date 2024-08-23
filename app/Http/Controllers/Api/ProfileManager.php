<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;

class ProfileManager extends Controller
{
    public function getUser(Request $request)
    {
        $user = $request->user();


        return response()->json([
            'status' => true,
            'data' => $request->user(),
            'profileRequired' => $user->fname ==  null || $user->lname == null ? true : false
        ]);
    }
    public function profileUpdate(Request $request)
    {

        $request->validate([
            'fname' => 'required|min:2|max:15',
            'lname' => 'required|min:2|max:15',
            'email' => 'email',
            'referCode' => 'min:9|max:9'
        ]);
        $user = User::find($request->user()->id);
        if ($user->fname == null && $user->lname == null && $user->referCode == null) {
            if (strlen($request->fname) < 4) {
                $referCode = Str::upper($request->fname . Str::password(4 - strlen($request->fname), true, false, false)) . Str::of($user->mobileNumber)->substr(5);
            } else {
                $referCode = Str::upper(Str::of($request->fname)->substr(0, 4)) . Str::of($user->mobileNumber)->substr(5);
            }
            $user->referCode = $referCode;
          
        }
        if ($request->has('referCode') && $user->refBy==null) {
          
            $mainUser = User::where('referCode',$request->referCode)->first();
            if($mainUser != null)
            $user->refBy = $mainUser->id;
        else
        return response()->json([
            'status' => false,
            'message' => 'Please Enter a Valid Refer Code',
        ]);
        }
        $user->fname = $request->fname;
        $user->lname = $request->lname;
        $user->email = $request->email;
        $user->save();
        return response()->json([
            'status' => true,
            'message' => 'profile update done',
        ]);
    }
}
