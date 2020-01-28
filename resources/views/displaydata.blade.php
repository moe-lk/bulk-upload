@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="panel panel-default">
            <div class="container">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="table">
                        <thead class="thead-dark">
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
                            <th>Actions</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(function () {
            $('#table').DataTable({
                processing: false,
                serverSide: true,
                ajax: '{{ url('index') }}',
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'filename', name: 'filename'},
                    {data: 'error', name: 'error'},
                    {data: 'insert', name: 'Insert Status'},
                    {data: 'update', name: 'Update Status'},
                    {data: 'is_processed', name: 'Overall'},
                    {data: 'created_at', name: 'Uploaded at'},
                    {data: 'updated_at', name: 'Last update'},
                    {data: 'is_email_sent', name: 'Email Status'},
                    {data: 'actions', name: 'actions'}
                ]
            });
        });

        function updateProcess($id, $action) {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: '/updateUnprocessedFiles/'+$id+'/'+$action,
                type: 'POST',
                data: {_token: CSRF_TOKEN, id: $id, action: $action},
                dataType: 'JSON',
                success: function () {
                    console.log("success");
                },
                error: function() {
                    console.log("error")
                }
            })
        }

    </script>
@endsection
