@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="panel panel-default">
            <div class="container">
                <table class="table table-bordered" id="table">
                    <thead>
                    <tr>
                        <th>Id</th>
                        <th>Original file</th>
                        <th>Error file</th>
                        <th>Insert Status</th>
                        <th>Update Status</th>
                        <th>Overall</th>
                        <th>Uploaded at</th>
                        <th>Last update</th>
                        <th>Email Status</th>
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
                            { data: 'error', name: 'error' },
                            { data: 'insert' , name: 'Insert Status'},
                            { data: 'update' , name: 'Update Status'},
                            { data: 'is_processed', name: 'Overall'},
                            { data: 'created_at' , name: 'Uploaded at'},
                            { data: 'updated_at' , name: 'Last update'},
                            { data: 'is_email_sent' , name: 'Email Status'}
                        ]
                    });
                });
            </script>
        </div>
    </div>
@endsection
