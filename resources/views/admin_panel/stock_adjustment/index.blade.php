@extends('admin_panel.layout.app')
@section('content')
<div class="main-content">
<div class="main-content-inner">
<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-3 mt-2">
    <h2 class="page-title m-0">📦 Stock Adjustments</h2>
    <div>
        <a href="{{ route('stock-adjustment.report') }}" class="btn btn-outline-secondary me-2"><i class="fas fa-chart-bar"></i> Report</a>
        <a href="{{ route('stock-adjustment.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Adjustment</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- FILTERS --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label fw-bold mb-1">From</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <label class="form-label fw-bold mb-1">To</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <label class="form-label fw-bold mb-1">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="increase" {{ request('type')=='increase' ? 'selected':'' }}>➕ Increase</option>
                    <option value="decrease" {{ request('type')=='decrease' ? 'selected':'' }}>➖ Decrease</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
                <a href="{{ route('stock-adjustment.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Ref #</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Items</th>
                        <th>By</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adj)
                    <tr>
                        <td><strong>{{ $adj->ref_no }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($adj->adjustment_date)->format('d-M-Y') }}</td>
                        <td>
                            @if($adj->type === 'increase')
                                <span class="badge bg-success">➕ Increase</span>
                            @else
                                <span class="badge bg-danger">➖ Decrease</span>
                            @endif
                        </td>
                        <td>{{ $adj->reason }}</td>
                        <td>
                            @foreach($adj->items as $item)
                                @php
                                    $isKg = optional($item->product)->unit_type === 'kg';
                                    $qty = (float)$item->qty;
                                    if ($isKg) {
                                        $kg = floor($qty); $gm = round(($qty - $kg) * 1000);
                                        $qtyFmt = ($kg > 0 ? $kg.'kg ' : '') . ($gm > 0 ? $gm.'g' : ($kg > 0 ? '' : '0g'));
                                    } else {
                                        $qtyFmt = number_format($qty, 0) . ' ' . $item->unit;
                                    }
                                @endphp
                                <div class="text-nowrap mb-1" style="font-size: 11px;">
                                    <strong>{{ optional($item->product)->item_name }}</strong> : <span class="badge bg-light text-dark border">{{ $qtyFmt }}</span>
                                    @if($item->variant) <small class="text-muted d-block">({{ $item->variant->size_label ?: $item->variant->variant_name }})</small> @endif
                                </div>
                            @endforeach
                        </td>
                        <td>{{ optional($adj->user)->name ?? 'System' }}</td>
                        <td>{{ Str::limit($adj->notes, 40) }}</td>
                        <td class="text-center">
                            <a href="{{ route('stock-adjustment.show', $adj->id) }}" class="btn btn-info btn-sm" title="View Detail"><i class="fas fa-eye"></i> View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No adjustments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2">{{ $adjustments->links() }}</div>
    </div>
</div>

</div>
</div>
</div>
@endsection
