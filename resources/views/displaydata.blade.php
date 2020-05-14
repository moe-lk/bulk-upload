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
            $.fn.dataTable.ext.errMode = 'throw';
            $('#table').DataTable({
                processing: false,
                serverSide: true,
                ajax: '{{ url('index') }}',
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'filename', name: 'filename'},
                    {data: 'error', name: 'error'},
                    {data: 'insert', name: 'insert'},
                    {data: 'update', name: 'update'},
                    {data: 'is_processed', name: 'is_processed'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'updated_at', name: 'updated_at'},
                    {data: 'is_email_sent', name: 'is_email_sent'},
                    {data: 'actions', name: 'actions'}
                ]
            });
        });


        function updateProcess($id, $action) {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: 'updateQueueWithUnprocessedFiles/'+$id+'/'+$action,
                type: 'POST',
                data: {_token: CSRF_TOKEN, id: $id, action: $action},
                dataType: 'JSON',
                success: function () {
                    console.log("success");
                },
                error: function() {
                    console.log("error")
                }
            }).done(location.reload())
        }

    </script>
@endsection
