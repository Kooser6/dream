<?php

namespace App\Http\Controllers\Auth;

use Socialite;
use App\User;
use App\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers {
        logout as performLogout;
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Redirect the user to the login page after logout.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->performLogout($request);
        return redirect('/login');
    }

    /**
     * Redirect the user to the social provider authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from social provider.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(string $provider)
    {
        $user = Socialite::driver($provider)->user();
        $acct = DB::table('social_logins')->where('provider_name', $provider)->where('provider_id', $user->getId())->first();
        $mail = DB::table('users')->where('email', $user->getEmail())->first();
        if ($acct) {
            Auth::loginUsingId($acct->user_id);
            return redirect()->to($this->redirectTo);
        } elseif ($mail) {
            DB::table('social_logins')->insert(
                ['user_id' => $mail->id, 'provider_name' => $provider, 'provider_id' => $user->getId()]
            );
            $veri = DB::table('users')->where('email_verified_at', null)->first();
            if ($veri) {
                DB::table('users')->where('id', $nusr->id)->update(['email_verified_at' => now()]);
            }
            Auth::loginUsingId($mail->id);
            return redirect()->to($this->redirectTo);
        } else {
            $role = Role::where('name', 'user')->first();
            $nusr = User::create([
                'name'     => $user->getName(),
                'email'    => $user->getEmail(),
                'password' => Hash::make(Str::random(40)),
            ]);
            $nusr->roles()->attach($role);
            DB::table('social_logins')->insert(
                ['user_id' => $nusr->id, 'provider_name' => $provider, 'provider_id' => $user->getId()]
            );
            DB::table('users')->where('id', $nusr->id)->update(['email_verified_at' => now()]);
            Auth::loginUsingId($user->id);
            return redirect()->to($this->redirectTo);
        }
    }
}
