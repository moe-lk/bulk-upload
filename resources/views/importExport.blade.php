@extends('layouts.app')
@section('content')
    <div class="container">
        <div class="panel panel-default">
          <div class="panel-heading">
          <h1>NEMIS - SIS Bulk data import</h1>
          </div>
          <div class="panel-body">
{{--              <iframe width="1200" height="450" frameborder="0" scrolling="no" src="https://onedrive.live.com/embed?resid=367F7CD71188D7DA%211012&authkey=%21AL4A-jLv8V-fhEI&em=2&wdAllowInteractivity=False&AllowTyping=True&wdHideHeaders=True&wdDownloadButton=True&wdInConfigurator=True"></iframe>--}}


              {{--            <a href="{{ url('downloadExcel') }}"><button class="btn btn-success">Download Excel xls</button></a>--}}
            <a href="https://onedrive.live.com/download?resid=367F7CD71188D7DA%211012&authkey=%21AL4A-jLv8V-fhEI&em=2&wdAllowInteractivity=False&wdHideGridlines=True&wdHideHeaders=True&wdDownloadButton=True&wdInConfigurator=True" target="_blank"><button class="btn btn-success"> Download Template</button></a>
            <form style="border: 4px solid #a1a1a1;margin-top: 15px;padding: 10px;" action="{{ url('upload') }}" class="form-horizontal" method="post" enctype="multipart/form-data">
                @csrf
 
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
 
                @if (Session::has('success'))
                    <div class="alert alert-success">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
                        <p>{{ Session::get('success') }}</p>
                    </div>
                @endif
                <select class="form-control" name="class">

                    @foreach($classes as $item)
                        <option value="{{$item->id}}">{{$item->name}}</option>
                    @endforeach
                </select>
                <input type="file" name="import_file" required />
                <button class="btn btn-primary">Import File</button>
            </form>
 
          </div>
        </div>
    </div>
@endsection