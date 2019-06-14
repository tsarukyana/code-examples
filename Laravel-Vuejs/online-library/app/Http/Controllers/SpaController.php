<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class SpaController extends Controller
{
    public function index()
    {
        return view('spa');
    }

    public function userInfo(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider()
    {
        return \Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback()
    {
        try {
            $user = \Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/auth/login');
        }
        $token = '';
        // check if they're an existing user
        $existingUser = User::where('email', $user->email)->first();
        if($existingUser){
            $token = $existingUser->createToken('GoogleLogin')->accessToken;
        } else {
            // create a new user
            $newUser                  = new User;
            $newUser->name            = $user->name;
            $newUser->email           = $user->email;
            $newUser->password        = bcrypt(str_random(8));
            $newUser->google_id       = $user->id;
            $newUser->avatar          = $user->avatar;
            $newUser->avatar_original = $user->avatar_original;
            if ($newUser->save()) {
                $token = $newUser->createToken('GoogleLogin')->accessToken;
            }
        }
        return redirect()->to(env('APP_URL') . '/?token='. $token);
    }
}
