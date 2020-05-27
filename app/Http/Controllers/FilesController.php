<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;


class FilesController extends Controller
{

    public function index()
    {
        return Datatables::of(Upload::with(['classRoom'])->where('security_user_id','=',Auth::user()->id))
            ->editColumn('is_processed', function ($data) {

                $nowTime = \Carbon\Carbon::now();
                $to = \Carbon\Carbon::createFromFormat('Y-m-d H:s:i', $nowTime);
                $from = \Carbon\Carbon::createFromFormat('Y-m-d H:s:i', $data->updated_at);
                $diff_in_hours = $to->diffInHours($from);

                if($diff_in_hours >= 2 && $data->is_processed == 3){
                    return "Terminated";
                } elseif ($data->is_processed === 1) {
                    return "Success";
                } elseif ($data->is_processed === 2){
                    return "Failed";
                } elseif($diff_in_hours < 2 && $data->is_processed == 3){
                    return "Processing";
                } elseif ($data->is_processed == 4){
                    return "Process Paused";
                } else{
                    return 'Pending';
                };

            })
            ->editColumn('is_email_sent', function ($data) {
                if ($data->is_email_sent === 1) {
                    return "Success";
                } elseif($data->is_email_sent === 2 ){
                    return 'Failed';
                } else{
                    return 'Pending';
                };

            })
            ->editColumn('update', function ($data) {
                if ($data->update === 0) {
                    return "No Processes";
                } elseif($data->update === 1 ){
                    return 'Success';
                } elseif($data->update === 3 ){
                    return 'Partial Success';
                } else{
                    return 'Failed';
                };

            })
            ->editColumn('insert', function ($data) {
                if ($data->insert === 0) {
                    return "No Processes";
                } elseif($data->insert === 1 ){
                    return 'Success';
                } elseif($data->insert === 3 ){
                    return 'Partial Success';
                } else{
                    return 'Failed';
                };

            })
            ->editColumn('filename', function ($data) {
                return '<a href='.env('APP_URL').'/download_file/'.$data->filename.'>'.substr($data->classRoom->name, 0, 10).'</a>';
            })
                ->editColumn('error', function ($data) {
                return '<a href='.env('APP_URL').'/download/'.$data->filename.'>'.substr($data->classRoom->name, 0, 10).'</a>';
            })->editColumn('actions', function ($data) {

                $nowTime = \Carbon\Carbon::now();
                $to = \Carbon\Carbon::createFromFormat('Y-m-d H:s:i', $nowTime);
                $from = \Carbon\Carbon::createFromFormat('Y-m-d H:s:i', $data->updated_at);
                $diff_in_hours = $to->diffInHours($from);

                if($diff_in_hours >= 2 && $data->is_processed == 3){
                    return '<button onclick="updateProcess('.($data->id).',100)" class="btn btn-primary text-uppercase">reprocess</button>';
                } elseif ($data->is_processed == 1){
                    return '<div><h6>Processing <span class="badge badge-success text-uppercase">Successful</span></h6></div>';
                } elseif ($data->is_processed == 2){
                    return '<div><h6>Processing <span class="badge badge-danger text-uppercase">Failed</span></h6></div>';
                } elseif ($data->is_processed == 0){
                    return '<button onclick="updateProcess('.($data->id).',200)" class="btn btn-block btn-warning text-uppercase">pause</button>';
                } elseif ($data->is_processed == 4){
                    return '<button onclick="updateProcess('.($data->id).',100)" class="btn btn-block btn-success text-uppercase">resume</button>';
                }
            })
            ->rawColumns(['filename','error','actions'])
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
