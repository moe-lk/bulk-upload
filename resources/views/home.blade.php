@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    You are logged in! If you can't access to the bulk upload menu , then you are not given permission for do this.
                    Please contact you School Coordinator and get access to your class room.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
