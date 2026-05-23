@extends('admin_panel.layout.app')
@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="body-wrapper">
                    <div class="bodywrapper__inner">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="page-title m-0">🏭 Production History</h2>
                            <a href="{{ route('production.create') }}" class="btn btn-primary">+ New Production Entry</a>
                        </div>

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="datatable" class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Batch #</th>
                                                <th>Date</th>
                                                <th>Source</th>
                                                <th>Items</th>
                                                <th>Notes</th>
                                                <th>Created By</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($entries as $e)
                                            <tr>
                                                <td><strong>{{ $e->entry_no }}</strong></td>
                                                <td>{{ \Carbon\Carbon::parse($e->production_date)->format('d-M-Y') }}</td>
                                                <td><span class="badge bg-info text-capitalize">{{ $e->source }}</span></td>
                                                <td>
                                                    <div title="{{ $e->product_details }}">
                                                        {{ \Illuminate\Support\Str::limit($e->product_details, 60) }}
                                                        <br>
                                                        <small class="text-muted">({{ $e->items_count }} items)</small>
                                                    </div>
                                                </td>
                                                <td>{{ $e->notes ?? '-' }}</td>
                                                <td>{{ $e->user_name ?? 'System' }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="{{ route('production.edit', $e->id) }}" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                                        <a href="{{ route('production.gatepass', $e->id) }}" class="btn btn-dark" target="_blank"><i class="fas fa-print"></i> Gatepass</a>
                                                    </div>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">No production entries found.</td>
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
        </div>
    </div>
</div>
@endsection
