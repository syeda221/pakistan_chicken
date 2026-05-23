<!-- meta tags and other links -->

@extends('admin_panel.layout.app')
@section('content')

<style>
    .bakery-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 20px rgba(0,0,0,0.06);
        border: 1px solid #e8e8e8;
    }

    .variant-section {
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
        border: 2px dashed #c5d0f0;
        border-radius: 12px;
        padding: 20px;
        margin-top: 15px;
    }

    .variant-row {
        background: #ffffff;
        border: 1px solid #e0e5f2;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 12px;
        position: relative;
        transition: all 0.3s ease;
        box-shadow: 0 1px 8px rgba(0,0,0,0.04);
    }

    .variant-row:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border-color: #a0b4f0;
    }

    .variant-row .remove-variant {
        position: absolute;
        top: 8px;
        right: 10px;
        background: #ff4757;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 26px;
        height: 26px;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
    }

    .variant-row .remove-variant:hover {
        background: #e0324a;
        transform: scale(1.1);
    }

    .add-variant-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 24px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .add-variant-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: #fff;
    }

    .unit-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 5px;
    }

    .unit-badge.kg { background: #e3f2fd; color: #1565c0; }
    .unit-badge.piece { background: #e8f5e9; color: #2e7d32; }
    .unit-badge.pound { background: #fff3e0; color: #e65100; }

    .gram-display {
        color: #667eea;
        font-size: 12px;
        font-weight: 600;
        margin-top: 2px;
    }

    .default-radio-label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        color: #555;
        cursor: pointer;
    }

    .default-radio-label input[type="radio"]:checked + span {
        color: #667eea;
        font-weight: 700;
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #333;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-title i {
        color: #667eea;
    }

    .variant-number {
        width: 28px;
        height: 28px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
    }

    .image-preview-wrapper {
        position: relative;
        display: inline-block;
    }

    .image-preview-wrapper img {
        max-width: 100%;
        border-radius: 8px;
    }

    .clear-image-btn {
        position: absolute;
        top: 2px;
        right: 18px;
        width: 28px;
        height: 28px;
        background-color: rgba(0, 0, 0, 0.6);
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s ease-in-out;
    }

    .clear-image-btn:hover {
        background-color: rgba(255, 0, 0, 0.8);
    }

    #preview {
        width: 100%;
        height: 200px;
        border: 2px dashed #ccc;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #f9f9f9;
    }

    #preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        display: block;
    }

    .unit-type-selector {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .unit-type-option {
        flex: 1;
        min-width: 100px;
    }

    .unit-type-option input[type="radio"] {
        display: none;
    }

    .unit-type-option label {
        display: block;
        text-align: center;
        padding: 12px 15px;
        border: 2px solid #e0e5f2;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 600;
        font-size: 14px;
    }

    .unit-type-option label i {
        display: block;
        font-size: 24px;
        margin-bottom: 5px;
    }

    .unit-type-option input[type="radio"]:checked + label {
        border-color: #667eea;
        background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 100%);
        color: #667eea;
        box-shadow: 0 3px 12px rgba(102, 126, 234, 0.2);
    }
</style>

