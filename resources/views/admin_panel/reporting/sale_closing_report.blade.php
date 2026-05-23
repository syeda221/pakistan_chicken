@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4>Sale Closing Report</h4>
                    <h6>Combined view of Sales and Expenses for daily/period closing</h6>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form id="ClosingFilterForm" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        @if(auth()->user()->hasRole('Admin'))
                        <div class="col-md-2">
                            <label class="form-label fw-bold">User / Cashier</label>
                            <select name="user_id" id="user_id" class="form-control">
                                <option value="all">All Users</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                            <input type="hidden" id="user_id" value="{{ auth()->id() }}">
                        @endif
                        <div class="col-md-2">
                            <button type="button" id="btnSearch" class="btn btn-primary w-100">
                                <i class="la la-search"></i> Search
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btnPrint" class="btn btn-dark w-100">
                                <i class="la la-print"></i> Print Closing
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body py-3">
                            <h6 class="mb-1 text-white">Total Sales (+)</h6>
                            <h3 class="mb-0 text-white" id="summarySale">Rs 0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body py-3">
                            <h6 class="mb-1 text-white">Total Expenses (-)</h6>
                            <h3 class="mb-0 text-white" id="summaryExpense">Rs 0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body py-3">
                            <h6 class="mb-1 text-white">Net Cash (Grand Total)</h6>
                            <h3 class="mb-0 text-white" id="summaryNet">Rs 0</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div id="loader" style="display:none;text-align:center;margin-bottom:10px;">
                        <div class="spinner-border" role="status"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3"><i class="la la-shopping-cart"></i> Sales Detailed</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Inv #</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="saleBody">
                                        <tr><td colspan="3" class="text-center text-muted">No data found</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3"><i class="la la-money-bill"></i> Expenses Detailed</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>EVID #</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="expenseBody">
                                        <tr><td colspan="3" class="text-center text-muted">No data found</td></tr>
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
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        fetchData();

        $('#btnSearch').on('click', fetchData);

        $('#btnPrint').on('click', function() {
            let start = $('#start_date').val();
            let end = $('#end_date').val();
            let user = $('#user_id').val();
            window.open("{{ route('report.sale_closing.print') }}?start_date=" + start + "&end_date=" + end + "&user_id=" + user, '_blank');
        });

        function fetchData() {
            let start = $('#start_date').val();
            let end = $('#end_date').val();
            let user = $('#user_id').val();

            $("#loader").show();
            $.ajax({
                url: "{{ route('report.sale_closing.fetch') }}",
                type: "GET",
                data: { start_date: start, end_date: end, user_id: user },
                success: function(res) {
                    $("#loader").hide();
                    
                    // Summary
                    $('#summarySale').text('Rs ' + res.total_sale.toLocaleString());
                    $('#summaryExpense').text('Rs ' + res.total_expense.toLocaleString());
                    $('#summaryNet').text('Rs ' + res.net_amount.toLocaleString());

                    // Sales Table
                    let saleHtml = "";
                    if (res.sales.length > 0) {
                        res.sales.forEach(s => {
                            let d = new Date(s.created_at);
                            let dateStr = d.getDate() + '-' + (d.getMonth()+1) + '-' + d.getFullYear();
                            saleHtml += `<tr>
                                <td>${dateStr}</td>
                                <td>${s.invoice_no}</td>
                                <td class="text-end">${parseFloat(s.total_net).toLocaleString()}</td>
                            </tr>`;
                        });
                    } else {
                        saleHtml = '<tr><td colspan="3" class="text-center">No sales found</td></tr>';
                    }
                    $('#saleBody').html(saleHtml);

                    // Expenses Table
                    let expHtml = "";
                    if (res.expenses.length > 0) {
                        res.expenses.forEach(e => {
                            expHtml += `<tr>
                                <td>${e.date}</td>
                                <td>${e.evid}</td>
                                <td class="text-end">${parseFloat(e.total_amount).toLocaleString()}</td>
                            </tr>`;
                        });
                    } else {
                        expHtml = '<tr><td colspan="3" class="text-center">No expenses found</td></tr>';
                    }
                    $('#expenseBody').html(expHtml);
                },
                error: function() {
                    $("#loader").hide();
                    alert('Error fetching data.');
                }
            });
        }
    });
</script>
@endsection
