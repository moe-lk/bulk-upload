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
                    <th>Created</th>
                    <th>Updated</th>
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
                        { data: 'insert', name: 'insert' },
                        { data: 'update', name: 'update' },
                        { data: 'updated_at' , name: 'updated_at'},
                        { data: 'is_processed', name: 'Status'},
                        { data: 'is_email_sent' , name: 'Email Status'}
                    ]
                });
            });
        </script>
        </div>
    </div>
@endsection
