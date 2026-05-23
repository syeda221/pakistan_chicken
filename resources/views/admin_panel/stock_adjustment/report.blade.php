@extends('admin_panel.layout.app')
@section('content')
<div class="main-content">
<div class="main-content-inner">
<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-3 mt-2">
    <h2 class="page-title m-0">📊 Stock Adjustment Report</h2>
    <a href="{{ route('stock-adjustment.index') }}" class="btn btn-secondary">← Back</a>
</div>

{{-- FILTERS --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label fw-bold mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ $from }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <label class="form-label fw-bold mb-1">To Date</label>
                <input type="date" name="to_date" value="{{ $to }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <label class="form-label fw-bold mb-1">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="increase" {{ $type=='increase'?'selected':'' }}>➕ Increase</option>
                    <option value="decrease" {{ $type=='decrease'?'selected':'' }}>➖ Decrease</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                <button type="button" onclick="window.print()" class="btn btn-dark btn-sm no-print"><i class="fas fa-print"></i> Print</button>
                <a href="{{ route('stock-adjustment.report') }}" class="btn btn-secondary btn-sm no-print">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
@php
    $totalInc = $rows->where('type','increase')->sum('qty');
    $totalDec = $rows->where('type','decrease')->sum('qty');
    $totalRows = $rows->count();
@endphp
<div class="row g-3 mb-3 no-print">
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body py-2 text-center">
                <div class="text-success fw-bold fs-5">{{ $rows->where('type','increase')->count() }}</div>
                <div class="text-muted small">Increase Entries</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body py-2 text-center">
                <div class="text-danger fw-bold fs-5">{{ $rows->where('type','decrease')->count() }}</div>
                <div class="text-muted small">Decrease Entries</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body py-2 text-center">
                <div class="text-primary fw-bold fs-5">{{ $totalRows }}</div>
                <div class="text-muted small">Total Line Items</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-secondary">
            <div class="card-body py-2 text-center">
                <div class="fw-bold fs-5">{{ $from }} → {{ $to }}</div>
                <div class="text-muted small">Date Range</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" id="adjReportTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Ref #</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Product</th>
                        <th>Code</th>
                        <th>Qty Entered</th>
                        <th>Stock Change</th>
                        <th>By</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $row)
                    @php
                        $isKg = $row->unit_type === 'kg';
                        $qty = (float)$row->qty;
                        if ($isKg) {
                            $kg = floor($qty); $gm = round(($qty - $kg) * 1000);
                            $qtyFmt = ($kg > 0 ? $kg.'kg ' : '') . ($gm > 0 ? $gm.'g' : ($kg > 0 ? '' : '0g'));
                        } else {
                            $qtyFmt = number_format($qty, 0) . ' ' . $row->unit;
                        }
                        $sFmt = $isKg
                            ? number_format($row->qty_stock, 0) . 'g'
                            : number_format($row->qty_stock, 0) . ' ' . $row->unit;
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><strong>{{ $row->ref_no }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($row->adjustment_date)->format('d-M-Y') }}</td>
                        <td>
                            @if($row->type === 'increase')
                                <span class="badge bg-success">➕ Inc</span>
                            @else
                                <span class="badge bg-danger">➖ Dec</span>
                            @endif
                        </td>
                        <td>{{ $row->reason }}</td>
                        <td>
                            <strong>{{ $row->item_name }}</strong>
                            @if($row->size_label || $row->variant_name)
                                <br><small class="text-muted">({{ $row->size_label ?: $row->variant_name }})</small>
                            @endif
                        </td>
                        <td>{{ $row->item_code }}</td>
                        <td><strong>{{ $qtyFmt }}</strong></td>
                        <td>
                            @if($row->type === 'increase')
                                <span class="text-success fw-bold">+{{ $sFmt }}</span>
                            @else
                                <span class="text-danger fw-bold">-{{ $sFmt }}</span>
                            @endif
                        </td>
                        <td>{{ $row->user_name ?? '-' }}</td>
                        <td>{{ $row->item_note ?? $row->adj_note ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">No records found for selected period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>
</div>
</div>

<style>
@media print {
    .no-print { display:none !important; }
    .main-content { margin-left: 0 !important; }
    body { font-size: 12px; }
}
</style>
@endsection
