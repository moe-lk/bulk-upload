<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrafanaOAuth;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

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



    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * @var string
     */
    protected $username = 'username';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->username = $this->findUsername();
    }

    /**
     * @return string
     */
    public function findUsername(){
        $login = request()->input('username');

        $fieldType  = filter_var($login,FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        request()->merge([$fieldType => $login]);

        return $fieldType;
    }

    // public function authenticated(Request $request){
    //         $state = $request->input('state');
    //         $request_uri = 'http://localhost:3000/login/generic_oauth' ;//$request->input('redirect_ur');
    //         $request_type = $request->input('response_type');
    //         $url = "http://localhost:3000/login/generic_oauth?state={$state}&code=cc536d98d27750394a87ab9d057016e636a8ac31";
    //         return redirect($url);
    //     // $response_type = Session::get('response_type');
    //     // $request_uri = Session::get('request_uri');
    //     // $state = Session::get('state');
    //     // if($response_type == 'code'){
    //     //     $url = "{$request_uri}?state={$state}&code=cc536d98d27750394a87ab9d057016e636a8ac31";
    //     //     // header("Location: {$url}");
    //     //     return redirect($url);
    //     // }else{
    //     //     $this->grafana = new GrafanaOAuth();
    //     //     $this->grafana->auth(request());
    //     //     $url = "{$request_uri}?state={$state}&code=cc536d98d27750394a87ab9d057016e636a8ac31";
    //     //     // header("Location: {$url}");
    //     //     return redirect($url);
    //     // }
    // }   

    /**
     * @return string
     */
    public function username(){
        return $this->username;
    }
}

