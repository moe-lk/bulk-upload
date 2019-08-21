<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;


class FilesController extends Controller
{

    public function index()
    {
        return Datatables::of(Upload::with(['classRoom'])->where('security_user_id','=',Auth::user()->id))
            ->editColumn('is_processed', function ($data) {
                if ($data->is_processed === 1) {
                    return "Success";
                }elseif ($data->is_processed === 2){
                    return "Failed";
                }elseif($data->is_processed == 3){
                    return "Processing  ";
                }else{
                    return 'Pending';
                };

            })
            ->editColumn('is_email_sent', function ($data) {
                if ($data->is_email_sent === 1) {
                    return "Success";
                }elseif($data->is_email_sent === 2 ){
                    return 'Failed';
                }else{
                    return 'Sending';
                };

            })
            ->editColumn('filename', function ($data) {
                if(env('APP_ENV') == 'local'){
                     return '<a href="/download_file/'.$data->filename.'">'.$data->filename.'</a>';
                    
                }else{
                    return '<a href="/bulk-upload/download_file/'.$data->filename.'">'.$data->filename.'</a>';
                }

            })
             ->editColumn('error', function ($data) {
                if(env('APP_ENV') == 'local'){
                     return '<a href="/download/'.$data->filename.'">'.$data->filename.'</a>';
                    
                }else{
                    return '<a href="/bulk-upload/download/'.$data->filename.'">'.$data->filename.'</a>';
                }

            })
            ->rawColumns(['filename','error'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('displaydata');
    }
}
