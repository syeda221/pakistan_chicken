@extends('admin_panel.layout.app')
@section('content')
<style>
    /* Thermal Receipt Styling for Preview */
    .receipt-preview {
        width: 80mm;
        margin: 0 auto;
        padding: 2mm 1mm;
        background: #fff;
        color: #000;
        font-family: 'Arial', sans-serif;
        font-size: 14px;
        line-height: 1.3;
        font-weight: 700;
        border: 1px solid #ddd;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        position: relative;
    }

    .receipt-preview .center { text-align: center; }
    .receipt-preview .bold { font-weight: 900; }
    .receipt-preview .line { border-top: 2px dashed #000; margin: 3px 0; }
    .receipt-preview table { width: 100%; border-collapse: collapse; }
    .receipt-preview th, .receipt-preview td { padding: 3px 0; vertical-align: top; }
    
    .receipt-preview .store-name { font-size: 22px; font-weight: 900; margin: 5px 0; text-transform: uppercase; }
    .receipt-preview .store-info { font-size: 13px; margin: 2px 0; font-weight: 800; }
    .receipt-preview .receipt-title { font-size: 18px; font-weight: 900; margin: 5px 0; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 3px 0; text-transform: uppercase; }

    .receipt-preview .items-table thead th { border-top: 2px solid #000; border-bottom: 2px solid #000; font-size: 14px; text-align: left; font-weight: 900; }
    .receipt-preview .col-amount { text-align: right; white-space: nowrap; padding-left: 5px; }
    .receipt-preview .col-price { text-align: right; white-space: nowrap; padding-left: 5px; }
    .receipt-preview .col-qty { text-align: right; white-space: nowrap; padding-left: 5px; }
    .receipt-preview th, .receipt-preview td { padding: 1px 2px; }

    .receipt-preview .totals-table th { text-align: left; font-weight: 800; }
    .receipt-preview .totals-table td { text-align: right; font-weight: 900; }
    .receipt-preview .grand-total-row th, .receipt-preview .grand-total-row td { font-weight: 900; font-size: 18px; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 3px 0; }

    .receipt-preview .ribbon-wrap { position: absolute; top: 0; right: 0; width: 70px; height: 70px; overflow: hidden; z-index: 99; }
    .receipt-preview .ribbon { position: absolute; top: 20px; right: -32px; width: 128px; text-align: center; background: #000; color: #fff; font-size: 12px; font-weight: 900; letter-spacing: 1px; padding: 5px 0; transform: rotate(45deg); }

    /* Summary Dashboard Styling */
    .summary-card {
        border-radius: 12px;
        padding: 20px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
    }
    .summary-item { text-align: center; }
    .summary-item h6 { font-size: 14px; color: #6c757d; text-transform: uppercase; margin-bottom: 8px; }
    .summary-item .value { font-size: 24px; font-weight: 800; color: #212529; }
    .summary-item.balance .value { color: #dc3545; }
    .summary-item.paid .value { color: #198754; }

    .payment-section {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }
    .confirm-btn {
        padding: 15px;
        font-size: 1.2rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        {{-- Left: Receipt Preview --}}
        <div class="col-lg-5 mb-4">
            <div class="receipt-preview">
                <div class="ribbon-wrap">
                    <div class="ribbon">BOOKED</div>
                </div>
                <div class="center">
                    <div class="store-name">Bin Sultan</div>
                    <div class="store-info">Sweet & Bakers</div>
                    <div class="store-info">Latifabad no 6 Hyderabad</div>
                    <div class="receipt-title">Booking Confirmation</div>
                </div>

                <table class="meta-table" style="font-size: 12px; width: 100%;">
                    <tr><th style="text-align:left;">Booking #</th><td>: {{ $booking->id }}</td></tr>
                    <tr><th style="text-align:left;">Customer</th><td>: {{ $booking->customer_relation->customer_name ?? 'N/A' }}</td></tr>
                    <tr><th style="text-align:left;">Reference</th><td>: {{ $booking->reference ?? 'N/A' }}</td></tr>
                    <tr><th style="text-align:left;">Date</th><td>: {{ \Carbon\Carbon::parse($booking->booking_date)->format('d-M-Y') }}</td></tr>
                </table>

                <div class="line"></div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="col-qty">Qty</th>
                            <th class="col-amount">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookingItems as $item)
                        <tr>
                            <td>{{ $item['item_name'] }}</td>
                            <td class="col-qty">{{ $item['qty'] }} {{ $item['unit'] }}</td>
                            <td class="col-amount">{{ number_format($item['total'], 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="line"></div>

                <table class="totals-table">
                    <tr>
                        <th style="text-align:left;">Gross Amount</th>
                        <td>{{ number_format($booking->total_bill_amount ?? 0, 0) }}</td>
                    </tr>
                    @if(!empty($booking->total_extradiscount) && $booking->total_extradiscount > 0)
                    <tr>
                        <th style="text-align:left;">Discount</th>
                        <td>{{ number_format($booking->total_extradiscount, 0) }}</td>
                    </tr>
                    @endif
                    <tr class="grand-total-row">
                        <th>Net Amount</th>
                        <td>{{ number_format($booking->total_net, 0) }}</td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Advance Paid</th>
                        <td style="color: #198754;">{{ number_format($booking->advance_payment ?? 0, 0) }}</td>
                    </tr>
                    @php
                        $remaining = max(($booking->total_net ?? 0) - ($booking->advance_payment ?? 0), 0);
                    @endphp
                    <tr style="font-size: 18px;">
                        <th>Remaining</th>
                        <td style="color: #dc3545;">{{ number_format($remaining, 0) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Right: Confirmation Form --}}
        <div class="col-lg-7">
            <div class="payment-section">
                <h4 class="mb-4 fw-bold text-dark border-bottom pb-2">Final Settlement</h4>
                
                <form action="{{ route('sales.store') }}" method="POST" id="confirmForm">
                    @csrf
                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                    <input type="hidden" name="customer" value="{{ $booking->customer }}">
                    <input type="hidden" name="reference" value="{{ $booking->reference }}">
                    
                    {{-- Re-pass item data for store logic --}}
                    @foreach($bookingItems as $index => $item)
                        <input type="hidden" name="product_id[]" value="{{ $item['product_id'] }}">
                        <input type="hidden" name="product_name[]" value="{{ $item['item_name'] }}">
                        <input type="hidden" name="item_code[]" value="{{ $item['item_code'] }}">
                        <input type="hidden" name="uom[]" value="{{ $item['uom'] }}">
                        <input type="hidden" name="unit[]" value="{{ $item['unit'] }}">
                        <input type="hidden" name="price[]" value="{{ $item['price'] }}">
                        <input type="hidden" name="item_disc[]" value="{{ $item['discount'] }}">
                        <input type="hidden" name="qty[]" value="{{ $item['qty'] }}">
                        <input type="hidden" name="total[]" value="{{ $item['total'] }}">
                        <input type="hidden" name="color[]" value="{{ json_encode($item['available_colors']) }}">
                    @endforeach

                    <input type="hidden" name="total_subtotal" value="{{ $booking->total_bill_amount }}">
                    <input type="hidden" name="total_extra_cost" value="{{ $booking->total_extradiscount }}">
                    <input type="hidden" name="total_net" value="{{ $booking->total_net }}">

                    <div class="summary-card mb-4">
                        <div class="row">
                            <div class="col-4 summary-item">
                                <h6>Total Bill</h6>
                                <div class="value">Rs. {{ number_format($booking->total_net, 0) }}</div>
                            </div>
                            <div class="col-4 summary-item paid border-start border-end">
                                <h6>Advance Paid</h6>
                                <div class="value">Rs. {{ number_format($booking->advance_payment ?? 0, 0) }}</div>
                            </div>
                            <div class="col-4 summary-item balance">
                                <h6>Remaining</h6>
                                <div class="value" id="balanceDisplay">Rs. {{ number_format($remaining, 0) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cash Received</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-success text-white">Rs.</span>
                                <input type="number" name="cash" id="cashInput" 
                                       class="form-control fw-bold text-success" 
                                       placeholder="0" step="0.01" 
                                       value="{{ $remaining }}"
                                       autofocus>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Card Payment</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-primary text-white">Rs.</span>
                                <input type="number" name="card" id="cardInput" 
                                       class="form-control fw-bold text-primary" 
                                       placeholder="0" step="0.01">
                            </div>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <div class="alert alert-info d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Change to Return:</span>
                                <span class="fs-4 fw-black" id="changeDisplay">Rs. 0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-grid gap-2">
                        <button type="submit" name="action" value="sale" class="btn btn-success confirm-btn">
                            <i class="fas fa-check-circle me-2"></i> Confirm & Print Sale Receipt
                        </button>
                        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i> Cancel & Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cashInput = document.getElementById('cashInput');
        const cardInput = document.getElementById('cardInput');
        const changeDisplay = document.getElementById('changeDisplay');
        const netAmount = parseFloat("{{ $booking->total_net }}") || 0;
        const advancePaid = parseFloat("{{ $booking->advance_payment ?? 0 }}") || 0;
        const remaining = netAmount - advancePaid;

        function updateChange() {
            const cash = parseFloat(cashInput.value) || 0;
            const card = parseFloat(cardInput.value) || 0;
            const totalReceived = cash + card;
            
            // Note: In confirmation, the "total net" for the new sale should be the full amount?
            // Or just the remaining? 
            // In the store logic, it creates a new Sale. If we want it to reflect the full transaction, 
            // we should store the full net amount.
            
            const change = totalReceived - remaining;
            changeDisplay.innerText = "Rs. " + change.toLocaleString(undefined, {minimumFractionDigits: 2});
            
            if (change < 0) {
                changeDisplay.classList.add('text-danger');
                changeDisplay.classList.remove('text-success');
            } else {
                changeDisplay.classList.add('text-success');
                changeDisplay.classList.remove('text-danger');
            }
        }

        cashInput.addEventListener('input', updateChange);
        cardInput.addEventListener('input', updateChange);
        
        // Form validation
        document.getElementById('confirmForm').addEventListener('submit', function(e) {
            const cash = parseFloat(cashInput.value) || 0;
            const card = parseFloat(cardInput.value) || 0;
            if ((cash + card) < remaining) {
                if (!confirm('Received amount is less than remaining balance. Proceed?')) {
                    e.preventDefault();
                }
            }
        });

        updateChange();
    });
</script>
@endsection