<!-- navbar-wrapper end -->
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="body-wrapper">
                <div class="bodywrapper__inner">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                        <!-- Left: Page Title -->
                        <h6 class="page-title mb-0">
                            <i class="la la-birthday-cake" style="color: #667eea;"></i> Add Bakery Product
                        </h6>

                        <!-- Center: Buttons -->
                        <div class="d-flex justify-content-center flex-wrap gap-2 flex-grow-1">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#categoryModal">
                                <i class="la la-plus-circle"></i> Add Category
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#subcategoryModal">
                                <i class="las la-plus"></i> Add Subcategory
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary cuModalBtn"
                                data-modal_title="Add New Brand" data-bs-toggle="modal" data-bs-target="#cuModal">
                                <i class="las la-plus"></i> Add Brand
                            </button>
                            <a class="btn btn-md btn-outline-primary py-2" href="{{ url('/home') }}">
                                <i class="la la-tachometer-alt"></i> Dashboard
                            </a>
                        </div>
                        <!-- Right: Back Button -->
                        <div class="d-flex">
                            <a href="{{ route('product') }}" class="btn btn-sm btn-outline-primary">
                                <i class="la la-undo"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="row mb-none-30">
                        <div class="col-lg-12 col-md-12 mb-30">
                            <div class="bakery-card">
                                <div class="card-body">
                                    @if (session()->has('success'))
                                    <div class="alert alert-success">
                                        <strong>Success!</strong> {{ session('success') }}.
                                    </div>
                                    @endif
                                    @if (session()->has('error'))
                                    <div class="alert alert-danger">
                                        <strong>Error!</strong> {{ session('error') }}
                                    </div>
                                    @endif

                                    <form action="{{ route('store-product') }}" method="POST"
                                        enctype="multipart/form-data" id="productForm">
                                        @csrf
                                        <div class="row g-3">
                                            @if ($errors->any())
                                            <div class="col-12">
                                                <div class="alert alert-danger py-2">
                                                    <strong>Validation Errors:</strong>
                                                    <ul class="mb-0 ps-3">
                                                        @foreach ($errors->all() as $error)
                                                        <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                            @endif

                                            <!-- ===== BASIC PRODUCT INFO ===== -->
                                            <div class="col-md-4">
                                                <div class="card shadow-sm border-0">
                                                    <div class="image-preview-wrapper">
                                                        <img id="preview" src="" alt="No Image Selected">
                                                        <button type="button" class="clear-image-btn"
                                                            id="clearImageBtn">&times;</button>
                                                    </div>
                                                    <input type="file" id="imageInput" name="image" class="mt-2">
                                                </div>
                                            </div>

                                            <div class="col-md-8">
                                                <div class="row g-3">
                                                    <div class="col-sm-6">
                                                        <label class="form-label fw-bold">Product Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="product_name" class="form-control"
                                                            placeholder="e.g. Chocolate Cake" required>
                                                    </div>

                                                    <div class="col-sm-3">
                                                        <label class="form-label fw-bold">Category</label>
                                                        <select id="category-dropdown" name="category_id" class="form-select">
                                                            <option value="">Select Category</option>
                                                            @foreach ($categories as $cat)
                                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="col-sm-3">
                                                        <label class="form-label fw-bold">Sub Category</label>
                                                        <select id="subcategory-dropdown" name="sub_category_id" class="form-select">
                                                            <option value="">Select Subcategory</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-sm-4">
                                                        <label class="form-label fw-bold">Brand</label>
                                                        <select name="brand_id[]" class="form-select brand-select">
                                                            <option value="">Select Brand</option>
                                                            @foreach ($brands as $brand)
                                                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="col-sm-4">
                                                        <label for="barcodeInput" class="form-label fw-bold">Barcode</label>
                                                        <div class="input-group">
                                                            <input type="text" id="barcodeInput" name="barcode_path" class="form-control"
                                                                placeholder="Enter or Generate">
                                                            <button type="button" id="generateBarcodeBtn" class="btn btn-primary btn-sm">
                                                                <i class="la la-barcode"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="col-sm-4">
                                                        <label class="form-label fw-bold">Alert Quantity</label>
                                                        <input type="number" name="alert_quantity" class="form-control" value="0">
                                                    </div>

                                                    <!-- ===== UNIT TYPE SELECTION ===== -->
                                                    <div class="col-12">
                                                        <label class="form-label fw-bold">
                                                            <i class="la la-balance-scale" style="color: #667eea;"></i>
                                                            Product Unit Type <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="unit-type-selector">
                                                            <div class="unit-type-option">
                                                                <input type="radio" name="unit_type" value="kg" id="unit_kg">
                                                                <label for="unit_kg">
                                                                    <i class="la la-weight-hanging"></i>
                                                                    KG (Weight)
                                                                    <small class="d-block text-muted">1 KG = 1000 Gram</small>
                                                                </label>
                                                            </div>
                                                            <div class="unit-type-option">
                                                                <input type="radio" name="unit_type" value="piece" id="unit_piece" checked>
                                                                <label for="unit_piece">
                                                                    <i class="la la-cubes"></i>
                                                                    Pieces
                                                                    <small class="d-block text-muted">Count based</small>
                                                                </label>
                                                            </div>
                                                            <div class="unit-type-option">
                                                                <input type="radio" name="unit_type" value="pound" id="unit_pound">
                                                                <label for="unit_pound">
                                                                    <i class="la la-birthday-cake"></i>
                                                                    Pound (Bakery)
                                                                    <small class="d-block text-muted">Cake sizes</small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-sm-12">
                                                        <label class="form-label fw-bold">Note</label>
                                                        <textarea name="note" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ===== PRODUCT VARIANTS SECTION ===== -->
                                        <div class="variant-section mt-4">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div class="section-title mb-0">
                                                    <i class="la la-layer-group"></i>
                                                    Product Sizes / Variants
                                                    <span class="unit-badge" id="selectedUnitBadge">Piece</span>
                                                </div>
                                                <button type="button" class="add-variant-btn" id="addVariantBtn">
                                                    <i class="la la-plus"></i> Add Size / Variant
                                                </button>
                                            </div>

                                            <p class="text-muted mb-3" style="font-size: 13px;">
                                                <i class="la la-info-circle"></i>
                                                <span id="variantHelpText">Add different sizes and prices. Example: 1 Pound = Rs 1000, 2 Pound = Rs 1500</span>
                                            </p>

                                            <div id="variantsContainer">
                                                <!-- Variant rows will be added here dynamically -->
                                            </div>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="mt-4">
                                            <button type="submit" id="submitProductBtn"
                                                class="btn w-100 py-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; font-weight: 700; font-size: 16px; border: none; border-radius: 10px;">
                                                <i class="la la-check-circle"></i> Save Product
                                            </button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- bodywrapper__inner end -->
            </div><!-- body-wrapper end -->
        </div>
    </div>

    {{-- category modal  --}}
    <div id="categoryModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span>Add Category</span></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('manual.category') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="redirect_url" value="{{ route('product') }}">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary h-45 w-100">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Subcategory modal  --}}
    <div id="subcategoryModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span>Add Subcategory</span></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('manual.subcategory') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Category Name</label>
                            <select name="category_id" class="form-select">
                                @foreach ($categories as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Sub-Category Name</label>
                            <input type="text" name="sub_category" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary h-45 w-100">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- brand modal --}}
    <div id="cuModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span>Add Brand</span></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('manual.Brand') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary h-45 w-100">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@section('scripts')

<script>
$(document).ready(function() {
    $('.brand-select').select2({
        placeholder: "Select Brand",
        allowClear: true
    });
});
</script>

<script>
(function() {
    let variantIndex = 0;
    const container = document.getElementById('variantsContainer');
    const addBtn = document.getElementById('addVariantBtn');
    const unitRadios = document.querySelectorAll('input[name="unit_type"]');
    const unitBadge = document.getElementById('selectedUnitBadge');
    const helpText = document.getElementById('variantHelpText');

    // Get current selected unit type
    function getUnitType() {
        const checked = document.querySelector('input[name="unit_type"]:checked');
        return checked ? checked.value : 'piece';
    }

    // Get size label based on unit type
    function getSizeLabel(unitType) {
        switch(unitType) {
            case 'kg': return 'Weight (Grams)';
            case 'pound': return 'Size (Pound)';
            case 'piece': return 'Quantity';
            default: return 'Size';
        }
    }

    // Get placeholder based on unit type
    function getPlaceholder(unitType) {
        switch(unitType) {
            case 'kg': return 'e.g. 250, 500, 1000';
            case 'pound': return 'e.g. 1, 2, 3';
            case 'piece': return 'e.g. 6, 12, 24';
            default: return 'Enter value';
        }
    }

    // Get variant name placeholder
    function getVariantPlaceholder(unitType) {
        switch(unitType) {
            case 'kg': return 'e.g. 250g, Half KG, 1 KG';
            case 'pound': return 'e.g. 1 Pound, 2 Pound';
            case 'piece': return 'e.g. Box of 6, Box of 12';
            default: return 'Variant Name';
        }
    }

    // Get help text based on unit type
    function getHelpText(unitType) {
        switch(unitType) {
            case 'kg': return 'Add weight in Grams. It will auto-convert to KG (e.g. 250 = 0.250 KG)';
            case 'pound': return 'Add different sizes and prices. Example: 1 Pound = Rs 1000, 2 Pound = Rs 1500';
            case 'piece': return 'Add different piece quantities and prices. Example: Box of 6 = Rs 500, Box of 12 = Rs 900';
            default: return '';
        }
    }

    // Update unit badge
    function updateUnitBadge(unitType) {
        unitBadge.textContent = unitType.toUpperCase();
        unitBadge.className = 'unit-badge ' + unitType;
        helpText.textContent = getHelpText(unitType);
    }

    // Listen for unit type changes
    unitRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            updateUnitBadge(this.value);
            // Update existing variant rows size labels
            document.querySelectorAll('.variant-size-label').forEach(function(el) {
                el.textContent = getSizeLabel(radio.value);
            });
            document.querySelectorAll('.variant-size-input').forEach(function(el) {
                el.placeholder = getPlaceholder(radio.value);
            });
            document.querySelectorAll('.variant-size-unit-hidden').forEach(input => input.value = radio.value);
            
            // Toggle variant stock column visibility
            const stockCols = document.querySelectorAll('.variant-stock-col');
            stockCols.forEach(col => {
                col.style.display = radio.value === 'kg' ? 'none' : 'block';
            });

            // Update variant name placeholders
            document.querySelectorAll('.variant-name-input').forEach(function(el) {
                el.placeholder = getVariantPlaceholder(radio.value);
            });

            // If KG, trigger KG calculation on existing rows
            document.querySelectorAll('.variant-size-input').forEach(function(el) {
                el.dispatchEvent(new Event('input'));
            });
        });
    });

    // Add variant row
    addBtn.addEventListener('click', function() {
        addVariantRow();
    });

    function addVariantRow() {
        const unitType = getUnitType();
        const idx = variantIndex++;
        const isFirst = container.children.length === 0;

        const row = document.createElement('div');
        row.className = 'variant-row';
        row.innerHTML = `
            <button type="button" class="remove-variant" onclick="this.closest('.variant-row').remove(); updateVariantNumbers();">&times;</button>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="variant-number">${container.children.length + 1}</span>
                <label class="default-radio-label mb-0">
                    <input type="radio" name="variant_default" value="${idx}" ${isFirst ? 'checked' : ''}>
                    <span>Default Variant</span>
                </label>
            </div>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Variant Name <span class="text-danger">*</span></label>
                    <input type="text" name="variant_name[]" class="form-control variant-name-input"
                        placeholder="${getVariantPlaceholder(unitType)}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold variant-size-label">${getSizeLabel(unitType)}</label>
                    <input type="number" name="variant_size_value[]" class="form-control variant-size-input"
                        placeholder="${getPlaceholder(unitType)}" step="0.01" min="0" value="0">
                    <input type="hidden" name="variant_size_unit[]" class="variant-size-unit-hidden" value="${unitType}">
                    <div class="gram-display" id="gramDisplay_${idx}"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Sale Price <span class="text-danger">*</span></label>
                    <input type="number" name="variant_price[]" class="form-control"
                        placeholder="0" step="0.01" min="0" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Purchase Price</label>
                    <input type="number" name="variant_cost_price[]" class="form-control"
                        placeholder="0" step="0.01" min="0" value="0">
                </div>
                <div class="col-md-1 variant-stock-col" style="display: ${unitType === 'kg' ? 'none' : 'block'}">
                    <label class="form-label small fw-bold">Stock</label>
                    <input type="number" name="variant_stock[]" class="form-control"
                        placeholder="0" step="0.01" min="0" value="0">
                </div>
            </div>
        `;

        container.appendChild(row);

        // Add KG calculation
        const sizeInput = row.querySelector('.variant-size-input');
        const gramDisplay = row.querySelector('.gram-display');

        sizeInput.addEventListener('input', function() {
            const currentUnit = getUnitType();
            if (currentUnit === 'kg') {
                const gramsValue = parseFloat(this.value) || 0;
                const kg = gramsValue / 1000;
                gramDisplay.textContent = '= ' + kg.toFixed(3) + ' KG';
            } else {
                gramDisplay.textContent = '';
            }
        });

        // Trigger initial display
        sizeInput.dispatchEvent(new Event('input'));
    }

    // Update variant numbers after removal
    window.updateVariantNumbers = function() {
        document.querySelectorAll('.variant-row .variant-number').forEach(function(el, i) {
            el.textContent = i + 1;
        });
    };

    // Auto-add first variant
    addVariantRow();

    // Initialize badge
    updateUnitBadge(getUnitType());
})();
</script>

