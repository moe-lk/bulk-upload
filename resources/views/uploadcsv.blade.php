@extends('layouts.app')

@section('content')
    <!-- Message -->
    @if(Session::has('message'))
        <p>{{ Session::get('message') }}</p>
    @endif
    <div class="container text-center">
        <h1>Total student count: <span class="badge badge-success">{{$studentCount=\DB::table('examination_students')->count()}}</span></h1>
        <!-- Upload Form -->
        @if((Auth::user()->username)=='admin')
            <h3>Please rename the file as <span class="badge badge-danger"> exams_students.csv</span>  before uploading</h3>
            <form class="form" method='post' action='/uploadFile' enctype='multipart/form-data'>
                {{ csrf_field() }}
                <hr>
                <input type='file' name='file'>
                <input type='submit' name='submit' value='Import' class="btn btn-active">
            </form>
        @else
            <div class="h1">Access denied. Log in as admin!</div>
        @endif
    </div>
@endsection
