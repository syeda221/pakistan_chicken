@extends('admin_panel.layout.app')
{{-- Purchase Page v2: Search → Click → Variant Modal --}}

<style>
/* ===== PURCHASE PAGE STYLES ===== */
.purchase-search-bar {
    position: relative;
}
.purchase-search-bar input {
    font-size: 1.05rem;
    padding: 10px 16px;
    border-radius: 8px;
    border: 2px solid #dee2e6;
    transition: border-color 0.2s;
}
.purchase-search-bar input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13,110,253,.15);
}
#productSearchResults {
    position: absolute;
    top: 100%;
    left: 0; right: 0;
    z-index: 9999;
    background: #fff;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 340px;
    overflow-y: auto;
    box-shadow: 0 6px 20px rgba(0,0,0,.12);
}
.product-search-item {
    padding: 10px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background .15s;
}
.product-search-item:hover, .product-search-item.active {
    background: #e8f4fd;
}
.product-search-item strong { display: block; font-size: .95rem; }
.product-search-item small { color: #6c757d; font-size: .8rem; }

/* ===== VARIANT MODAL ===== */
#variantModal .modal-header {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    color: white;
    border-radius: 8px 8px 0 0;
}
#variantModal .modal-header .btn-close { filter: invert(1); }
.variant-size-btn {
    border: 2px solid #0d6efd;
    background: #fff;
    color: #0d6efd;
    border-radius: 8px;
    padding: 8px 18px;
    font-weight: 600;
    transition: all .2s;
    cursor: pointer;
    font-size: .92rem;
}
.variant-size-btn:hover, .variant-size-btn.selected {
    background: #0d6efd;
    color: #fff;
}
.no-variant-btn {
    border: 2px solid #198754;
    background: #fff;
    color: #198754;
    border-radius: 8px;
    padding: 8px 18px;
    font-weight: 600;
    transition: all .2s;
    cursor: pointer;
}
.no-variant-btn.selected {
    background: #198754;
    color: #fff;
}

