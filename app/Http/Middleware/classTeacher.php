<?php

namespace App\Http\Middleware;

use Closure;

class classTeacher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->user()->super_admin == 1){
            return $next($request);
        }elseif ($request->user() && (!($request->user()->permissions->isEmpty()))  && $request->user()->permissions[0]->roles &&  $request->user()->permissions[0]->roles->code === 'HOMEROOM_TEACHER') {
            return $next($request);
        }elseif($request->user() && (!($request->user()->principal->isEmpty()))  && $request->user()->principal[0]->roles &&  $request->user()->principal[0]->roles->code === 'PRINCIPAL'){
            return $next($request);
        }elseif($request->user() && (!($request->user()->zonal_cordinator->isEmpty()))  && $request->user()->zonal_cordinator[0]->roles &&  $request->user()->zonal_cordinator[0]->roles->code === 'PRINCIPAL'){
            return $next($request);
        }
        return redirect('/login')->with('status', 'Your dont have access for upload data. Please get assign your to the class and try');
    }
}