<script>
    // Form submission guard
    (function() {
        const form = document.getElementById('productForm');

        form.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            const el = e.target;
            const tag = el.tagName.toLowerCase();
            if (tag === 'textarea') return;
            if (el.classList && el.classList.contains('select2-search__field')) return;
            e.preventDefault();
        });

        form.addEventListener('submit', function(e) {
            const byButton = e.submitter && e.submitter.id === 'submitProductBtn';
            if (!byButton) {
                e.preventDefault();
            }
        });
    })();
</script>

<script>
    // Barcode generation
    document.getElementById('generateBarcodeBtn').addEventListener('click', function() {
        let currentValue = document.getElementById('barcodeInput').value.trim();
        if (currentValue !== "") {
            fetch('/generate-barcode-image?code=' + currentValue)
                .then(res => { if (!res.ok) throw new Error('Server error'); return res.json(); })
                .then(data => {
                    document.getElementById('barcodeInput').value = data.barcode_number;
                })
                .catch(err => console.error('Barcode error:', err));
        } else {
            fetch('{{ route("generate-barcode-image") }}')
                .then(res => { if (!res.ok) throw new Error('Server error'); return res.json(); })
                .then(data => {
                    document.getElementById('barcodeInput').value = data.barcode_number;
                })
                .catch(err => console.error('Barcode error:', err));
        }
    });
</script>

<script>
    // Image preview
    const imageInput = document.getElementById('imageInput');
    const preview = document.getElementById('preview');
    const clearImageBtn = document.getElementById('clearImageBtn');

    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    clearImageBtn.addEventListener('click', function() {
        preview.src = "";
        imageInput.value = "";
    });

    // Category-Subcategory dropdown
    $('#category-dropdown').on('change', function() {
        var categoryId = $(this).val();
        if (categoryId) {
            $.ajax({
                url: '/get-subcategories/' + categoryId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    $('#subcategory-dropdown').empty();
                    $('#subcategory-dropdown').append('<option selected disabled>Select Subcategory</option>');
                    $.each(data, function(key, value) {
                        $('#subcategory-dropdown').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                }
            });
        } else {
            $('#subcategory-dropdown').empty();
        }
    });
</script>

@endsection