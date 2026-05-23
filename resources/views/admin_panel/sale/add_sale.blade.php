@extends('admin_panel.layout.app')
@section('content')
<style>
/* POS page scoped styles */
.pos-wrap,.pos-wrap *{box-sizing:border-box}
.pos-wrap{display:flex;background:#f0f2f7;font-family:'Segoe UI',sans-serif;margin:-20px -15px 0}
/* LEFT */
.pos-left{flex:1;display:flex;flex-direction:column;gap:8px;padding:10px;min-width:0}
/* TOPBAR */
.pos-topbar{background:#fff;border-radius:12px;padding:9px 14px;display:flex;align-items:center;gap:10px;box-shadow:0 2px 8px rgba(0,0,0,.07);flex-shrink:0}
.pos-brand{font-size:16px;font-weight:800;color:#2d3748;white-space:nowrap}
.srchwrap{flex:1;position:relative}
.srchwrap input{width:100%;border:1.5px solid #e0e4ef;border-radius:9px;padding:7px 12px 7px 34px;font-size:14px;outline:none;background:#f7f8fc;transition:.2s}
.srchwrap input:focus{border-color:#667eea;background:#fff}
.srchwrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#999;font-size:16px}
.btn-cls{background:#f0f2f8;border:none;border-radius:9px;padding:7px 12px;font-size:13px;color:#555;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:4px;white-space:nowrap;transition:.15s}
.btn-cls:hover{background:#e0e5f0;color:#333}
/* CATEGORY TABS */
.cat-tabs{display:flex;gap:7px;overflow-x:auto;flex-shrink:0;padding-bottom:2px;scrollbar-width:none}
.cat-tabs::-webkit-scrollbar{display:none}
.cat-tab{white-space:nowrap;padding:6px 16px;border-radius:20px;background:#fff;color:#555;font-size:12.5px;font-weight:600;cursor:pointer;border:1.5px solid #e0e4ef;transition:.18s;user-select:none}
.cat-tab:hover{border-color:#667eea;color:#667eea}
.cat-tab.active{background:linear-gradient(135deg,#667eea,#764ba2);border-color:transparent;color:#fff;box-shadow:0 3px 10px rgba(102,126,234,.3)}
/* GRID — key fix: height is set by JS, overflow-y scrolls  */
.prod-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:12px;overflow-y:auto;align-content:start;padding-bottom:8px}
.prod-grid::-webkit-scrollbar{width:5px}
.prod-grid::-webkit-scrollbar-thumb{background:#d0d6e8;border-radius:5px}
/* CARD */
.pc{background:#fff;border-radius:16px;overflow:hidden;cursor:pointer;transition:all .18s;box-shadow:0 2px 10px rgba(0,0,0,.07);display:flex;flex-direction:column;position:relative;border:2px solid transparent;min-height:180px}
.pc:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(102,126,234,.2);border-color:#667eea}
.pc-img-wrap{height:115px;min-height:115px;background:linear-gradient(135deg,#ece9f0,#e4e0f0);position:relative;overflow:hidden;flex-shrink:0}
.pc-img-wrap img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s}
.pc:hover .pc-img-wrap img{transform:scale(1.07)}
.pc-emoji{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:42px}
.pc-body{padding:8px 10px 10px;background:#fff;flex:1}
.pc-cat{font-size:10px;font-weight:700;color:#667eea;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px}
.pc-name{font-size:12.5px;font-weight:700;color:#1a202c;line-height:1.3;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;margin-bottom:4px}
.pc-price{font-size:14px;font-weight:900;color:#27ae60}
.badge-stk{position:absolute;top:6px;right:6px;font-size:9px;font-weight:700;padding:2px 5px;border-radius:5px;background:#e3f2fd;color:#1565c0}
.badge-stk.low{background:#fff3e0;color:#e65100}
.badge-stk.out{background:#ffebee;color:#c62828}
.badge-sz{position:absolute;top:6px;left:6px;background:rgba(102,126,234,.85);color:#fff;font-size:9px;font-weight:700;padding:2px 5px;border-radius:4px}
.badge-disc{position:absolute;bottom:6px;right:6px;background:#e74c3c;color:#fff;font-size:9px;font-weight:700;padding:2px 5px;border-radius:4px}
.g-load,.g-empty{grid-column:1/-1;text-align:center;padding:40px 20px;color:#bbb;font-size:14px}
.g-load i,.g-empty i{font-size:38px;display:block;margin-bottom:8px;color:#d0d6e8}
.btn-more{grid-column:1/-1;background:#fff;border:1.5px dashed #c5d0f0;border-radius:10px;padding:12px;color:#667eea;font-size:13px;font-weight:600;cursor:pointer;display:none;align-items:center;justify-content:center;gap:8px;transition:.18s;margin-top:4px}
.btn-more:hover{background:#f0f3ff}
/* RIGHT */
.pos-right{width:380px;min-width:300px;background:#fff;display:flex;flex-direction:column;border-left:1px solid #dde2f0;box-shadow:-4px 0 18px rgba(0,0,0,.07)}
.ord-hdr{padding:12px 14px 9px;border-bottom:1.5px solid #f0f2f8;flex-shrink:0}
.ord-hdr h5{font-size:14px;font-weight:800;color:#2d3748;margin:0 0 8px}
.ord-cust{display:flex;gap:7px}
.ord-cust select,.ord-cust input{border:1.5px solid #e0e4ef;border-radius:8px;padding:6px 9px;font-size:12.5px;outline:none;background:#f7f8fc}
.ord-cust select:focus,.ord-cust input:focus{border-color:#667eea;background:#fff}
.ord-list{flex:1;overflow-y:auto;padding:8px 10px;scrollbar-width:thin;scrollbar-color:#d0d6e8 transparent}
.ord-list::-webkit-scrollbar{width:4px}
.ord-list::-webkit-scrollbar-thumb{background:#d0d6e8;border-radius:4px}
.ord-empty{text-align:center;padding:40px 20px;color:#bbb}
.ord-empty i{font-size:36px;display:block;margin-bottom:8px;color:#e0e4ef}
/* item row */
.oi{display:flex;gap:7px;align-items:flex-start;padding:7px 5px;border-radius:9px;transition:.12s;margin-bottom:2px}
.oi:hover{background:#f7f8fc}
.oi-img{width:38px;height:38px;border-radius:8px;overflow:hidden;background:linear-gradient(135deg,#f8f9ff,#eef0fa);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:15px;color:#c5cce0}
.oi-img img{width:100%;height:100%;object-fit:cover}
.oi-info{flex:1;min-width:0}
.oi-name{font-size:12.5px;font-weight:700;color:#2d3748;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.oi-sub{font-size:10.5px;color:#888}
.oi-ctrl{display:flex;align-items:center;gap:4px;margin-top:4px}
.qb{width:22px;height:22px;border-radius:6px;border:1.5px solid #e0e4ef;background:#fff;color:#555;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.14s;line-height:1}
.qb:hover{background:#667eea;border-color:#667eea;color:#fff}
.oi-qty{width:40px;text-align:center;border:1.5px solid #e0e4ef;border-radius:6px;padding:2px 3px;font-size:12px;font-weight:700;outline:none}
.oi-qty:focus{border-color:#667eea}
.oi-price{width:64px;text-align:right;border:1.5px solid #e0e4ef;border-radius:6px;padding:2px 4px;font-size:11.5px;font-weight:600;outline:none;color:#27ae60}
.oi-price:focus{border-color:#27ae60}
.oi-rt{display:flex;flex-direction:column;align-items:flex-end;gap:4px}
.oi-tot{font-size:12.5px;font-weight:800;color:#2d3748;min-width:52px;text-align:right}
.oi-del{width:20px;height:20px;border-radius:50%;border:none;background:#ffebee;color:#e53935;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.13s}
.oi-del:hover{background:#e53935;color:#fff}
/* SUMMARY */
.ord-sum{border-top:1.5px solid #f0f2f8;padding:9px 14px;background:#fafbfe;flex-shrink:0}
.sr{display:flex;justify-content:space-between;align-items:center;font-size:12.5px;color:#666;margin-bottom:5px}
.sr.big{font-size:15px;font-weight:800;color:#2d3748;border-top:1.5px solid #e0e4ef;padding-top:7px;margin-top:3px;margin-bottom:0}
.disc-i{width:76px;border:1.5px solid #e0e4ef;border-radius:7px;padding:2px 6px;font-size:12.5px;text-align:right;outline:none}
.disc-i:focus{border-color:#667eea}
.cash-row{display:flex;gap:7px;margin:7px 0 5px}
.cash-row .cg{flex:1}
.cash-row label{font-size:10.5px;font-weight:600;color:#888;text-transform:uppercase;display:block;margin-bottom:2px}
.cash-row input{width:100%;border:1.5px solid #e0e4ef;border-radius:7px;padding:5px 7px;font-size:13px;font-weight:700;outline:none;text-align:right}
.cash-row input:focus{border-color:#27ae60}
.chng-bar{background:linear-gradient(135deg,#e8f5e9,#f1f8e9);border:1.5px solid #c8e6c9;border-radius:9px;padding:5px 11px;display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
.chng-bar span:first-child{font-size:11.5px;font-weight:700;color:#2e7d32}
.chng-bar span:last-child{font-size:16px;font-weight:900;color:#27ae60}
.words-bar{font-size:11px;color:#888;font-style:italic;padding:4px 14px 2px;border-top:1.5px solid #f0f2f8;text-align:center}
.words-bar strong{color:#555;font-style:normal}
.pos-btns{display:flex;gap:7px;padding:8px 14px 12px;flex-shrink:0}
.btn-sale{flex:2;background:linear-gradient(135deg,#27ae60,#2ecc71);color:#fff;border:none;border-radius:11px;padding:12px;font-size:14px;font-weight:800;cursor:pointer;transition:.18s;box-shadow:0 4px 13px rgba(39,174,96,.32);display:flex;align-items:center;justify-content:center;gap:5px}
.btn-sale:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(39,174,96,.42)}
.btn-book{flex:1;background:linear-gradient(135deg,#f39c12,#f1c40f);color:#fff;border:none;border-radius:11px;padding:12px;font-size:13px;font-weight:700;cursor:pointer;transition:.18s;display:flex;align-items:center;justify-content:center;gap:4px}
.btn-book:hover{transform:translateY(-1px)}
.btn-clr{width:40px;background:#fff;border:1.5px solid #e0e4ef;border-radius:11px;color:#e53935;cursor:pointer;font-size:15px;transition:.18s;display:flex;align-items:center;justify-content:center}
.btn-clr:hover{background:#ffebee;border-color:#e53935}
/* MODAL */
.modal-content{border-radius:16px!important;border:none!important;overflow:hidden}
.mod-hdr{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:16px 20px;display:flex;align-items:center;justify-content:space-between}
.mod-hdr h5{margin:0;font-size:15px;font-weight:700}
.mod-x{width:32px;height:32px;border-radius:50%;border:2px solid rgba(255,255,255,.5);background:rgba(255,255,255,.15);color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;transition:.15s}
.mod-x:hover{background:rgba(255,255,255,.3)}
.sz-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(128px,1fr));gap:10px;margin-bottom:14px}
.szc{border:2px solid #e0e4ef;border-radius:11px;padding:12px 8px;cursor:pointer;text-align:center;transition:.18s;position:relative}
.szc:hover{border-color:#667eea;transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,.15)}
.szc.sel{border-color:#667eea;background:linear-gradient(135deg,#f0f4ff,#e8ecff)}
.szc.sel::after{content:'✓';position:absolute;top:4px;right:7px;color:#667eea;font-weight:900;font-size:13px}
.slabel{font-size:13px;font-weight:700;color:#2d3748}
.sprice{font-size:14px;font-weight:900;color:#27ae60;margin-top:3px}
.sstk{font-size:10px;color:#999;margin-top:2px}
.man-sec{background:#f8f9fe;border:1.5px dashed #c5d0f0;border-radius:11px;padding:12px 14px;margin-bottom:2px}
.man-sec h6{font-size:12.5px;font-weight:700;color:#667eea;margin-bottom:8px}
.man-sec .form-label{font-size:11.5px;font-weight:600;color:#666;margin-bottom:3px}
.man-sec .form-control{font-size:13px;border-radius:8px;border:1.5px solid #e0e4ef}
.man-sec .form-control:focus{border-color:#667eea;box-shadow:none}
.btn-add-o{margin-top:12px;width:100%;background:linear-gradient(135deg,#27ae60,#2ecc71);color:#fff;border:none;border-radius:11px;padding:11px;font-size:14px;font-weight:800;cursor:pointer;transition:.18s;box-shadow:0 4px 12px rgba(39,174,96,.28)}
.btn-add-o:hover{transform:translateY(-1px)}
/* booking modal header */
.bk-hdr{background:linear-gradient(135deg,#f39c12,#f1c40f)}
</style>

<form id="salesForm" action="{{ route('sales.store') }}" method="POST">
@csrf
<input type="hidden" name="advance_payment" id="hAdv" value="0">
<input type="hidden" name="action" id="hAction" value="sale">
<input type="hidden" name="running_sale_id" id="hRunningSaleId" value="">

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 10px;">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin: 10px;">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="pos-wrap">
{{-- ======= LEFT ======= --}}
<div class="pos-left">
    <div class="pos-topbar">
        <div class="pos-brand">🎂 Bin Sultan Sweets</div>
        <div class="srchwrap">
            <i class="la la-search"></i>
            <input type="text" id="posSearch" placeholder="Search products…" autocomplete="off">
        </div>
        <button type="button" class="btn-cls" style="background:#f39c12; color:#fff; border:none; margin-right: 10px;" onclick="showModal('tableModal')"><i class="la la-server"></i> Running Orders</button>
        <a href="{{ route('sale.index') }}" class="btn-cls"><i class="la la-times"></i> Close</a>
    </div>

    <div class="cat-tabs">
        <div class="cat-tab active" data-cat="">🍰 All</div>
        @foreach($categories as $cat)
        <div class="cat-tab" data-cat="{{ $cat->id }}">{{ $cat->name }}</div>
        @endforeach
    </div>

    <div class="prod-grid" id="prodGrid">
        <div class="g-load"><i class="la la-spinner la-spin"></i>Loading products…</div>
    </div>
</div>

{{-- ======= RIGHT ======= --}}
<div class="pos-right">
    <div class="ord-hdr">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <h5 style="margin:0;">🛍️ Current Order</h5>
            <span id="selTableName" style="font-size:12px; font-weight:700; color:#e67e22; display:none;">No Table</span>
        </div>
        
        <div class="ord-cust" style="margin-bottom:8px;">
            <select name="order_type" id="orderTypeSel" style="flex:1" onchange="handleOrderTypeChange()">
                <option value="Walk-in">🚶 Walk-in</option>
                <option value="Takeaway">🥡 Takeaway</option>
                <option value="Dine-in">🍽️ Dine-in</option>
            </select>
        </div>

        <div class="ord-cust">
            <select name="customer" id="custSel" style="flex:2">
                <option value="Walk-in Customer">👤 Walk-in Customer</option>
                @foreach($Customer as $c)
                <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
                @endforeach
            </select>
            <input type="text" name="reference" id="refInput" placeholder="Ref #" style="flex:1;max-width:85px">
        </div>
        <input type="hidden" name="table_id" id="hTableId" value="">
    </div>

    <div class="ord-list" id="ordList">
        <div class="ord-empty" id="ordEmpty">
            <i class="la la-shopping-basket"></i>
            <p style="font-size:13px;margin:0">Tap a product to add it</p>
        </div>
    </div>

    <div id="hFields" style="display:none"></div>

    <div class="ord-sum">
        <div class="sr"><span>Subtotal</span><span id="sSubtotal">Rs 0.00</span></div>
        <div class="sr" style="display:none;"><span>Item Disc.</span><span id="sItemDisc">Rs 0.00</span></div>
        <div class="sr"><span>Extra Disc. (%)</span>
            <input type="number" id="extraDisc" class="disc-i" value="0" min="0" placeholder="%" step="any">
            <input type="hidden" id="hExtraDisc" name="total_extra_cost" value="0">
        </div>
        <div class="sr big"><span>NET TOTAL</span><span id="sNet">Rs 0.00</span></div>
        <div class="cash-row">
            <div class="cg"><label>💵 Cash</label><input type="number" id="cashI" name="cash" placeholder="0" min="0"></div>
            <div class="cg"><label>💳 Card</label><input type="number" id="cardI" name="card" placeholder="0" min="0"></div>
        </div>
        <div class="chng-bar"><span>Change / Balance</span><span id="chngAmt">0.00</span></div>
        <input type="hidden" name="total_subtotal" id="hSub">
        <input type="hidden" name="total_discount" id="hDisc">
        <input type="hidden" name="total_net" id="hNet">
        <input type="hidden" name="change" id="hChng">
        <input type="hidden" name="total_amount_Words" id="hWords">
        <input type="hidden" name="total_items" id="hItems">
        <input type="hidden" name="total_pieces" id="hPieces">
        <input type="hidden" name="total_yard" id="hYard" value="0">
        <input type="hidden" name="total_meter" id="hMeter" value="0">
    </div>

    <div class="words-bar"><strong id="wordsSpan">–</strong></div>

    <div class="pos-btns">
        <button type="button" class="btn-clr" onclick="clearOrder()" title="Clear" style="font-weight:900; font-size:18px;">✖</button>
        <button type="button" class="btn-book" onclick="openBook()" id="btnBook"><i class="la la-bookmark"></i> Book</button>
        <button type="button" class="btn-book" onclick="saveToken()" id="btnSaveToken" style="display:none; background:linear-gradient(135deg, #8e44ad, #9b59b6);"><i class="la la-bell"></i> Save (Token)</button>
        <button type="button" class="btn-sale" onclick="doSale()" id="btnSale"><i class="la la-check-circle"></i> Sale / Bill</button>
    </div>
</div>
</div>
</form>

{{-- ======= SIZE MODAL ======= --}}
<div class="modal fade" id="szModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="mod-hdr">
                <h5 id="szTitle">Select Size</h5>
                <button type="button" class="mod-x" id="szClose">✕</button>
            </div>
            <div class="modal-body">
                <div class="sz-grid" id="szGrid"></div>
                <div class="man-sec">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <h6 style="margin:0; font-size:14px; color:#333;"><i class="la la-edit"></i> Custom / Manual Entry</h6>
                        <div id="kgHelpers" style="display:none; gap:15px; align-items:center;">
                             <div class="d-flex align-items-center gap-2">
                                <span style="font-size:13px; font-weight:700; color:#27ae60">By Grams:</span>
                                <input type="number" id="hGrams" class="form-control" style="width:100px; height:34px; font-size:15px; font-weight:800; border-color:#27ae60" placeholder="e.g. 50">
                             </div>
                             <div class="d-flex align-items-center gap-2">
                                <span style="font-size:13px; font-weight:700; color:#e67e22">By Price:</span>
                                <input type="number" id="hPriceVal" class="form-control" style="width:100px; height:34px; font-size:15px; font-weight:800; border-color:#e67e22" placeholder="e.g. 100">
                             </div>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-4"><label class="form-label">Label</label>
                            <input type="text" id="mLabel" class="form-control" placeholder="e.g. 300g"></div>
                        <div class="col-2"><label class="form-label">Qty</label>
                            <input type="number" id="mQty" class="form-control" value="1" min="0.001" step="any"></div>
                        <div class="col-3"><label class="form-label">Unit Price (Rs)</label>
                            <input type="number" id="mPrice" class="form-control" value="0" min="0" step="any"></div>
                        <div class="col-3"><label class="form-label">Discount</label>
                            <input type="text" id="mDisc" class="form-control" value="0" placeholder="0 or 10%"></div>
                    </div>
                </div>
                <button class="btn-add-o" onclick="addToOrder()"><i class="la la-plus-circle"></i> Add to Order</button>
            </div>
        </div>
    </div>
</div>

{{-- ======= BOOKING MODAL ======= --}}
<div class="modal fade" id="bkModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="mod-hdr bk-hdr">
                <h5>📋 Booking — Advance</h5>
                <button type="button" class="mod-x" id="bkClose">✕</button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label fw-bold">Net Amount</label>
                    <input type="text" id="bkNet" class="form-control" readonly></div>
                <div class="mb-2"><label class="form-label fw-bold">Advance Payment</label>
                    <input type="number" id="bkAdv" class="form-control" value="0" min="0" step="0.01"></div>
                <small class="text-muted">Zero advance bhi theek hai</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" id="bkCancel">Cancel</button>
                <button type="button" class="btn btn-warning btn-sm" onclick="confirmBook()">Confirm Booking</button>
            </div>
        </div>
    </div>
</div>

{{-- ======= TABLE MODAL ======= --}}
<div class="modal fade" id="tableModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="mod-hdr bk-hdr" style="background:linear-gradient(135deg, #2c3e50, #3498db)">
                <h5>🍽️ Select Table</h5>
                <button type="button" class="mod-x" id="tableClose">✕</button>
            </div>
            <div class="modal-body">
                <div class="sz-grid" id="tableGrid">
                    @foreach($tables as $t)
                        <div class="szc @if($t->status == 'occupied') sel @endif" 
                             style="@if($t->status == 'occupied') cursor:pointer; border-color:#e74c3c; background:#ffecec; @else cursor:pointer; @endif"
                             onclick="selectTable({{ $t->id }}, '{{ $t->table_name }}', '{{ $t->status }}')">
                            <div class="slabel">{{ $t->table_name }}</div>
                            <div class="sstk" style="@if($t->status == 'occupied') color:#e74c3c; font-weight:bold; @endif">{{ ucfirst($t->status) }}</div>
                        </div>
                    @endforeach
                </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
const POS_URL     = "{{ route('pos.products') }}";
const VAR_URL     = "{{ url('/pos/product-variants') }}";

let cart     = [];
let curProd  = null;
let selVarI  = null;
let curPage  = 1, lastPage = 1, curQ = '', curCat = '', loading = false;
let szModalI = null, bkModalI = null, tbModalI = null;

/* ---- MODAL SETUP (manual show/hide to avoid BS close btn issues) ---- */
function showModal(id) {
    const el = document.getElementById(id);
    el.classList.add('show');
    el.style.display = 'block';
    document.body.classList.add('modal-open');
    let bd = document.getElementById('modBackdrop');
    if (!bd) { bd = document.createElement('div'); bd.id='modBackdrop'; bd.className='modal-backdrop fade show'; document.body.appendChild(bd); }
}
function hideModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('show');
    el.style.display = 'none';
    if (!document.querySelector('.modal.show')) {
        document.body.classList.remove('modal-open');
        document.getElementById('modBackdrop')?.remove();
    }
}

document.getElementById('szClose').onclick = () => hideModal('szModal');
document.getElementById('bkClose').onclick = () => hideModal('bkModal');
document.getElementById('bkCancel').onclick = () => hideModal('bkModal');
document.getElementById('tableClose').onclick = () => hideModal('tableModal');

document.getElementById('szModal').addEventListener('click', function(e){ if(e.target===this) hideModal('szModal'); });
document.getElementById('bkModal').addEventListener('click', function(e){ if(e.target===this) hideModal('bkModal'); });
document.getElementById('tableModal').addEventListener('click', function(e){ if(e.target===this) hideModal('tableModal'); });

/* ---- SPECIFIC FUNCS FOR ORDER TYPE ---- */
function handleOrderTypeChange() {
    let orderType = document.getElementById('orderTypeSel').value;
    if (orderType === 'Dine-in') {
        showModal('tableModal');
        document.getElementById('btnSaveToken').style.display = 'flex';
        document.getElementById('btnBook').style.display = 'none';
        document.getElementById('btnSale').innerHTML = '<i class="la la-check-circle"></i> Pay & Bill';
    } else {
        document.getElementById('hTableId').value = '';
        document.getElementById('selTableName').style.display = 'none';
        document.getElementById('btnSaveToken').style.display = 'none';
        document.getElementById('btnBook').style.display = 'flex';
        document.getElementById('btnSale').innerHTML = '<i class="la la-check-circle"></i> Sale / Bill';
    }
}

function selectTable(id, name, status) {
    if (status === 'occupied') {
        fetchActiveOrder(id, name);
    } else {
        document.getElementById('hTableId').value = id;
        document.getElementById('selTableName').innerText = 'Table: ' + name;
        document.getElementById('selTableName').style.display = 'inline-block';
        document.getElementById('hRunningSaleId').value = ''; 
        clearOrder(false); // clear cart without prompting
        hideModal('tableModal');
    }
}

async function fetchActiveOrder(id, name) {
    try {
        Swal.fire({ title: 'Loading...', text: 'Fetching order details', allowOutsideClick: false, showConfirmButton: false });
        Swal.showLoading();
        
        const res = await fetch(`/pos/table-active-sale/${id}`);
        const data = await res.json();
        
        Swal.close();
        if (data.success) {
            cart = [];
            data.items.forEach(item => {
                const unique_id = item.prod_id + "_" + (item.var_id || "");
                cart.push({
                    unique_id: unique_id,
                    id: item.prod_id,
                    variantId: item.var_id || null,
                    name: item.name,
                    code: item.code,
                    brand: item.brand,
                    unitId: item.unit,
                    price: parseFloat(item.price),
                    label: item.label || '', 
                    qty: parseFloat(item.qty),
                    old_qty: parseFloat(item.old_qty),
                    disc: parseFloat(item.disc)
                });
            });
            document.getElementById('hRunningSaleId').value = data.sale_id;
            document.getElementById('hTableId').value = id;
            document.getElementById('selTableName').innerText = 'Table: ' + name + ' (Running)';
            document.getElementById('selTableName').style.display = 'inline-block';
            document.getElementById('orderTypeSel').value = 'Dine-in';
            document.getElementById('custSel').value = data.customer;
            document.getElementById('refInput').value = data.reference || '';
            
            handleOrderTypeChange(); 
            renderCart();
            recalc();
            hideModal('tableModal');
            toast('Order loaded', 'success');
        } else {
            toast('Failed: ' + data.message, 'error');
        }
    } catch (err) {
        Swal.close();
        console.error('Fetch error:', err);
        toast('Error fetching active order', 'error');
    }
}

function saveToken() {
    if (submitting) return;
    if (!cart.length) { toast('Koi product nahi add kiya'); return; }
    if (!document.getElementById('hTableId').value) { toast('Table select karein!'); showModal('tableModal'); return; }
    buildFields();
    document.getElementById('hAction').value = 'save_token'; // We will handle this in SaleController
    submitting = true;
    document.getElementById('salesForm').submit();
}


/* ---- FETCH PRODUCTS ---- */
function loadProds(reset=false) {
    if (loading) return;
    if (!reset && curPage > lastPage) return;
    if (reset) { curPage=1; lastPage=1; }
    loading = true;

    const grid = document.getElementById('prodGrid');

    if (reset) {
        grid.innerHTML = '<div class="g-load"><i class="la la-spinner la-spin" style="font-size:40px;display:block;margin-bottom:8px;color:#d0d6e8"></i>Loading products…</div>';
    } else {
        // Update button to show loading state
        const btn = document.getElementById('loadMore');
        if (btn) { btn.disabled=true; btn.innerHTML='<i class="la la-spinner la-spin"></i> Loading…'; }
    }

    const params = new URLSearchParams({q: curQ, page: curPage, per_page: 60});
    if (curCat !== '') params.set('category_id', curCat);

    fetch(`${POS_URL}?${params}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
    .then(r => {
        if (!r.ok) throw new Error('Server error: '+r.status);
        return r.json();
    })
    .then(json => {
        curPage  = json.current_page + 1;
        lastPage = json.last_page;
        const total   = json.total;
        const loaded  = Math.min(json.current_page * 60, total);

        if (reset) grid.innerHTML = '';

        if (!json.data.length && reset) {
            grid.innerHTML = '<div class="g-empty"><i class="la la-frown-open"></i>No products found</div>';
            loading = false;
            return;
        }

        // Append cards
        const frag = document.createDocumentFragment();
        json.data.forEach(prod => frag.appendChild(makeCard(prod)));
        // Remove old load-more button before appending
        document.getElementById('loadMore')?.remove();
        document.getElementById('posCounter')?.remove();
        grid.appendChild(frag);

        // Counter label
        const counter = document.createElement('div');
        counter.id = 'posCounter';
        counter.style.cssText = 'grid-column:1/-1;text-align:center;font-size:12px;color:#999;padding:4px 0';
        counter.textContent = 'Showing ' + loaded + ' of ' + total + ' products';
        grid.appendChild(counter);

        // Load more button
        if (curPage <= lastPage) {
            const remaining = total - loaded;
            const btn = document.createElement('button');
            btn.type = 'button'; btn.id = 'loadMore'; btn.className = 'btn-more';
            btn.style.display = 'flex';
            const ico  = document.createElement('i'); ico.className = 'la la-chevron-down';
            const span = document.createElement('span');
            span.style.cssText = 'background:rgba(102,126,234,.15);color:#667eea;font-size:11px;padding:2px 8px;border-radius:20px;font-weight:700';
            span.textContent = remaining.toLocaleString() + ' more';
            btn.appendChild(ico);
            btn.appendChild(document.createTextNode('\u00a0 Load More \u00a0'));
            btn.appendChild(span);
            btn.addEventListener('click', function() { if (!loading) loadProds(false); });
            grid.appendChild(btn);
        }

        loading = false;
    })
    .catch(e => {
        console.error('POS load error:', e);
        loading = false;
        if (reset) {
            document.getElementById('prodGrid').innerHTML = '<div class="g-empty"><i class="la la-exclamation-triangle"></i>Error loading: '+e.message+'</div>';
        } else {
            const btn = document.getElementById('loadMore');
            if (btn) { btn.disabled=false; btn.innerHTML='<i class="la la-chevron-down"></i> Retry Load More'; }
        }
    });
}

function makeCard(p) {
    const div = document.createElement('div');
    div.className = 'pc';

    const isKg   = p.unit_type === 'kg';
    const lowTh  = isKg ? 5000 : 5;
    const stk    = p.stock <= 0 ? 'out' : (p.stock <= lowTh ? 'low' : '');
    const stkTxt = p.stock <= 0 ? 'Out' : ('Stk:' + fmtStk(p.stock, p.unit_type));

    // Build image HTML using concat (no nested backticks)
    let imgHtml = '';
    if (p.image) {
        imgHtml = '<img src="' + p.image + '" alt="" loading="lazy" '
                + 'onerror="this.style.display=\'none\';this.nextSibling.style.display=\'flex\'">';
    }

    const emojiStyle = p.image ? 'display:none' : 'display:flex';
    const szBadge    = p.has_variants ? '<span class="badge-sz">Sizes</span>' : '';
    const discBadge  = p.has_discount ? '<span class="badge-disc">' + p.discount_percent + '% off</span>' : '';
    const priceLine  = p.has_discount
        ? '<s style="color:#aaa;font-size:11px;margin-right:3px">Rs ' + fmt(p.original_price) + '</s> Rs ' + fmt(p.price)
        : 'Rs ' + fmt(p.price);
    const catName = (p.category || '').toString().replace(/</g,'&lt;');
    const prodName = (p.item_name || '').toString().replace(/</g,'&lt;');

    div.innerHTML =
        '<div class="pc-img-wrap">'
        +   imgHtml
        +   '<div class="pc-emoji" style="' + emojiStyle + '">🧁</div>'
        +   '<span class="badge-stk ' + stk + '">' + stkTxt + '</span>'
        +   szBadge
        +   discBadge
        + '</div>'
        + '<div class="pc-body">'
        +   '<div class="pc-cat">' + catName + '</div>'
        +   '<div class="pc-name">' + prodName + '</div>'
        +   '<div class="pc-price">' + priceLine + '</div>'
        + '</div>';

    div.addEventListener('click', function() { handleClick(p); });
    return div;
}

let srchT;
document.getElementById('posSearch').addEventListener('input', function() {
    clearTimeout(srchT);
    curQ = this.value.trim();
    srchT = setTimeout(function() { loadProds(true); }, 350);
});

document.querySelectorAll('.cat-tab').forEach(function(t) {
    t.addEventListener('click', function() {
        document.querySelectorAll('.cat-tab').forEach(function(x) { x.classList.remove('active'); });
        this.classList.add('active');
        // dataset.cat is '' for All, or a numeric id for specific category
        curCat = (this.dataset.cat !== undefined) ? this.dataset.cat : '';
        loadProds(true);
    });
});

loadProds(true);


/* ---- CLICK HANDLER (allow out of stock) ---- */
function handleClick(p) {
    curProd=p; selVarI=null;
    if (p.has_variants) openSzModal(p);
    else openSingleModal(p);
}

/* ---- SIZE MODAL ---- */
function openSzModal(p) {
    document.getElementById('szTitle').textContent='📦 Size — '+p.item_name;
    document.getElementById('szGrid').innerHTML='<div class="text-center py-3 text-muted"><i class="la la-spinner la-spin" style="font-size:24px"></i></div>';
    document.getElementById('mLabel').value=''; document.getElementById('mQty').value=1;
    document.getElementById('mPrice').value=p.price; document.getElementById('mDisc').value=0;
    showModal('szModal');
    
    // KG Helpers logic
    const kgHelpers = document.getElementById('kgHelpers');
    const hGrams = document.getElementById('hGrams');
    const hPriceVal = document.getElementById('hPriceVal');
    const mQty = document.getElementById('mQty');
    const mPrice = document.getElementById('mPrice');
    const mLabel = document.getElementById('mLabel');

    hGrams.value = ''; hPriceVal.value = '';
    let originalLabel = mLabel.value; // Store the initial label (product name or selected variant)

    if (p.unit_type === 'kg') {
        kgHelpers.style.display = 'flex';
        
        hGrams.oninput = function() {
            let g = parseFloat(this.value) || 0;
            
            let vPrice = parseFloat(p.price) || 0;
            let variantKgSize = 1;

            if (selVarI !== null && p.variants && p.variants[selVarI]) {
                let v = p.variants[selVarI];
                vPrice = parseFloat(v.price) || 0;
                let sVal = parseFloat(v.size_value) || 0;
                let sUnit = (v.size_unit || '').toLowerCase();
                
                if (sVal > 0) {
                    if (sUnit === 'kg') {
                        variantKgSize = sVal;
                    } else if (sUnit === 'g' || sUnit === 'gm' || sUnit === 'gram' || sUnit === 'grams') {
                        variantKgSize = sVal / 1000;
                    } else if (sUnit === 'pound') {
                         variantKgSize = sVal * 0.453592;
                    }
                }
            }
            
            if (g > 0) {
                let targetKg = g / 1000;
                let qtyVal = targetKg / variantKgSize;
                
                mQty.value = qtyVal.toFixed(4);
                
                let varName = (selVarI !== null && p.variants && p.variants[selVarI]) ? (p.variants[selVarI].size_label || p.variants[selVarI].name || '') : '';
                let labelPrefix = varName ? (varName + ' ') : '';
                mLabel.value = labelPrefix + g + 'g';
                
                if (vPrice > 0) {
                    hPriceVal.value = Math.round(qtyVal * vPrice);
                }
                mPrice.value = vPrice;
            } else {
                mQty.value = 1;
                mLabel.value = originalLabel;
                hPriceVal.value = '';
                mPrice.value = vPrice;
            }
        };

        hPriceVal.oninput = function() {
            let pVal = parseFloat(this.value) || 0;
            
            let vPrice = parseFloat(p.price) || 0;
            let variantKgSize = 1;

            if (selVarI !== null && p.variants && p.variants[selVarI]) {
                let v = p.variants[selVarI];
                vPrice = parseFloat(v.price) || 0;
                let sVal = parseFloat(v.size_value) || 0;
                let sUnit = (v.size_unit || '').toLowerCase();
                
                if (sVal > 0) {
                    if (sUnit === 'kg') {
                        variantKgSize = sVal;
                    } else if (sUnit === 'g' || sUnit === 'gm' || sUnit === 'gram' || sUnit === 'grams') {
                        variantKgSize = sVal / 1000;
                    } else if (sUnit === 'pound') {
                         variantKgSize = sVal * 0.453592;
                    }
                }
            }
            
            if (pVal > 0 && vPrice > 0) {
                let calculatedQty = pVal / vPrice; // number of packages
                mQty.value = calculatedQty.toFixed(5); 
                
                let targetKg = calculatedQty * variantKgSize;
                let g = Math.round(targetKg * 1000);
                
                let varName = (selVarI !== null && p.variants && p.variants[selVarI]) ? (p.variants[selVarI].size_label || p.variants[selVarI].name || '') : '';
                let labelPrefix = varName ? (varName + ' ') : '';
                mLabel.value = labelPrefix + g + 'g (' + pVal + ' Rs)';
                
                hGrams.value = g;
                mPrice.value = vPrice;
            } else if (pVal === 0) {
                mQty.value = 1;
                mLabel.value = originalLabel;
                hGrams.value = '';
                mPrice.value = vPrice;
            }
        };
    } else {
        kgHelpers.style.display = 'none';
    }

    // Update originalLabel when a variant is selected
    window.updateManualLabel = function(newLabel) {
        originalLabel = newLabel;
        if (!hGrams.value && !hPriceVal.value) {
            mLabel.value = newLabel;
        }
    };

    fetch(`${VAR_URL}/${p.id}`,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(data=>{ 
        curProd.variants = data.variants; 
        renderSizes(data.variants); 
        document.getElementById('szTitle').innerHTML = '📦 Size — ' + data.item_name + ' <small class="badge bg-dark ms-2" style="font-size:11px">Avail: ' + fmtStk(data.total_stock, data.unit_type) + '</small>';
    })
    .catch(()=>{ document.getElementById('szGrid').innerHTML='<p class="text-danger">Size load failed.</p>'; });
}

function renderSizes(variants) {
    const g = document.getElementById('szGrid');
    g.innerHTML = '';
    if (!variants || !variants.length) {
        g.innerHTML = '<p class="text-muted p-3">Koi size nahi mili.</p>';
        return;
    }
    let defaultSet = false;
    variants.forEach(function(v, i) {
        const c = document.createElement('div');
        c.className = 'szc' + (v.is_default ? ' sel' : '');
        const stkTxt = v.stock > 0 ? ('Stk:' + fmtStk(v.stock, curProd.unit_type)) : 'Low/Out';
        c.innerHTML =
            '<div class="slabel">' + (v.size_label || v.name || '') + '</div>'
            + '<div class="sprice">Rs ' + fmt(v.price) + '</div>'
            + '<div class="sstk">' + stkTxt + '</div>';

        // IMPORTANT: store index and variant on element for reliable access
        c._varIdx = i;
        c._var    = v;

        c.addEventListener('click', function() {
            selectSz(this, this._varIdx, this._var);
        });
        g.appendChild(c);

        if (v.is_default && !defaultSet) {
            defaultSet = true;
            selVarI = i;
            document.getElementById('mPrice').value = v.price;
            document.getElementById('mLabel').value = v.size_label || v.name || '';
        }
    });
    // If no default, auto-select first
    if (!defaultSet && variants.length) {
        selVarI = 0;
        g.firstElementChild.classList.add('sel');
        document.getElementById('mPrice').value = variants[0].price;
        document.getElementById('mLabel').value = variants[0].size_label || variants[0].name || '';
    }
}

function selectSz(el, i, v) {
    // Remove 'sel' from all size cards
    document.querySelectorAll('#szGrid .szc').forEach(function(c) { c.classList.remove('sel'); });
    el.classList.add('sel');
    selVarI = i;
    document.getElementById('mPrice').value = v.price;
    const newLabel = v.size_label || v.name || '';
    document.getElementById('mLabel').value = newLabel;
    if (typeof window.updateManualLabel === 'function') window.updateManualLabel(newLabel);
    // Flash the price field to show it updated
    const pf = document.getElementById('mPrice');
    pf.style.background = '#e8f5e9';
    setTimeout(function() { pf.style.background = ''; }, 400);

    // Re-trigger calculation if grams or price is filled
    const hGrams = document.getElementById('hGrams');
    if (hGrams && hGrams.value) {
        hGrams.dispatchEvent(new Event('input'));
    } else {
        const hPriceVal = document.getElementById('hPriceVal');
        if (hPriceVal && hPriceVal.value) {
            hPriceVal.dispatchEvent(new Event('input'));
        }
    }
}

function openSingleModal(p) {
    document.getElementById('szTitle').textContent='➕ Add — '+p.item_name;
    document.getElementById('szGrid').innerHTML='';
    document.getElementById('mLabel').value=p.item_name; document.getElementById('mQty').value=1;
    document.getElementById('mPrice').value=p.price; document.getElementById('mDisc').value=0;
    showModal('szModal');
}

/* ---- ADD TO ORDER ---- */
function addToOrder() {
    const p = curProd;
    if (!p) return;

    let label = (document.getElementById('mLabel').value || '').trim();
    let qty   = parseFloat(document.getElementById('mQty').value) || 0;
    let price = parseFloat(document.getElementById('mPrice').value) || 0;
    let disc  = (document.getElementById('mDisc').value || '0').trim();

    // Fallback label
    if (!label) {
        if (selVarI !== null && p.variants && p.variants[selVarI]) {
            label = p.variants[selVarI].size_label || p.variants[selVarI].name || p.item_name;
        } else {
            label = p.item_name;
        }
    }

    if (qty <= 0)   { toast('Qty enter karein'); return; }
    if (price <= 0) { toast('Price enter karein'); return; }

    // Close modal FIRST (user sees immediate feedback)
    hideModal('szModal');

    // Unique cart key = product_id + ':' + label (so different sizes are separate rows)
    const cartKey = p.id + ':' + label;
    const idx = cart.findIndex(function(i) { return i.id == p.id && i.label === label; });

    if (idx >= 0) {
        // Same product + same size → update qty
        cart[idx].qty   += qty;
        cart[idx].price  = price;
        cart[idx].disc   = disc;
    } else {
        // New product OR different size → new row
        cart.push({
            id:     p.id,
            name:   p.item_name,
            code:   p.item_code,
            price:  price,
            qty:    qty,
            disc:   disc,
            note:   p.note || '',
            image:  p.image || '',
            unitId: p.unit_id || '',
            label:  label,
            variantId: (selVarI !== null && p.variants && p.variants[selVarI]) ? p.variants[selVarI].id : null
        });
    }

    renderCart();
    recalc();

    // Flash order panel
    const ol = document.getElementById('ordList');
    ol.style.transition = 'none';
    ol.style.background = '#e8f5e9';
    setTimeout(function() { ol.style.transition = 'background .6s'; ol.style.background = ''; }, 100);
}

/* ---- CART ---- */
function renderCart() {
    const list  = document.getElementById('ordList');

    // Always fetch fresh reference — but keep it alive by detaching first
    let empty = document.getElementById('ordEmpty');
    if (!empty) {
        // Re-create if lost (safety net)
        empty = document.createElement('div');
        empty.id = 'ordEmpty'; empty.className = 'ord-empty';
        empty.innerHTML = '<i class="la la-shopping-basket"></i><p style="font-size:13px;margin:0">Tap a product to add it</p>';
    }

    // Detach ordEmpty from DOM BEFORE clearing (keeps element reference alive)
    if (empty.parentNode) empty.parentNode.removeChild(empty);

    if (!cart.length) {
        list.innerHTML = '';
        empty.style.display = '';
        list.appendChild(empty);
        return;
    }

    // Build new cart rows
    list.innerHTML = '';

    cart.forEach(function(item, idx) {
        const tot  = rowTotal(item);
        const div  = document.createElement('div');
        div.className = 'oi';

        // Image cell
        const imgCell = document.createElement('div');
        imgCell.className = 'oi-img';
        if (item.image) {
            const img = document.createElement('img');
            img.src = item.image; img.alt = '';
            img.onerror = function() { this.parentElement.textContent = '\uD83E\uDDE1'; };
            imgCell.appendChild(img);
        } else { imgCell.textContent = '🧁'; }

        // Info cell
        const infoCell = document.createElement('div');
        infoCell.className = 'oi-info';

        const nameDiv = document.createElement('div');
        nameDiv.className = 'oi-name'; nameDiv.title = item.name;
        nameDiv.textContent = item.name;

        const subDiv = document.createElement('div');
        subDiv.className = 'oi-sub';
        subDiv.textContent = (item.label && item.label !== item.name) ? ('📦 ' + item.label) : '';

        const ctrlDiv = document.createElement('div');
        ctrlDiv.className = 'oi-ctrl';

        const btnMinus = document.createElement('button');
        btnMinus.type = 'button'; btnMinus.className = 'qb'; btnMinus.textContent = '−';
        btnMinus.setAttribute('data-idx', idx);
        btnMinus.addEventListener('click', function() { chQ(parseInt(this.dataset.idx), -1); });

        const qtyInput = document.createElement('input');
        qtyInput.type = 'number'; qtyInput.className = 'oi-qty';
        // Clean display of qty
        qtyInput.value = parseFloat(parseFloat(item.qty).toFixed(4)); 
        qtyInput.min = '0.001'; qtyInput.step = 'any';
        qtyInput.setAttribute('data-idx', idx);
        qtyInput.addEventListener('change', function() { setQ(parseInt(this.dataset.idx), this.value); });

        const btnPlus = document.createElement('button');
        btnPlus.type = 'button'; btnPlus.className = 'qb'; btnPlus.textContent = '+';
        btnPlus.setAttribute('data-idx', idx);
        btnPlus.addEventListener('click', function() { chQ(parseInt(this.dataset.idx), 1); });

        const priceInput = document.createElement('input');
        priceInput.type = 'number'; priceInput.className = 'oi-price';
        priceInput.value = item.price; priceInput.min = '0'; priceInput.step = 'any';
        priceInput.setAttribute('data-idx', idx);
        priceInput.addEventListener('change', function() { setP(parseInt(this.dataset.idx), this.value); });

        ctrlDiv.appendChild(btnMinus); ctrlDiv.appendChild(qtyInput);
        ctrlDiv.appendChild(btnPlus);  ctrlDiv.appendChild(priceInput);

        infoCell.appendChild(nameDiv); infoCell.appendChild(subDiv); infoCell.appendChild(ctrlDiv);

        // Right cell
        const rtCell = document.createElement('div');
        rtCell.className = 'oi-rt';

        const totDiv = document.createElement('div');
        totDiv.className = 'oi-tot';
        totDiv.textContent = 'Rs ' + fmt(tot);

        const delBtn = document.createElement('button');
        delBtn.type = 'button'; delBtn.className = 'oi-del'; delBtn.textContent = '×';
        delBtn.setAttribute('data-idx', idx);
        delBtn.addEventListener('click', function() { delI(parseInt(this.dataset.idx)); });

        rtCell.appendChild(totDiv); rtCell.appendChild(delBtn);

        div.appendChild(imgCell); div.appendChild(infoCell); div.appendChild(rtCell);
        list.appendChild(div);
    });

    // Re-attach ordEmpty (hidden) so getElementById finds it next time
    empty.style.display = 'none';
    list.appendChild(empty);
}



function rowTotal(item){
    let d=0; const dr=(item.disc||'').toString().trim();
    if (dr.endsWith('%')) d=item.price*(parseFloat(dr)/100)*item.qty;
    else d=(parseFloat(dr)||0)*item.qty;
    return Math.max(0,item.qty*item.price-d);
}
function chQ(i,d){ cart[i].qty=Math.max(0.01,(cart[i].qty||0)+d); renderCart(); recalc(); }
function setQ(i,v){ cart[i].qty=Math.max(0.01,parseFloat(v)||0.01); recalc(); }
function setP(i,v){ cart[i].price=parseFloat(v)||0; renderCart(); recalc(); }
function delI(i){ cart.splice(i,1); renderCart(); recalc(); }
function clearOrder(prompt = true){
    if (!cart.length) return;
    if (!prompt) { cart=[]; renderCart(); recalc(); return; }
    Swal.fire({title:'Clear order?',icon:'question',showCancelButton:true,confirmButtonText:'Clear',cancelButtonText:'No',confirmButtonColor:'#e53935'})
    .then(r=>{ if(r.isConfirmed){ cart=[]; document.getElementById('hRunningSaleId').value = ''; renderCart(); recalc(); } });
}

/* ---- RECALC ---- */
function recalc(){
    let sub=0,iDisc=0,pcs=0;
    cart.forEach(item=>{ const r=item.qty*item.price,t=rowTotal(item); sub+=t; iDisc+=r-t; pcs+=item.qty; });
    const ex_pct=parseFloat(document.getElementById('extraDisc').value)||0;
    const ex = sub * (ex_pct / 100);
    if(document.getElementById('hExtraDisc')) document.getElementById('hExtraDisc').value = ex.toFixed(2);
    const cs=parseFloat(document.getElementById('cashI').value)||0;
    const cd=parseFloat(document.getElementById('cardI').value)||0;
    const net=Math.max(0,sub-ex), chng=(cs+cd)-net;
    document.getElementById('sSubtotal').textContent='Rs '+sub.toFixed(2);
    document.getElementById('sItemDisc').textContent='Rs '+iDisc.toFixed(2);
    document.getElementById('sNet').textContent='Rs '+net.toFixed(2);
    document.getElementById('chngAmt').textContent=chng.toFixed(2);
    document.getElementById('hSub').value=sub.toFixed(2);
    document.getElementById('hDisc').value=(iDisc+ex).toFixed(2);
    document.getElementById('hNet').value=net.toFixed(2);
    document.getElementById('hChng').value=chng.toFixed(2);
    document.getElementById('hItems').value=pcs;
    document.getElementById('hPieces').value=pcs;
    const w=n2w(Math.round(net));
    document.getElementById('wordsSpan').textContent=w||'–';
    document.getElementById('hWords').value=w;
}
document.getElementById('extraDisc').addEventListener('input',recalc);
document.getElementById('cashI').addEventListener('input',recalc);
document.getElementById('cardI').addEventListener('input',recalc);

/* ---- SUBMIT ---- */
function buildFields(){
    const c=document.getElementById('hFields'); c.innerHTML='';
    const f=(n,v)=>{ const i=document.createElement('input'); i.type='hidden'; i.name=n; i.value=v; c.appendChild(i); };
    cart.forEach(item=>{ 
        f('product_id[]',item.id); 
        f('variant_id[]',item.variantId || ''); 
        f('item_code[]',item.code); 
        f('uom[]',''); 
        f('unit[]',item.unitId||''); 
        f('price[]',item.price); 
        f('item_disc[]',item.disc||0); 
        f('qty[]',item.qty); 
        f('total[]',rowTotal(item).toFixed(2)); 
        f('color[]',item.label); 
    });
}
let submitting=false;
function doSale(){
    if (submitting) return;
    if (!cart.length){ toast('Koi product nahi add kiya'); return; }

    const net = parseFloat(document.getElementById('hNet').value) || 0;
    const cash = parseFloat(document.getElementById('cashI').value) || 0;
    const card = parseFloat(document.getElementById('cardI').value) || 0;
    const totalPaid = cash + card;

    if (totalPaid < net && net > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Incomplete Payment',
            text: 'Kindly pay full payment!',
            confirmButtonColor: '#e53935'
        });
        return;
    }

    buildFields(); document.getElementById('hAction').value='sale'; submitting=true;
    document.getElementById('salesForm').submit();
}
function openBook(){
    if (!cart.length){ toast('Koi product nahi add kiya'); return; }
    const net=parseFloat(document.getElementById('hNet').value)||0;
    document.getElementById('bkNet').value=net.toFixed(2);
    document.getElementById('bkAdv').value=0;
    showModal('bkModal');
}
function confirmBook(){
    const adv=parseFloat(document.getElementById('bkAdv').value)||0;
    document.getElementById('hAdv').value=adv.toFixed(2);
    buildFields(); document.getElementById('hAction').value='booking'; submitting=true;
    hideModal('bkModal');
    document.getElementById('salesForm').submit();
}

/* ---- UTILS ---- */
function fmt(n){ return (parseFloat(n)||0).toLocaleString('en-PK',{minimumFractionDigits:0,maximumFractionDigits:2}); }
function fmtStk(n, u){
    n = parseFloat(n) || 0;
    if (u === 'kg') return (n / 1000).toFixed(2) + ' KG';
    return n.toLocaleString();
}
function toast(msg){ Swal.fire({toast:true,position:'top-end',icon:'warning',title:msg,showConfirmButton:false,timer:1800}); }
function n2w(n){
    if(!n||n<=0)return'';
    const a=['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    const b=['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    n=Math.floor(n); if(n>999999999)return'';
    const m=('000000000'+n).slice(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{3})$/); if(!m)return'';
    let s='';
    s+= +m[1]?(a[+m[1]]||(b[m[1][0]]+' '+a[m[1][1]]))+' Crore ':'';
    s+= +m[2]?(a[+m[2]]||(b[m[2][0]]+' '+a[m[2][1]]))+' Lakh ':'';
    s+= +m[3]?(a[+m[3]]||(b[m[3][0]]+' '+a[m[3][1]]))+' Thousand ':'';
    s+= +m[4]?(a[+m[4]]||(b[m[4][0]]+' '+a[m[4][1]])) :'';
    return s.trim()+' Rupees Only';
}
document.addEventListener('keydown',e=>{ if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();doSale();} });
recalc();

// Set proper heights dynamically based on actual layout
function fixHeights() {
    const posWrap = document.querySelector('.pos-wrap');
    const posGrid = document.getElementById('prodGrid');
    const posLeft = document.querySelector('.pos-left');
    const posRight = document.querySelector('.pos-right');
    if (!posWrap) return;

    // Calculate available height: full viewport minus pos-wrap's top offset
    const top = posWrap.getBoundingClientRect().top;
    const h   = window.innerHeight - top - 5;
    posWrap.style.height  = h + 'px';
    posRight.style.height = h + 'px';

    // Grid height = pos-left height minus topbar minus cats minus padding
    const topbar = posLeft.querySelector('.pos-topbar');
    const cats   = posLeft.querySelector('.cat-tabs');
    const used   = (topbar ? topbar.offsetHeight : 50) + (cats ? cats.offsetHeight : 40) + 30;
    posGrid.style.height = (h - used) + 'px';
}
fixHeights();
window.addEventListener('resize', fixHeights);
</script>
@endsection