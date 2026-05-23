@extends('admin_panel.layout.app')
@section('content')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="main-content">
    <div class="container-fluid">
        <div class="card-header mt-2 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Expense Vouchers</h4>
            <a class="btn btn-primary" href="{{ route('expense-vochers') }}">Add Expense Voucher</a>
        </div>
        <div class="card shadow mt-4">
            <div class="card-body">
                <form action="{{ route('all-expense-vochers') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    @if(auth()->user()->hasRole('Admin'))
                    <div class="col-md-3">
                        <label class="form-label fw-bold">User / Cashier</label>
                        <select name="user_id" class="form-control">
                            <option value="all">All Users</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <a href="{{ route('all-expense-vochers') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow mt-4 mb-5">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="productTable" class="table table-striped table-bordered align-middle nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Voucher No</th>
                                <th>Account Head</th>
                                <th>Account</th>
                                <th style="min-width:260px;">Remarks</th>
                                <th>Total Amount</th>
                                <th>Date</th>
                                <th>User</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vouchers as $voucher)
                            <tr>
                                <td>{{ $voucher->id }}</td>
                                <td>{{ $voucher->evid }}</td>
                                <td>{{ $voucher->type_name }}</td>
                                <td>{{ $voucher->party_name }}</td>
                                <td>
                                    @php
                                    $remarks = is_array($voucher->remarks)
                                    ? $voucher->remarks
                                    : json_decode($voucher->remarks, true) ?? [];

                                    $amounts = is_array($voucher->amount)
                                    ? $voucher->amount
                                    : json_decode($voucher->amount, true) ?? [];
                                    @endphp

                                    @foreach ($remarks as $i => $remark)
                                    <div class="d-flex justify-content-between align-items-center mb-1 p-2 rounded bg-light border">
                                        <span class="text-dark fw-medium">
                                            {{ $remark }}
                                        </span>
                                        <span class="badge bg-primary">
                                            Rs {{ number_format($amounts[$i] ?? 0, 2) }}
                                        </span>
                                    </div>
                                    @endforeach
                                </td>
                                <td class="text-end fw-bold text-success">
                                    Rs {{ number_format($voucher->total_amount, 2) }}
                                </td>
                                <td>{{ \Carbon\Carbon::parse($voucher->date)->format('d-m-Y') }}</td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $voucher->user->name ?? 'Admin' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('expenseVoucher.print', $voucher->id) }}"
                                        target="_blank"
                                        class="btn btn-sm btn-danger">
                                        <i class="bi bi-printer"></i>
                                    </a>
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

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#productTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            order: [
                [1, 'desc'] // ✅ Invoice No / ID column ke basis pe latest pehle
            ],
            columnDefs: [{
                    targets: 0,
                    orderable: false
                } // S.No column sortable nahi
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search Sale..."
            }
        });
    });
</script>

@endsection