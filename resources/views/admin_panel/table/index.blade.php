@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Tables</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tableModal"
                            id="reset">Create Table</button>
                    </div>
                    <div class="border mt-1 shadow rounded " style="background-color: white;">
                        <div class="col-lg-12 m-auto">
                            <div class="table-responsive mt-5 mb-5 ">
                                <table id="default-datatable" class="table">
                                    <thead class="text-center">
                                        <tr>
                                            <th class="text-center">Id</th>
                                            <th class="text-center">Table Name</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center">
                                        @foreach ($tables as $table)
                                        <tr>
                                            <td class="id">{{ $table->id }}</td>
                                            <td class="name">{{ $table->table_name }}</td>
                                            <td class="status">
                                                <span class="badge @if($table->status == 'available') bg-success @elseif($table->status == 'occupied') bg-danger @else bg-warning @endif">
                                                    {{ ucfirst($table->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-primary btn-sm edit-btn">
                                                    Edit
                                                </button>
                                                <button class="btn btn-danger btn-sm delete-btn"
                                                    data-url="{{ route('table.delete', $table->id) }}"
                                                    data-msg="Are you sure you want to delete this table?"
                                                    data-method="get"
                                                    onclick="logoutAndDeleteFunction(this)">
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="tableModal" tabindex="-1" aria-labelledby="tableModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tableModalLabel">Add Table</h5>
            </div>
            <div class="modal-body">
                <form class="myform" action="{{ route('table.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id" />
                    <div class="mb-3">
                        <label for="table_name" class="form-label">Table Name</label>
                        <input type="text" name="table_name" class="form-control" id="table_name" required />
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status_select" class="form-control">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="reserved">Reserved</option>
                        </select>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <input type="submit" class="btn btn-primary save-btn">
            </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).on('submit', '.myform', function(e) {
        e.preventDefault();
        var formdata = new FormData(this);
        var url = $(this).attr('action');
        var method = $(this).attr('method');
        $(this).find(':submit').attr('disabled', true);
        myAjax(url, formdata, method);
    });

    $(document).on('click', '.edit-btn', function() {
        var tr = $(this).closest("tr");
        var id = tr.find(".id").text();
        var name = tr.find(".name").text();
        var status = tr.find(".status").text().trim().toLowerCase();
        
        $('#edit_id').val(id);
        $('#table_name').val(name);
        $('#status_select').val(status);
        $("#tableModal").modal("show");
    });

    $('#reset').click(function() {
        $('#edit_id').val('');
        $('#table_name').val('');
        $('#status_select').val('available');
    });
</script>
<script>
    $(document).ready(function() {
        $('#default-datatable').DataTable({
            "pageLength": 10,
            "lengthMenu": [5, 10, 25, 50, 100],
            "order": [
                [0, 'desc']
            ],
            "language": {
                "search": "Search Table:",
                "lengthMenu": "Show _MENU_ entries"
            }
        });
    });
</script>
@endsection