/* ===== PURCHASE TABLE ===== */
.purchase-items-table th { background: #f8f9fa; font-size: .85rem; }
.purchase-items-table td { vertical-align: middle; font-size: .9rem; }
.badge-variant { background: #e3f2fd; color: #0d47a1; border-radius: 4px; padding: 2px 8px; font-size: .78rem; }

/* Summary box */
.summary-card { background: #f8f9fa; border-radius: 10px; border: 1px solid #dee2e6; padding: 20px; }
.summary-card .net-amount { font-size: 1.6rem; color: #0d6efd; font-weight: 700; }
</style>

@section('content')
<div class="main-content">
  <div class="main-content-inner">
    <div class="container-fluid">

      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="page-title m-0"><i class="bi bi-cart-plus text-primary me-2"></i>Create Purchase</h2>
        <a href="{{ route('Purchase.home') }}" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Back
        </a>
      </div>

      @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        <strong>✓</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      @endif

      <form action="{{ route('store.Purchase') }}" method="POST" id="mainPurchaseForm">
        @csrf

        <!-- ===== TOP FORM FIELDS ===== -->
        <div class="card mb-3">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label fw-semibold"><i class="bi bi-calendar3 text-primary me-1"></i>Date</label>
                <input type="date" name="purchase_date" class="form-control" value="{{ date('Y-m-d') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold"><i class="bi bi-building text-primary me-1"></i>Vendor</label>
                <select name="vendor_id" class="form-control select2">
                  <option disabled selected>Select Vendor</option>
                  @foreach($Vendor as $v)
                  <option value="{{ $v->id }}">{{ $v->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold"><i class="bi bi-receipt text-primary me-1"></i>Company Inv #</label>
                <input type="text" name="purchase_order_no" class="form-control" placeholder="Company invoice no.">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold"><i class="bi bi-arrow-left-right text-primary me-1"></i>Purchase Type</label>
                <div class="d-flex gap-3 mt-1">
                  <div class="form-check">
                    <input class="form-check-input purchaseType" type="radio" name="purchase_to" value="shop" id="purchaseShop" checked>
                    <label class="form-check-label" for="purchaseShop">Shop</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input purchaseType" type="radio" name="purchase_to" value="warehouse" id="purchaseWarehouse">
                    <label class="form-check-label" for="purchaseWarehouse">Warehouse</label>
                  </div>
                </div>
              </div>
              <div class="col-md-3 d-none" id="warehouseBox">
                <label class="form-label fw-semibold"><i class="bi bi-building2 text-primary me-1"></i>Select Warehouse</label>
                <select name="warehouse_id" class="form-control">
                  <option disabled selected>Select Warehouse</option>
                  @foreach($Warehouse as $w)
                  <option value="{{ $w->id }}">{{ $w->warehouse_name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold"><i class="bi bi-chat-text text-primary me-1"></i>Note</label>
                <input type="text" name="note" class="form-control" placeholder="Optional note">
              </div>
            </div>
          </div>
        </div>

        <!-- ===== PRODUCT SEARCH ===== -->
        <div class="card mb-3">
          <div class="card-body">
            <label class="form-label fw-bold fs-5"><i class="bi bi-search text-primary me-2"></i>Search Product</label>
            <div class="purchase-search-bar">
              <input type="text" id="productSearchInput" class="form-control form-control-lg"
                placeholder="Type product name to search..." autocomplete="off">
              <div id="productSearchResults"></div>
            </div>
            <small class="text-muted mt-1 d-block">Search karein → product par click karein → size aur qty fill karein</small>
          </div>
        </div>

        <!-- ===== PURCHASE ITEMS TABLE ===== -->
        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-table text-primary me-1"></i>Purchase Items</strong>
            <span class="badge bg-primary" id="itemCountBadge">0 items</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered purchase-items-table mb-0">
                <thead>
                  <tr class="text-center">
                    <th>#</th>
                    <th>Product</th>
                    <th>Size / Variant</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="purchaseItemsBody">
                  <tr id="emptyRow">
                    <td colspan="7" class="text-center text-muted py-4">
                      <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                      Koi product add nahi kiya. Upar se product search karein.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- hidden inputs container (actual form data) -->
        <div id="hiddenInputs"></div>

        <!-- ===== SUMMARY ===== -->
        <div class="card mb-3">
          <div class="card-body">
            <div class="row g-3 align-items-end">
              <div class="col-md-3">
                <label class="form-label fw-semibold">Subtotal</label>
                <input type="text" id="subtotalDisplay" class="form-control" value="0.00" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Discount (Overall)</label>
                <input type="number" step="0.01" name="discount" id="overallDiscount" class="form-control" value="0">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Extra Cost</label>
                <input type="number" step="0.01" name="extra_cost" id="extraCost" class="form-control" value="0">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold text-primary">Net Amount</label>
                <input type="text" id="netAmountDisplay" name="net_amount" class="form-control fw-bold text-primary fs-5" value="0.00" readonly>
              </div>
            </div>
            <div class="mt-3 text-end">
              <button type="button" id="savePurchaseBtn" class="btn btn-primary btn-lg px-5">
                <i class="bi bi-check-circle me-1"></i>Save Purchase
              </button>
            </div>
          </div>
        </div>

      </form>
    </div>
  </div>
</div>

<!-- ===== VARIANT SELECTION MODAL ===== -->
<div class="modal fade" id="variantModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0" id="variantModalTitle">Product Name</h5>
          <small id="variantModalSubtitle" class="opacity-75"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Size Buttons -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Size / Variant چنیں:</label>
          <div id="variantSizeButtons" class="d-flex flex-wrap gap-2"></div>
        </div>

        <!-- Price -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Purchase Price (Rs)</label>
          <input type="number" step="0.01" id="modalPrice" class="form-control form-control-lg"
            placeholder="0.00">
        </div>

        <!-- Qty -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Quantity</label>
          <input type="number" step="0.001" min="0.001" id="modalQty" class="form-control form-control-lg"
            placeholder="0" value="1">
        </div>

        <!-- Live Total -->
        <div class="alert alert-info py-2 mb-0">
          <span class="fw-semibold">Item Total: </span>
          <span id="modalItemTotal" class="fw-bold text-primary fs-5">0.00</span>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" id="addToCartBtn" class="btn btn-success btn-lg px-4">
          <i class="bi bi-plus-circle me-1"></i>Add to Purchase
        </button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {

  // ===== SELECT2 =====
  $('.select2').select2({ width: '100%', placeholder: 'Select...', allowClear: true });

  // ===== PURCHASE TYPE TOGGLE =====
  $('.purchaseType').on('change', function() {
    if ($(this).val() === 'warehouse') {
      $('#warehouseBox').removeClass('d-none');
    } else {
      $('#warehouseBox').addClass('d-none');
    }
  });

  // ===== GLOBAL STATE =====
  let purchaseItems = []; // array of {product_id, variant_id, product_name, size_label, price, qty, line_total}
  let currentProduct = null; // product being selected
  let currentVariant = null; // selected variant in modal

  // ===== PRODUCT SEARCH =====
  let searchTimer = null;
  let searchResults = [];
  let activeSearchIndex = -1;

  $('#productSearchInput').on('input', function() {
    let q = $(this).val().trim();
    clearTimeout(searchTimer);
    activeSearchIndex = -1;

    if (q.length < 1) {
      $('#productSearchResults').empty();
      return;
    }

    searchTimer = setTimeout(function() {
      $('#productSearchResults').html('<div class="product-search-item text-muted"><i class="bi bi-hourglass-split me-1"></i>Searching...</div>');

      $.get("{{ route('search-product-name') }}", { q: q }, function(res) {
        searchResults = res;
        renderSearchResults(res);
      });
    }, 200);
  });

  $('#productSearchInput').on('keydown', function(e) {
    const items = $('#productSearchResults .product-search-item[data-index]');
    if (!items.length) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      activeSearchIndex = Math.min(activeSearchIndex + 1, items.length - 1);
      items.removeClass('active').eq(activeSearchIndex).addClass('active');
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeSearchIndex = Math.max(activeSearchIndex - 1, 0);
      items.removeClass('active').eq(activeSearchIndex).addClass('active');
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (activeSearchIndex >= 0) {
        items.eq(activeSearchIndex).trigger('click');
      }
    }
  });

  function renderSearchResults(results) {
    if (!results.length) {
      $('#productSearchResults').html('<div class="product-search-item text-muted">کوئی product نہیں ملا</div>');
      return;
    }

    // Group by product_id (to show product once even if it has variants)
    let seen = {};
    let html = '';
    let index = 0;

    results.forEach(function(p) {
      let productId = p.id;
      if (!seen[productId]) {
        seen[productId] = true;
        
        let isGram = p.unit_type === 'kg' || (p.item_name && p.item_name.toLowerCase().includes('gram')) || (p.unit && p.unit.name && p.unit.name.toLowerCase().includes('gram'));
        // Show with variant count info
        let variantInfo = (p.variant_id && !isGram) ? '<span class="text-primary">• Sizes available</span>' : '';
        html += `<div class="product-search-item" data-index="${index}" data-product-id="${productId}" data-is-gram="${isGram ? 1 : 0}">
          <strong>${p.item_name.split(' (')[0]}</strong>
          <small>Code: ${p.item_code || '-'} | Price: Rs ${p.wholesale_price || 0} ${variantInfo}</small>
        </div>`;
        index++;
      }
    });

    $('#productSearchResults').html(html);
  }

  // ===== PRODUCT CLICK → OPEN MODAL =====
  $(document).on('click', '.product-search-item[data-product-id]', function() {
    let productId = $(this).data('product-id');
    let isGram = $(this).data('is-gram') == 1;

    // Clear search
    $('#productSearchResults').empty();

    // Fetch full product + variants
    $.get("{{ route('pos.product.variants', ':id') }}".replace(':id', productId), function(res) {
      if (isGram) {
          res.variants = []; // Ignore variants for KG/Gram items, treat as main product
      }
      openVariantModal(res);
    });
  });

  // Close dropdown on outside click
  $(document).on('click', function(e) {
    if (!$(e.target).closest('.purchase-search-bar').length) {
      $('#productSearchResults').empty();
      activeSearchIndex = -1;
    }
  });

  // ===== VARIANT MODAL =====
  function openVariantModal(product) {
    currentProduct = product;
    currentVariant = null;

    $('#variantModalTitle').text(product.item_name);
    $('#variantModalSubtitle').text('Code: ' + (product.item_code || '-'));
    $('#modalPrice').val('');
    $('#modalQty').val(1);
    $('#modalItemTotal').text('0.00');

    // Build size buttons
    let btnsHtml = '';
    if (product.variants && product.variants.length > 0) {
      product.variants.forEach(function(v) {
        let wholeSalePrice = v.wholesale_price || v.price || 0;
        btnsHtml += `<button type="button" class="variant-size-btn" data-variant-id="${v.id}"
          data-size-label="${v.size_label || v.name}"
          data-wholesale="${wholeSalePrice}"
          data-price="${v.price || 0}">
          ${v.size_label || v.name}
        </button>`;
      });
    } else {
      // No variants — show single "Default" option, user enters price manually
      btnsHtml = `<button type="button" class="no-variant-btn selected" data-variant-id="" data-size-label="">
        Default (No Size)
      </button>`;
      currentVariant = { id: null, size_label: '', price: 0 };
    }

    $('#variantSizeButtons').html(btnsHtml);

    // If only one variant, auto-select it
    if (product.variants && product.variants.length === 1) {
      $('#variantSizeButtons .variant-size-btn:first').trigger('click');
    }

    $('#variantModal').modal('show');

    setTimeout(function() {
      $('#modalPrice').focus().select();
    }, 400);
  }

  // Size button click
  $(document).on('click', '.variant-size-btn', function() {
    $('.variant-size-btn').removeClass('selected');
    $(this).addClass('selected');

    let vid = $(this).data('variant-id');
    let sizeLabel = $(this).data('size-label');
    let price = parseFloat($(this).data('wholesale')) || parseFloat($(this).data('price')) || 0;

    currentVariant = { id: vid, size_label: sizeLabel, price: price };
    $('#modalPrice').val(price > 0 ? price : '');
    calcModalTotal();
    $('#modalQty').focus().select();
  });

  $(document).on('click', '.no-variant-btn', function() {
    let price = parseFloat($('#modalPrice').val()) || 0;
    currentVariant = { id: null, size_label: '', price: price };
    calcModalTotal();
  });

  // Modal price/qty live calc
  $('#modalPrice, #modalQty').on('input', function() {
    calcModalTotal();
  });

  function calcModalTotal() {
    let price = parseFloat($('#modalPrice').val()) || 0;
    let qty = parseFloat($('#modalQty').val()) || 0;
    $('#modalItemTotal').text((price * qty).toFixed(2));
  }

  // ===== ADD TO PURCHASE =====
  $('#addToCartBtn').on('click', function() {
    if (!currentProduct) return;

    let price = parseFloat($('#modalPrice').val());
    let qty = parseFloat($('#modalQty').val());

    if (!currentVariant && currentProduct.variants && currentProduct.variants.length > 0) {
      Swal.fire({ icon: 'warning', title: 'Size چنیں', text: 'پہلے کوئی size/variant ضرور چنیں', timer: 2000, showConfirmButton: false });
      return;
    }
    if (!price || price <= 0) {
      Swal.fire({ icon: 'warning', title: 'Price درج کریں', text: 'Price درج کریں', timer: 2000, showConfirmButton: false });
      $('#modalPrice').focus();
      return;
    }
    if (!qty || qty <= 0) {
      Swal.fire({ icon: 'warning', title: 'Qty درج کریں', text: 'Quantity درج کریں', timer: 1500, showConfirmButton: false });
      $('#modalQty').focus();
      return;
    }

    let lineTotal = price * qty;
    let productId = currentProduct.product_id;
    let variantId = currentVariant ? currentVariant.id : null;
    let sizeLabel = currentVariant ? currentVariant.size_label : '';
    let productName = currentProduct.item_name;

    // Check if same product+variant already exists → update qty
    let existingIndex = purchaseItems.findIndex(function(item) {
      return item.product_id == productId && item.variant_id == variantId;
    });

    if (existingIndex >= 0) {
      purchaseItems[existingIndex].qty += qty;
      purchaseItems[existingIndex].price = price;
      purchaseItems[existingIndex].line_total = purchaseItems[existingIndex].price * purchaseItems[existingIndex].qty;
    } else {
      purchaseItems.push({
        product_id: productId,
        variant_id: variantId,
        product_name: productName,
        size_label: sizeLabel,
        price: price,
        qty: qty,
        line_total: lineTotal
      });
    }

    renderTable();

    // Reset for next entry
    $('#modalPrice').val('');
    $('#modalQty').val(1).focus().select();
    $('#modalItemTotal').text('0.00');
    // Don't close modal — user can add more sizes
  });

  // ===== RENDER TABLE =====
  function renderTable() {
    if (purchaseItems.length === 0) {
      $('#purchaseItemsBody').html(`<tr id="emptyRow"><td colspan="7" class="text-center text-muted py-4">
        <i class="bi bi-inbox fs-3 d-block mb-2"></i>کوئی product add نہیں کیا۔</td></tr>`);
      $('#itemCountBadge').text('0 items');
      updateHiddenInputs();
      updateSummary();
      return;
    }

    let html = '';
    purchaseItems.forEach(function(item, index) {
      let sizeSpan = item.size_label
        ? `<span class="badge-variant ms-1">${item.size_label}</span>`
        : '';
      html += `<tr>
        <td class="text-center">${index+1}</td>
        <td>${item.product_name}${sizeSpan}</td>
        <td class="text-center">${item.size_label || '<span class="text-muted">-</span>'}</td>
        <td class="text-end">
          <input type="number" step="0.01" class="form-control form-control-sm text-end inline-price"
            data-index="${index}" value="${item.price}" style="width:100px">
        </td>
        <td class="text-center">
          <input type="number" step="0.001" min="0.001" class="form-control form-control-sm text-center inline-qty"
            data-index="${index}" value="${item.qty}" style="width:80px">
        </td>
        <td class="text-end fw-semibold row-line-total">${item.line_total.toFixed(2)}</td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-danger remove-item" data-index="${index}">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>`;
    });

    $('#purchaseItemsBody').html(html);
    $('#itemCountBadge').text(purchaseItems.length + ' item' + (purchaseItems.length !== 1 ? 's' : ''));
    updateHiddenInputs();
    updateSummary();
  }

  // Inline price / qty edit
  $(document).on('input', '.inline-price', function() {
    let idx = $(this).data('index');
    let price = parseFloat($(this).val()) || 0;
    purchaseItems[idx].price = price;
    purchaseItems[idx].line_total = price * purchaseItems[idx].qty;
    $(this).closest('tr').find('.row-line-total').text(purchaseItems[idx].line_total.toFixed(2));
    updateHiddenInputs();
    updateSummary();
  });

  $(document).on('input', '.inline-qty', function() {
    let idx = $(this).data('index');
    let qty = parseFloat($(this).val()) || 0;
    purchaseItems[idx].qty = qty;
    purchaseItems[idx].line_total = purchaseItems[idx].price * qty;
    $(this).closest('tr').find('.row-line-total').text(purchaseItems[idx].line_total.toFixed(2));
    updateHiddenInputs();
    updateSummary();
  });

  // Remove item
  $(document).on('click', '.remove-item', function() {
    let idx = $(this).data('index');
    purchaseItems.splice(idx, 1);
    renderTable();
  });

  // ===== HIDDEN INPUTS (actual form submission) =====
  function updateHiddenInputs() {
    let html = '';
    purchaseItems.forEach(function(item, index) {
      html += `<input type="hidden" name="product_id[]" value="${item.product_id}">`;
      html += `<input type="hidden" name="variant_id[]" value="${item.variant_id || ''}">`;
      html += `<input type="hidden" name="qty[]" value="${item.qty}">`;
      html += `<input type="hidden" name="price[]" value="${item.price}">`;
      html += `<input type="hidden" name="unit[]" value="${item.size_label || 'Pc'}">`;
      html += `<input type="hidden" name="item_note[]" value="">`;
    });
    $('#hiddenInputs').html(html);
  }

  // ===== SUMMARY =====
  function updateSummary() {
    let subtotal = purchaseItems.reduce(function(sum, item) { return sum + item.line_total; }, 0);
    let discount = parseFloat($('#overallDiscount').val()) || 0;
    let extra = parseFloat($('#extraCost').val()) || 0;
    let net = subtotal - discount + extra;

    $('#subtotalDisplay').val(subtotal.toFixed(2));
    $('#netAmountDisplay').val(net.toFixed(2));
  }

  $('#overallDiscount, #extraCost').on('input', updateSummary);

  // ===== SAVE PURCHASE =====
  $('#savePurchaseBtn').on('click', function() {
    if (purchaseItems.length === 0) {
      Swal.fire({ icon: 'warning', title: 'خالی', text: 'کم از کم ایک product add کریں', timer: 2000, showConfirmButton: false });
      return;
    }

    let purchaseTo = $('input[name="purchase_to"]:checked').val();
    if (!purchaseTo) {
      Swal.fire({ icon: 'warning', title: 'Purchase Type', text: 'Shop یا Warehouse select کریں', timer: 2000, showConfirmButton: false });
      return;
    }

    if (purchaseTo === 'warehouse' && !$('select[name="warehouse_id"]').val()) {
      Swal.fire({ icon: 'warning', title: 'Warehouse', text: 'Warehouse select کریں', timer: 2000, showConfirmButton: false });
      return;
    }

    $('#mainPurchaseForm').submit();
  });

  // ===== SUCCESS ALERT =====
  @if(session('success'))
  Swal.fire({ icon: 'success', title: 'کامیاب!', text: @json(session('success')), confirmButtonColor: '#0d6efd' });
  @endif

  @if($errors->any())
  Swal.fire({ icon: 'error', title: 'خطا', html: @json(implode('<br>', $errors->all())), confirmButtonColor: '#dc3545' });
  @endif
});
</script>
@endsection