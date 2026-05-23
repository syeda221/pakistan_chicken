@extends('admin_panel.layout.app')
@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="body-wrapper">
                    <div class="bodywrapper__inner">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="page-title m-0">🏭 Edit Production Entry — {{ $entry->entry_no }}</h2>
                            <a href="{{ route('production.index') }}" class="btn btn-danger">Back</a>
                        </div>

                        @if($errors->any())
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $err)
                                <p class="mb-0">{{ $err }}</p>
                            @endforeach
                        </div>
                        @endif
                        
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('production.update', $entry->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label>Production Date</label>
                                            <input type="date" name="production_date" value="{{ $entry->production_date }}" class="form-control" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Batch / Entry #</label>
                                            <input type="text" name="entry_no" value="{{ $entry->entry_no }}" class="form-control" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Source (Kitchen/Warehouse)</label>
                                            <select name="source" class="form-control">
                                                <option value="kitchen" {{ $entry->source == 'kitchen' ? 'selected' : '' }}>Main Kitchen</option>
                                                <option value="warehouse" {{ $entry->source == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12">
                                            <label>Notes</label>
                                            <input type="text" name="notes" class="form-control" placeholder="Optional notes for this batch..." value="{{ $entry->notes }}">
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th width="40%">Product</th>
                                                    <th>Code</th>
                                                    <th>Unit</th>
                                                    <th>Entered Qty (KG/Pc)</th>
                                                    <th>Note</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="productionItems">
                                                @foreach($items as $item)
                                                <tr>
                                                    <td>
                                                        <select name="product_id[]" class="form-control select2 product-select" required>
                                                            <option value="">Search Product...</option>
                                                            @foreach($products as $p)
                                                                <option value="{{ $p->id }}" 
                                                                    data-code="{{ $p->item_code }}"
                                                                    data-unit="{{ $p->unit_type === 'kg' ? 'KG' : ($p->unit->name ?? 'Pc') }}"
                                                                    data-is-gram="{{ $p->unit_type === 'kg' || str_contains(strtolower($p->item_name), 'gram') || str_contains(strtolower($p->unit->name ?? ''), 'gram') ? '1' : '0' }}"
                                                                    {{ $item->product_id == $p->id ? 'selected' : '' }}>
                                                                    {{ $p->item_code }} - {{ $p->item_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        
                                                        <div class="variant-container mt-2" style="display:none;">
                                                            <select name="variant_id[]" class="form-control variant-select">
                                                                <option value="">Select Size (Optional)</option>
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td><input type="text" class="form-control code-display" value="{{ $item->item_code }}" readonly></td>
                                                    <td><input type="text" class="form-control unit-display" value="{{ $item->unit }}" readonly></td>
                                                    <td>
                                                        <input type="number" step="0.001" name="qty[]" class="form-control qty-input" required min="0.001" value="{{ $item->qty_entered }}">
                                                        <small class="text-muted conversion-display"></small>
                                                    </td>
                                                    <td><input type="text" name="item_note[]" class="form-control" value="{{ $item->notes }}"></td>
                                                    <td><button type="button" class="btn btn-danger remove-row"><i class="fas fa-trash"></i></button></td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <button type="button" class="btn btn-success mt-2" id="addRow">+ Add More</button>
                                    <hr>
                                    <button type="submit" class="btn btn-primary btn-lg px-5">💾 Update Entry</button>
                                </form>
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
    $('.select2').select2({ width: '100%', placeholder: 'Select product...' });

    $(document).on('change', '.product-select', function() {
        let opt = $(this).find(':selected');
        let row = $(this).closest('tr');
        row.find('.code-display').val(opt.data('code'));
        row.find('.unit-display').val(opt.data('unit'));
        updateConversion(row);
        
        let productId = $(this).val();
        let variantSelect = row.find('.variant-select');
        let variantContainer = row.find('.variant-container');
        let isGram = opt.data('is-gram') == '1';
        
        if (productId && !isGram) {
            variantContainer.show();
            variantSelect.html('<option value="">Loading sizes...</option>');
            $.ajax({
                url: '/pos/product-variants/' + productId,
                type: 'GET',
                success: function(res) {
                    variantSelect.html('<option value="">Select Size (Optional)</option>');
                    if (res.variants && res.variants.length > 0) {
                        res.variants.forEach(function(v) {
                            variantSelect.append('<option value="' + v.id + '">' + v.size_label + '</option>');
                        });
                    } else {
                        variantContainer.hide();
                        variantSelect.html('<option value="">No Sizes</option>');
                    }
                },
                error: function() {
                    variantSelect.html('<option value="">Select Size (Optional)</option>');
                }
            });
        } else {
            variantContainer.hide();
            variantSelect.html('<option value="">Select Size (Optional)</option>');
        }
    });

    $(document).on('input', '.qty-input', function() {
        updateConversion($(this).closest('tr'));
    });

    function updateConversion(row) {
        let qty = parseFloat(row.find('.qty-input').val()) || 0;
        let isGram = row.find('.product-select option:selected').data('is-gram') == '1';
        if (isGram && qty > 0) {
            let grams = qty * 1000;
            row.find('.conversion-display').text('(' + grams.toLocaleString() + ' grams to stock)');
        } else {
            row.find('.conversion-display').text('');
        }
    }

    // Run conversion on load for existing items
    $('#productionItems tr').each(function() {
        updateConversion($(this));
    });

    $('#addRow').click(function() {
        let newRow = $('#productionItems tr:first').clone();
        newRow.find('input').val('');
        newRow.find('.conversion-display').text('');
        newRow.find('.variant-container').hide();
        newRow.find('.variant-select').html('<option value="">Select Size (Optional)</option>');
        newRow.find('.select2-container').remove();
        $('#productionItems').append(newRow);
        newRow.find('.select2').select2({ width: '100%' });
    });

    $(document).on('click', '.remove-row', function() {
        if ($('#productionItems tr').length > 1) $(this).closest('tr').remove();
    });
});
</script>
@endsection
