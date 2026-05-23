@extends('admin_panel.layout.app')
@section('content')
<style>
/* ========== SHARED STYLES ========== */
.isr-wrap { padding: 20px; font-family: 'Segoe UI', sans-serif; }

/* Tab Switcher */
.rpt-tabs { display:flex; gap:0; margin-bottom:18px; background:#fff; border-radius:12px; padding:5px; box-shadow:0 2px 10px rgba(0,0,0,.07); width:fit-content; }
.rpt-tab  { padding:9px 22px; border-radius:9px; font-weight:700; font-size:13px; cursor:pointer; color:#888; transition:.15s; border:none; background:transparent; }
.rpt-tab.active { background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; box-shadow:0 3px 10px rgba(102,126,234,.4); }

/* Summary Cards */
.isr-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(155px,1fr)); gap:12px; margin-bottom:18px; }
.isr-card  { background:#fff; border-radius:12px; padding:14px 16px; box-shadow:0 2px 10px rgba(0,0,0,.07); border-left:4px solid #667eea; }
.isr-card.green  { border-color:#27ae60; }
.isr-card.orange { border-color:#e67e22; }
.isr-card.red    { border-color:#e74c3c; }
.isr-card.blue   { border-color:#2980b9; }
.isr-card .lbl   { font-size:11px; color:#888; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.isr-card .val   { font-size:22px; font-weight:800; color:#2d3748; margin-top:4px; }

/* Filter Bar */
.isr-filter { background:#fff; border-radius:12px; padding:16px 20px; box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:16px; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.isr-filter .fg { display:flex; flex-direction:column; gap:4px; }
.isr-filter label { font-size:12px; font-weight:700; color:#555; text-transform:uppercase; letter-spacing:.4px; }
.isr-filter input, .isr-filter select { border:1.5px solid #dde2f0; border-radius:8px; padding:7px 12px; font-size:13px; outline:none; transition:border .2s; }
.isr-filter input:focus, .isr-filter select:focus { border-color:#667eea; }
.isr-filter .fg-wide { flex:1; min-width:200px; }
.btn-srch { background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; border:none; border-radius:8px; padding:8px 22px; font-weight:700; cursor:pointer; transition:.18s; font-size:13px; white-space:nowrap; }
.btn-srch:hover { opacity:.88; transform:translateY(-1px); }
.btn-csv  { background:#fff; color:#555; border:1.5px solid #dde2f0; border-radius:8px; padding:8px 16px; font-weight:600; cursor:pointer; font-size:13px; transition:.18s; white-space:nowrap; }
.btn-csv:hover  { border-color:#667eea; color:#667eea; }

/* Live search box */
.ls-wrap { position:relative; }
.ls-wrap input { padding-left:34px; width:100%; min-width:220px; border:1.5px solid #dde2f0; border-radius:8px; padding:7px 12px 7px 34px; font-size:13px; outline:none; }
.ls-wrap input:focus { border-color:#667eea; }
.ls-wrap .ls-ico { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#aaa; font-size:15px; pointer-events:none; }

/* Table Card */
.isr-table-wrap { background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.07); overflow:hidden; }
.isr-table-header { display:flex; justify-content:space-between; align-items:center; padding:14px 18px; border-bottom:1px solid #f0f2f7; flex-wrap:wrap; gap:10px; }
.isr-table-header h6 { margin:0; font-weight:700; color:#2d3748; }
.isr-count { font-size:12px; color:#888; }

/* Item Stock Table */
table.ist { width:100%; border-collapse:collapse; font-size:13px; }
table.ist thead th { background:#f8f9fc; padding:10px 12px; text-align:left; font-weight:700; color:#555; font-size:11px; text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid #edf0f7; white-space:nowrap; position:sticky; top:0; z-index:2; }
table.ist thead th.num { text-align:right; }
table.ist tbody tr { border-bottom:1px solid #f5f5f5; transition:background .12s; }
table.ist tbody tr:hover { background:#fafbff; }
table.ist td { padding:9px 12px; color:#3a3a4a; vertical-align:middle; }
table.ist td.num { text-align:right; font-variant-numeric:tabular-nums; }
table.ist td.bold { font-weight:700; }
.bal-good { color:#27ae60; font-weight:700; }
.bal-low  { color:#e67e22; font-weight:700; }
.bal-out  { color:#e74c3c; font-weight:700; }
tr.row-out { background:#fff5f5 !important; }
tr.row-low { background:#fffbf0 !important; }

/* Variant/Size Stock Table */
.vsw-product { background:#f4f6ff; padding:10px 16px; font-weight:700; font-size:13px; color:#2d3748; border-top:2px solid #e8ebf7; display:flex; align-items:center; gap:10px; }
.vsw-product .pc { font-size:11px; background:#667eea; color:#fff; border-radius:6px; padding:2px 8px; }
.vsw-product .cat-badge { font-size:11px; background:#f0f2f7; color:#666; border-radius:6px; padding:2px 8px; font-weight:600; }
.vsw-product .tot-stk { margin-left:auto; font-size:12px; color:#888; }

.sz-grid { display:flex; flex-wrap:wrap; gap:10px; padding:12px 16px 14px; border-bottom:1px solid #f0f2f7; }
.sz-card { border-radius:10px; padding:12px 16px; min-width:130px; text-align:center; border:2px solid #eee; transition:.15s; }
.sz-card:hover { border-color:#667eea; box-shadow:0 2px 8px rgba(102,126,234,.15); }
.sz-card.ok   { border-color:#27ae60; background:#f0fdf4; }
.sz-card.low  { border-color:#e67e22; background:#fffbf0; }
.sz-card.out  { border-color:#e74c3c; background:#fff5f5; }
.sz-card .sz-lbl  { font-size:12px; font-weight:700; color:#444; margin-bottom:6px; }
.sz-card .sz-stk  { font-size:24px; font-weight:800; line-height:1; }
.sz-card.ok  .sz-stk { color:#27ae60; }
.sz-card.low .sz-stk { color:#e67e22; }
.sz-card.out .sz-stk { color:#e74c3c; }
.sz-card .sz-price { font-size:11px; color:#888; margin-top:4px; }
.sz-card .sz-status { font-size:10px; font-weight:700; text-transform:uppercase; margin-top:4px; letter-spacing:.5px; }
.sz-card.ok  .sz-status { color:#27ae60; }
.sz-card.low .sz-status { color:#e67e22; }
.sz-card.out .sz-status { color:#e74c3c; }

/* Loading/empty */
.ist-empty  { text-align:center; padding:48px 20px; color:#aaa; font-size:14px; }
.ist-spinner{ display:flex; align-items:center; justify-content:center; gap:10px; padding:48px; color:#667eea; font-size:14px; font-weight:600; }
.ist-spinner i { font-size:26px; animation:spin 1s linear infinite; }
@keyframes spin { to{transform:rotate(360deg);} }
.ist-scroll { overflow-x:auto; overflow-y:auto; max-height:calc(100vh - 380px); }
</style>

<div class="isr-wrap">

    {{-- Page Header --}}
    <div style="margin-bottom:14px">
        <h4 style="font-weight:800;color:#2d3748;margin:0">📦 Stock Reports</h4>
        <p style="color:#888;font-size:13px;margin:4px 0 0">Item-wise aur Size-wise stock dekhein</p>
    </div>

    {{-- Tab Switcher --}}
    <div class="rpt-tabs">
        <button class="rpt-tab active" id="tabItem" onclick="switchTab('item')">📋 Item Stock</button>
        <button class="rpt-tab"        id="tabSize" onclick="switchTab('size')">📐 Size / Variant Stock</button>
    </div>

    {{-- ================= ITEM STOCK PANEL ================= --}}
    <div id="panelItem">

        {{-- Summary Cards --}}
        <div class="isr-cards">
            <div class="isr-card blue">  <div class="lbl">Total Products</div><div class="val" id="cTotal">–</div></div>
            <div class="isr-card green"> <div class="lbl">In Stock</div>      <div class="val" id="cInStock">–</div></div>
            <div class="isr-card orange"><div class="lbl">Low Stock (≤5)</div><div class="val" id="cLow">–</div></div>
            <div class="isr-card red">   <div class="lbl">Out of Stock</div>  <div class="val" id="cOut">–</div></div>
            <div class="isr-card">       <div class="lbl">Total Sold</div>    <div class="val" id="cSold">–</div></div>
            <div class="isr-card">       <div class="lbl">Total Purchased</div><div class="val" id="cPurch">–</div></div>
            <div class="isr-card blue" style="border-color:#764ba2"> <div class="lbl">Inventory Value</div><div class="val" id="cValue">–</div></div>
        </div>

        {{-- Filter --}}
        <div class="isr-filter">
            <div class="fg fg-wide">
                <label>Filter by Product</label>
                <select id="product_id" class="form-select select2-item" style="width:100%">
                    <option value="all">— All Products —</option>
                    @foreach($products as $prod)
                    <option value="{{ $prod->id }}">{{ $prod->item_code }} – {{ $prod->item_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg"><label>Start Date</label><input type="date" id="start_date" value="{{ date('Y-m-01') }}"></div>
            <div class="fg"><label>End Date</label><input type="date" id="end_date" value="{{ date('Y-m-d') }}"></div>
            <button class="btn-srch" onclick="fetchReport()">🔍 Search</button>
            <button class="btn-csv"  onclick="exportCsv()">📤 Export CSV</button>
        </div>

        {{-- Table Card --}}
        <div class="isr-table-wrap">
            <div class="isr-table-header">
                <h6>📋 Stock Detail</h6>
                <div style="display:flex;align-items:center;gap:12px">
                    <div class="ls-wrap">
                        <i class="la la-search ls-ico"></i>
                        <input type="text" id="liveSearch" placeholder="Live search by name / code…" oninput="applyFilter()">
                    </div>
                    <span class="isr-count" id="rowCount">–</span>
                </div>
            </div>
            <div class="ist-scroll">
                <table class="ist">
                    <thead>
                        <tr>
                            <th>#</th><th>Item Code</th><th>Item Name</th>
                            <th class="num">Initial</th><th class="num">Produced</th>
                            <th class="num">Purchased</th><th class="num">Purch. Return</th>
                            <th class="num" style="color:#27ae60">Adj ➕</th>
                            <th class="num" style="color:#e74c3c">Adj ➖</th>
                            <th class="num">Sold</th><th class="num">Sale Return</th>
                            <th class="num">Stock Qty</th>
                        </tr>
                    </thead>
                    <tbody id="reportBody">
                        <tr><td colspan="10" class="ist-empty">Click Search to load data</td></tr>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8f9fc;font-weight:700">
                            <td colspan="3" style="padding:10px 12px;text-align:right;color:#555;font-size:12px;text-transform:uppercase">Totals:</td>
                            <td class="num" id="ftInitial">–</td><td class="num" id="ftProduced">–</td>
                            <td class="num" id="ftPurch">–</td><td class="num" id="ftPReturn">–</td>
                            <td class="num" id="ftAdjInc" style="color:#27ae60">–</td>
                            <td class="num" id="ftAdjDec" style="color:#e74c3c">–</td>
                            <td class="num" id="ftSold">–</td><td class="num" id="ftSReturn">–</td>
                            <td class="num bold" id="ftBal" style="color:#667eea">–</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>{{-- /panelItem --}}

    {{-- ================= SIZE / VARIANT STOCK PANEL ================= --}}
    <div id="panelSize" style="display:none">

        {{-- Summary Cards --}}
        <div class="isr-cards">
            <div class="isr-card blue">  <div class="lbl">Products w/ Sizes</div><div class="val" id="vTotal">–</div></div>
            <div class="isr-card green"> <div class="lbl">Sizes In Stock</div>   <div class="val" id="vOk">–</div></div>
            <div class="isr-card orange"><div class="lbl">Sizes Low Stock</div>  <div class="val" id="vLow">–</div></div>
            <div class="isr-card red">   <div class="lbl">Sizes Out of Stock</div><div class="val" id="vOut">–</div></div>
            <div class="isr-card">       <div class="lbl">Total Size Units</div> <div class="val" id="vUnits">–</div></div>
        </div>

        {{-- Filter --}}
        <div class="isr-filter">
            <div class="fg fg-wide">
                <label>Filter by Product</label>
                <select id="v_product_id" class="form-select select2-var" style="width:100%">
                    <option value="all">— All Products (with sizes) —</option>
                    @foreach($products as $prod)
                    <option value="{{ $prod->id }}">{{ $prod->item_code }} – {{ $prod->item_name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-srch" onclick="fetchVariants()">🔍 Search</button>
            <button class="btn-csv"  onclick="exportVariantCsv()">📤 Export CSV</button>
        </div>

        {{-- Live search + variant card view --}}
        <div class="isr-table-wrap">
            <div class="isr-table-header">
                <h6>📐 Size-wise Stock</h6>
                <div style="display:flex;align-items:center;gap:12px">
                    <div class="ls-wrap">
                        <i class="la la-search ls-ico"></i>
                        <input type="text" id="vLiveSearch" placeholder="Search product / size…" oninput="applyVarFilter()">
                    </div>
                    <span class="isr-count" id="vRowCount">–</span>
                </div>
            </div>
            <div class="ist-scroll" id="variantBody">
                <div class="ist-empty">Click Search to load size data</div>
            </div>
        </div>
    </div>{{-- /panelSize --}}

</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2-item').select2({ placeholder:'Search product…', allowClear:true, width:'100%' });
    $('.select2-var').select2({ placeholder:'Search product…', allowClear:true, width:'100%' });
    fetchReport();     // auto-load item stock
});

/* ===================== TAB SWITCH ===================== */
function switchTab(tab) {
    document.getElementById('panelItem').style.display = tab === 'item' ? '' : 'none';
    document.getElementById('panelSize').style.display = tab === 'size' ? '' : 'none';
    document.getElementById('tabItem').classList.toggle('active', tab === 'item');
    document.getElementById('tabSize').classList.toggle('active', tab === 'size');
    if (tab === 'size' && allVarRows.length === 0) fetchVariants();
}

/* ==================== ITEM STOCK ==================== */
let allRows = [];

function fetchReport() {
    const productId = $('#product_id').val() || 'all';
    const startDate = $('#start_date').val();
    const endDate   = $('#end_date').val();
    showSpinner('reportBody', 10);
    $.ajax({
        url: "{{ route('report.item_stock.fetch') }}", type:'POST', dataType:'json',
        data: { _token:"{{ csrf_token() }}", product_id:productId, start_date:startDate, end_date:endDate },
        success: function(res) { 
            allRows = res.data || []; 
            document.getElementById('liveSearch').value=''; 
            applyFilter(); 
            updateCards(res); 
        },
        error: function(xhr) { document.getElementById('reportBody').innerHTML='<tr><td colspan="10" class="ist-empty" style="color:red">❌ Error. Check console.</td></tr>'; console.error(xhr.responseText); }
    });
}

function applyFilter() {
    const q = (document.getElementById('liveSearch').value||'').toLowerCase().trim();
    const vis = q ? allRows.filter(r=>(r.item_name||'').toLowerCase().includes(q)||(r.item_code||'').toLowerCase().includes(q)) : allRows;
    renderRows(vis);
}

function formatVal(val, isKg, unit) {
    val = parseFloat(val) || 0;
    if (!isKg) {
        let fmtVal = Number.isInteger(val) ? val : val.toFixed(2);
        return fmtVal + ' ' + (unit || 'PC');
    }
    
    let isNegative = val < 0;
    let grams = Math.abs(val);

    if (grams >= 1000) {
        const kg = Math.floor(grams / 1000);
        const gm = Math.round(grams % 1000);
        return (isNegative ? '-' : '') + `${kg}kg${gm > 0 ? ' ' + gm + 'g' : ''}`;
    } else if (grams > 0) {
        return (isNegative ? '-' : '') + Math.round(grams) + 'g';
    }
    return '0';
}

function renderRows(rows) {
    const tbody = document.getElementById('reportBody');
    if (!rows||!rows.length) { tbody.innerHTML='<tr><td colspan="12" class="ist-empty">کوئی record نہیں ملا</td></tr>'; setText('rowCount','0'); clearFooter(); return; }
    let h='';
    rows.forEach(function(r,i){
        const bal=parseFloat(r.balance)||0;
        const rc=bal<=0?'row-out':(bal<=5&&!r.is_kg?'row-low':'');
        const bc=bal<=0?'bal-out':(bal<=5&&!r.is_kg?'bal-low':'bal-good');
        const adjInc = parseFloat(r.adj_increase)||0;
        const adjDec = parseFloat(r.adj_decrease)||0;

        h+='<tr class="'+rc+'"><td>'+(i+1)+'</td>';
        h+='<td><code style="font-size:11px;background:#f5f5f5;padding:2px 6px;border-radius:4px">'+esc(r.item_code)+'</code></td>';
        h+='<td style="font-weight:600">'+esc(r.item_name)+'</td>';
        h+='<td class="num">'+formatVal(r.initial_stock, r.is_kg, r.unit)+'</td>';
        h+='<td class="num">'+formatVal(r.produced, r.is_kg, r.unit)+'</td>';
        h+='<td class="num">'+formatVal(r.purchased, r.is_kg, r.unit)+'</td>';
        h+='<td class="num" style="color:#e74c3c">'+formatVal(r.purchase_return, r.is_kg, r.unit)+'</td>';
        h+='<td class="num" style="color:#27ae60;font-weight:700">'+(adjInc>0?'+'+formatVal(adjInc,r.is_kg, r.unit):'–')+'</td>';
        h+='<td class="num" style="color:#e74c3c;font-weight:700">'+(adjDec>0?'-'+formatVal(adjDec,r.is_kg, r.unit):'–')+'</td>';
        h+='<td class="num" style="color:#8e44ad">'+formatVal(r.sold, r.is_kg, r.unit)+'</td>';
        h+='<td class="num" style="color:#2980b9">'+formatVal(r.sale_return, r.is_kg, r.unit)+'</td>';
        h+='<td class="num '+bc+'">'+formatVal(bal, r.is_kg, r.unit)+(bal<=0?' ❌':'')+'</td></tr>';
    });
    tbody.innerHTML=h;
    setText('rowCount', rows.length+' products');
    updateFooter(rows);
}

function updateCards(res) {
    const rows = res.data || [];
    setText('cTotal',   rows.length);
    setText('cInStock', rows.filter(r=>parseFloat(r.balance)>5).length);
    setText('cLow',     rows.filter(r=>parseFloat(r.balance)>0&&parseFloat(r.balance)<=5).length);
    setText('cOut',     rows.filter(r=>parseFloat(r.balance)<=0).length);
    
    // Total Sold/Purchased are tricky with mixed units, so we just show raw sum or sum of pieces/kg separately?
    // For now keeping it simple as items count.
    setText('cSold',    rows.length > 0 ? rows.reduce((s,r)=>s+(parseFloat(r.sold)||0),0).toFixed(0) : '0');
    setText('cPurch',   rows.length > 0 ? rows.reduce((s,r)=>s+(parseFloat(r.purchased)||0),0).toFixed(0) : '0');
    
    if (res.grand_total !== undefined) {
        setText('cValue', 'Rs ' + Math.round(res.grand_total).toLocaleString());
    }
}

function updateFooter(rows) {
    function sm(f){return rows.reduce(function(s,r){return s+(parseFloat(r[f])||0);},0);}
    setText('ftInitial',fmt(sm('initial_stock'))); 
    setText('ftProduced',fmt(sm('produced')));
    setText('ftPurch',fmt(sm('purchased')));        setText('ftPReturn',fmt(sm('purchase_return')));
    setText('ftAdjInc',fmt(sm('adj_increase')));   setText('ftAdjDec',fmt(sm('adj_decrease')));
    setText('ftSold',fmt(sm('sold')));              setText('ftSReturn',fmt(sm('sale_return')));
    setText('ftBal',fmt(sm('balance')));
}
function clearFooter(){['ftInitial','ftProduced','ftPurch','ftPReturn','ftAdjInc','ftAdjDec','ftSold','ftSReturn','ftBal'].forEach(id=>setText(id,'–'));}

function exportCsv() {
    if (!allRows.length){alert('Pehle Search karein');return;}
    const q=(document.getElementById('liveSearch').value||'').toLowerCase().trim();
    const rows=q?allRows.filter(r=>(r.item_name||'').toLowerCase().includes(q)||(r.item_code||'').toLowerCase().includes(q)):allRows;
    let csv='Item Code,Item Name,Initial,Produced,Purchased,Purch.Return,Adj+,Adj-,Sold,Sale Return,Stock Qty\n';
    rows.forEach(function(r){
        csv+'"'+esc2(r.item_code)+'","'+esc2(r.item_name)+'",'+
            [r.initial_stock,r.produced,r.purchased,r.purchase_return,r.adj_increase||0,r.adj_decrease||0,r.sold,r.sale_return,r.balance].map(v=>parseFloat(v||0).toFixed(2)).join(',')+'\n';
    });
    download(csv,'item_stock_'+today()+'.csv');
}

/* ==================== VARIANT STOCK ==================== */
let allVarRows = [];

function fetchVariants() {
    const productId = $('#v_product_id').val() || 'all';
    document.getElementById('variantBody').innerHTML = '<div class="ist-spinner"><i class="la la-spinner"></i> Loading…</div>';
    ['vTotal','vOk','vLow','vOut','vUnits'].forEach(id=>setText(id,'…'));
    $.ajax({
        url: "{{ route('report.variant_stock.fetch') }}", type:'POST', dataType:'json',
        data: { _token:"{{ csrf_token() }}", product_id:productId },
        success: function(res) {
            allVarRows = res.data || [];
            document.getElementById('vLiveSearch').value = '';
            applyVarFilter();
            updateVarCards(allVarRows);
        },
        error: function(xhr){ document.getElementById('variantBody').innerHTML='<div class="ist-empty" style="color:red">❌ Error. Check console.</div>'; console.error(xhr.responseText); }
    });
}

function applyVarFilter() {
    const q=(document.getElementById('vLiveSearch').value||'').toLowerCase().trim();
    const vis = q ? allVarRows.filter(r=>
        (r.product_name||'').toLowerCase().includes(q)||
        (r.item_code||'').toLowerCase().includes(q)||
        r.sizes.some(s=>(s.label||'').toLowerCase().includes(q))
    ) : allVarRows;
    renderVariants(vis);
}

function renderVariants(data) {
    const body = document.getElementById('variantBody');
    if (!data||!data.length){
        body.innerHTML='<div class="ist-empty">Koi size/variant data nahi mila.<br><small>Sirf un products ka data aayega jin ke sizes set hain.</small></div>';
        setText('vRowCount','0 products');
        return;
    }
    let h='';
    data.forEach(function(prod){
        const totStk = prod.sizes.reduce((s,v)=>s+(v.stock_qty||0),0);
        h+='<div class="vsw-product">';
        h+='<span class="pc">'+esc(prod.item_code)+'</span>';
        h+='<strong>'+esc(prod.product_name)+'</strong>';
        if(prod.category && prod.category!=='–') h+='<span class="cat-badge">'+esc(prod.category)+'</span>';
        h+='<span class="tot-stk">Total: '+fmt(totStk)+'</span>';
        h+='</div>';
        h+='<div class="sz-grid">';
        prod.sizes.forEach(function(sz){
            const stk=sz.stock_qty||0;
            const cls=sz.status;
            const statusTxt=cls==='out'?'Out of Stock':(cls==='low'?'Low Stock ⚠️':'In Stock ✅');
            h+='<div class="sz-card '+cls+'">';
            h+='<div class="sz-lbl">'+esc(sz.label)+'</div>';
            h+='<div class="sz-stk">'+parseFloat(stk).toFixed(0)+'</div>';
            h+='<div class="sz-price">Rs '+fmt(sz.price)+'</div>';
            h+='<div class="sz-status">'+statusTxt+'</div>';
            h+='</div>';
        });
        h+='</div>';
    });
    body.innerHTML=h;
    setText('vRowCount', data.length+' products');
}

function updateVarCards(data) {
    let ok=0,low=0,out=0,units=0;
    data.forEach(function(p){
        p.sizes.forEach(function(s){
            if(s.status==='ok')  ok++;
            else if(s.status==='low') low++;
            else out++;
            units += (s.stock_qty||0);
        });
    });
    setText('vTotal', data.length);
    setText('vOk',    ok);
    setText('vLow',   low);
    setText('vOut',   out);
    setText('vUnits', parseFloat(units).toFixed(0));
}

function exportVariantCsv() {
    if (!allVarRows.length){alert('Pehle Search karein');return;}
    const q=(document.getElementById('vLiveSearch').value||'').toLowerCase().trim();
    const rows=q?allVarRows.filter(r=>(r.product_name||'').toLowerCase().includes(q)||(r.item_code||'').toLowerCase().includes(q)):allVarRows;
    let csv='Item Code,Product Name,Category,Size/Variant,Sale Price,Stock Qty,Status\n';
    rows.forEach(function(p){
        p.sizes.forEach(function(s){
            csv+='"'+esc2(p.item_code)+'","'+esc2(p.product_name)+'","'+esc2(p.category||'')+'","'+esc2(s.label)+'",'+
                parseFloat(s.price||0).toFixed(2)+','+parseFloat(s.stock_qty||0).toFixed(2)+',"'+s.status+'"\n';
        });
    });
    download(csv,'size_stock_'+today()+'.csv');
}

/* ==================== HELPERS ==================== */
function showSpinner(tbodyId, cols) {
    document.getElementById(tbodyId).innerHTML='<tr><td colspan="'+cols+'"><div class="ist-spinner"><i class="la la-spinner"></i> Loading…</div></td></tr>';
}
function fmt(v) { return parseFloat(v||0).toFixed(2); }
function esc(s) { return (s||'').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function esc2(s){ return (s||'').toString().replace(/"/g,'""'); }
function setText(id,v){ const el=document.getElementById(id); if(el) el.textContent=v; }
function today(){ return new Date().toISOString().slice(0,10); }
function download(csv, name){
    const a=document.createElement('a');
    a.href=URL.createObjectURL(new Blob([csv],{type:'text/csv;charset=utf-8;'}));
    a.download=name; document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
</script>
@endsection