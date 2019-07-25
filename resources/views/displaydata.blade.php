@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="panel panel-default">
        <div class="container">
            <table class="table table-bordered" id="table">
                <thead>
                <tr>
                    <th>Id</th>
                    <th>File Name</th>
                    <th>Class</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Status</th>
                </tr>
                </thead>
            </table>
        </div>
        <script>
            $(function() {
                $('#table').DataTable({
                    processing: false,
                    serverSide: true,
                    ajax: '{{ url('index') }}',
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'filename', name: 'filename' },
                        { data: 'class_room.name', name: 'Class' },
                        { data: 'created_at' , name: 'Uploaded'},
                        { data: 'updated_at' , name: 'updated_at'},
                        { data: 'is_processed', name: 'Status'}
                    ]
                });
            });
        </script>
        </div>
    </div>
@endsection