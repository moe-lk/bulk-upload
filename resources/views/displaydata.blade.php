<html lang="en">
<head>
    <title>Uploaded files lists</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
    <link  href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
</head>
<body>
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
            processing: true,
            serverSide: true,
            ajax: '{{ url('index') }}',
            columns: [
                { data: 'id', name: 'id' },
                { data: 'filename', name: 'filename' },
                { data: 'class_room.name', name: 'Class' },
                { data: 'created_at' , name: 'Uploaded'},
                { data: 'updated_at' , name: 'Processed'},
                { data: 'is_processed', name: 'Status'}
            ]
        });
    });
</script>
</body>
</html>