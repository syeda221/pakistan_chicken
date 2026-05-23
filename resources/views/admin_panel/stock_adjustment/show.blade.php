@extends('admin_panel.layout.app')
@section('content')
<div class="main-content">
<div class="main-content-inner">
<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-3 mt-2">
    <h2 class="page-title m-0">📦 Adjustment Detail — {{ $adjustment->ref_no }}</h2>
    <div>
        <a href="{{ route('stock-adjustment.index') }}" class="btn btn-secondary">← Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>Ref No</th><td><strong>{{ $adjustment->ref_no }}</strong></td></tr>
                    <tr><th>Date</th><td>{{ \Carbon\Carbon::parse($adjustment->adjustment_date)->format('d-M-Y') }}</td></tr>
                    <tr><th>Type</th><td>
                        @if($adjustment->type === 'increase')
                            <span class="badge bg-success fs-6">➕ Increase</span>
                        @else
                            <span class="badge bg-danger fs-6">➖ Decrease</span>
                        @endif
                    </td></tr>
                    <tr><th>Reason</th><td>{{ $adjustment->reason }}</td></tr>
                    <tr><th>By</th><td>{{ optional($adjustment->user)->name ?? 'System' }}</td></tr>
                    <tr><th>Notes</th><td>{{ $adjustment->notes ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Code</th>
                            <th>Unit</th>
                            <th>Qty Entered</th>
                            <th>Stock Impact</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adjustment->items as $i => $item)
                        @php
                            $isKg = optional($item->product)->unit_type === 'kg';
                            $qty = (float)$item->qty;
                            if ($isKg) {
                                $kg = floor($qty);
                                $gm = round(($qty - $kg) * 1000);
                                $qtyFmt = ($kg > 0 ? $kg.'kg ' : '') . ($gm > 0 ? $gm.'g' : ($kg > 0 ? '' : '0g'));
                            } else {
                                $qtyFmt = number_format($qty, 0) . ' ' . $item->unit;
                            }
                            $stockFmt = $isKg
                                ? number_format($item->qty_stock, 0) . ' g'
                                : number_format($item->qty_stock, 0) . ' ' . $item->unit;
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ optional($item->product)->item_name }}</strong>
                                @if($item->variant)
                                    <br><small class="text-muted">({{ $item->variant->size_label ?: $item->variant->variant_name }})</small>
                                @endif
                            </td>
                            <td>{{ optional($item->product)->item_code }}</td>
                            <td>{{ $item->unit }}</td>
                            <td><strong>{{ $qtyFmt }}</strong></td>
                            <td>
                                @if($adjustment->type === 'increase')
                                    <span class="text-success fw-bold">+{{ $stockFmt }}</span>
                                @else
                                    <span class="text-danger fw-bold">-{{ $stockFmt }}</span>
                                @endif
                            </td>
                            <td>{{ $item->notes ?? '-' }}</td>
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
@endsection
