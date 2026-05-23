<?php
$content = file_get_contents('resources/views/admin_panel/sale/add_sale.blade.php');

$content = str_replace(
    '<form id="salesForm" action="{{ route(\'sales.store\') }}" method="POST">',
    '<form id="salesForm" action="{{ route(\'sales.update\', $sale->id) }}" method="POST">' . "\n" . '    @method(\'PUT\')',
    $content
);

$content = preg_replace(
    '/<select name="customer" id="custSel" style="flex:2">/',
    '<select name="customer" id="custSel" style="flex:2">
    <option value="Walk-in Customer" {{ $sale->customer === "Walk-in Customer" ? "selected" : "" }}>👤 Walk-in Customer</option>
    @foreach($Customer as $c)
        <option value="{{ $c->id }}" {{ $sale->customer == $c->id ? "selected" : "" }}>
            {{ $c->customer_name }}
        </option>
    @endforeach',
    $content
);

// We need to only replace the FIRST occurrence or just the correct one, but preg_replace is fine.
// Also remove the existing <option value="Walk-in Customer">... and @foreach section so it doesn't duplicate:
// Let's do a more robust preg_replace for the whole customer dropdown.
$content = preg_replace(
    '/<select name="customer" id="custSel" style="flex:2">[\s\S]*?<\/select>/',
    '<select name="customer" id="custSel" style="flex:2">
    <option value="Walk-in Customer" {{ $sale->customer === "Walk-in Customer" ? "selected" : "" }}>👤 Walk-in Customer</option>
    @foreach($Customer as $c)
        <option value="{{ $c->id }}" {{ $sale->customer == $c->id ? "selected" : "" }}>
            {{ $c->customer_name }}
        </option>
    @endforeach
</select>',
    $content
);

$cartReplacement = 'let cart = [
    @foreach($saleItems as $item)
    {
        id: "{{ $item["product_id"] }}",
        variantId: "",
        code: "{{ addslashes($item["item_code"]) }}",
        name: "{{ addslashes($item["item_name"]) }}",
        price: parseFloat({{ $item["price"] }}),
        disc: parseFloat({{ $item["discount"] }}),
        qty: parseFloat({{ $item["qty"] }}),
        unitId: "{{ addslashes($item["unit"]) }}",
        label: "{{ addslashes($item["note"] ?? "") }}"
    },
    @endforeach
];
setTimeout(function(){ renderCart(); recalc(); }, 500);';

$content = preg_replace('/let\s+cart\s*=\s*\[\];/', $cartReplacement, $content);

$content = preg_replace(
    '/<input type="text" name="reference" id="refInput"[^>]*>/',
    '<input type="text" name="reference" id="refInput" value="{{ $sale->reference }}" placeholder="Ref #" style="flex:1;max-width:85px">',
    $content
);

$content = preg_replace(
    '/<input type="number" id="cashI"([^>]*?)>/',
    '<input type="number" id="cashI" value="{{ $sale->cash }}"$1>',
    $content
);

$content = preg_replace(
    '/<input type="number" id="cardI"([^>]*?)>/',
    '<input type="number" id="cardI" value="{{ $sale->card }}"$1>',
    $content
);

$content = preg_replace(
    '/<input type="number" id="extraDisc"([^>]*?)value="0"([^>]*?)>/',
    '<input type="number" id="extraDisc"$1value="{{ $sale->total_extradiscount }}"$2>',
    $content
);

file_put_contents('resources/views/admin_panel/sale/saleedit.blade.php', $content);
echo "SUCCESS";
