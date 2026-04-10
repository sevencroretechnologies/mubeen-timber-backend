<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $order->po_code }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #333;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }
        .top-bar {
            height: 15px;
            background-color: #2F889A; /* Elegant Teal */
            width: 100%;
        }
        .content {
            padding: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 40px;
        }
        .logo-container {
            margin-bottom: 10px;
        }
        .logo-container img {
            max-height: 70px;
            max-width: 200px;
            object-fit: contain;
        }
        .invoice-title {
            font-size: 38px;
            font-weight: normal;
            color: #000;
            margin-top: 10px;
            letter-spacing: -1px;
        }
        .company-info {
            text-align: right;
            color: #666;
            line-height: 1.5;
        }
        .company-name {
            font-size: 16px;
            color: #333;
            font-weight: bold;
            margin-bottom: 4px;
        }
        
        .meta-table {
            margin-bottom: 40px;
        }
        .meta-table td {
            vertical-align: top;
        }
        .bill-to-label {
            font-size: 11px;
            color: #888;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .bill-to-info {
            color: #333;
            line-height: 1.5;
        }
        .supplier-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .invoice-details {
            text-align: right;
            font-size: 12px;
        }
        .invoice-details table {
            width: auto;
            float: right;
        }
        .invoice-details td {
            padding: 4px 0 4px 20px;
        }
        .invoice-details .label {
            color: #888;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 1px;
        }
        .invoice-details .val {
            color: #333;
            text-align: right;
        }
        
        .items-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .items-table th {
            text-align: left;
            padding: 12px 5px;
            border-bottom: 1px solid #eee;
            border-top: 1px solid #eee;
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .items-table td {
            padding: 15px 5px;
            border-bottom: 1px solid #f5f5f5;
            color: #333;
            vertical-align: top;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        
        .totals-section {
            width: 100%;
            margin-top: 20px;
        }
        .totals-section table {
            width: auto;
            float: right;
            border-collapse: collapse;
        }
        .totals-section td {
            padding: 8px 5px;
            text-align: right;
            font-size: 13px;
        }
        .totals-section .label {
            color: #666;
            padding-right: 25px;
        }
        .totals-section .grand-total {
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 5px;
        }
        .totals-section .grand-total .label {
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }
        .totals-section .grand-total .amount {
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }
        
        .notes-section {
            margin-top: 50px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            font-size: 12px;
            color: #666;
            line-height: 1.5;
            width: 60%;
        }
        .notes-section .label {
            color: #888;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .clear {
            clear: both;
        }
        
        .warehouse-info {
            margin-top: 25px;
        }
    </style>
</head>
<body>
    @php
        $logoBase64 = null;
        if ($order->company && $order->company->company_logo) {
            // Check storage path
            $path = storage_path('app/public/' . $order->company->company_logo);
            if (!file_exists($path)) {
                // Fallback to public path
                $path = public_path($order->company->company_logo);
            }
            if (!file_exists($path)) {
                // Fallback to S3 or other URL? Only local base64 encode works smoothly in dompdf
                // We'll leave it null if not found locally
            }
            if (file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }
    @endphp

    <div class="top-bar"></div>
    <div class="content">
        <table class="header-table">
            <tr>
                <td style="vertical-align: top; width: 50%;">
                    @if($logoBase64)
                        <div class="logo-container">
                            <img src="{{ $logoBase64 }}" alt="Company Logo">
                        </div>
                    @endif
                    <div class="invoice-title">Purchase Order</div>
                </td>
                <td class="company-info" style="vertical-align: top;">
                    <div class="company-name">{{ $order->company->company_name ?? 'Your Company Name' }}</div>
                    <div>{!! nl2br(e($order->company->address ?? 'Your Business Address')) !!}</div>
                    @if($order->company && $order->company->company_phone)
                        <div>{{ $order->company->company_phone }}</div>
                    @endif
                    @if($order->company && $order->company->email)
                        <div>{{ $order->company->email }}</div>
                    @endif
                    @if($order->company && $order->company->website)
                        <div>{{ $order->company->website }}</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="meta-table">
            <tr>
                <td style="width: 50%;">
                    <div class="bill-to-label">VENDOR:</div>
                    <div class="bill-to-info">
                        <div class="supplier-name">{{ $order->supplier->name ?? 'Supplier Name' }}</div>
                        @if($order->supplier)
                            <div>{!! nl2br(e($order->supplier->address ?? '')) !!}</div>
                            @if($order->supplier->phone)<div>{{ $order->supplier->phone }}</div>@endif
                            @if($order->supplier->email)<div>{{ $order->supplier->email }}</div>@endif
                        @endif
                    </div>
                    
                    <div class="warehouse-info">
                        <div class="bill-to-label">SHIP TO:</div>
                        <div class="bill-to-info">
                            <div class="supplier-name">{{ $order->warehouse->name ?? 'Warehouse Name' }}</div>
                            @if($order->warehouse)
                                <div>{!! nl2br(e($order->warehouse->address ?? '')) !!}</div>
                                @if($order->warehouse->contact_number)<div>{{ $order->warehouse->contact_number }}</div>@endif
                            @endif
                        </div>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="invoice-details">
                        <table>
                            <tr>
                                <td class="label">P.O. #</td>
                                <td class="val">{{ $order->po_code }}</td>
                            </tr>
                            <tr>
                                <td class="label">DATE</td>
                                <td class="val">{{ \Carbon\Carbon::parse($order->order_date)->format('m/d/y') }}</td>
                            </tr>
                            <tr>
                                <td class="label">EXPECTED</td>
                                <td class="val">
                                    {{ $order->expected_delivery_date ? \Carbon\Carbon::parse($order->expected_delivery_date)->format('m/d/y') : ($order->expected_date ? \Carbon\Carbon::parse($order->expected_date)->format('m/d/y') : '-') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="label">STATUS</td>
                                <td class="val" style="text-transform: uppercase;">{{ str_replace('_', ' ', $order->status->value ?? $order->status) }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="35%">ITEMS</th>
                    <th width="25%">DESCRIPTION</th>
                    <th width="15%" class="text-center">QUANTITY</th>
                    <th width="10%" class="text-right">PRICE</th>
                    <th width="15%" class="text-right">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @if($order->items && count($order->items) > 0)
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->woodType->name ?? 'Unknown Item' }}</strong>
                        </td>
                        <td style="color: #666; font-size: 12px;">
                            Unit: {{ $item->unit }}
                        </td>
                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">@if($item->unit_price == 0) {{ '--' }} @else ₹{{ number_format($item->unit_price, 2) }} @endif</td>
                        <td class="text-right">₹{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 30px; color: #999;">No items found.</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="totals-section">
            <table>
                <tr>
                    <td class="label">SUBTOTAL</td>
                    <td>₹{{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if($order->discount_amount > 0)
                <tr>
                    <td class="label">DISCOUNT</td>
                    <td style="color: #d9534f;">- ₹{{ number_format($order->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($order->tax_amount > 0)
                <tr>
                    <td class="label">TAX @if($order->tax_percentage > 0)({{ $order->tax_percentage }}%)@endif</td>
                    <td>₹{{ number_format($order->tax_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="grand-total">
                    <td class="label">TOTAL</td>
                    <td class="amount">₹{{ number_format($order->total_amount, 2) }}</td>
                </tr>
            </table>
            <div class="clear"></div>
        </div>

        @if($order->notes || $order->terms)
        <div class="notes-section">
            @if($order->notes)
            <div class="label">NOTES:</div>
            <div style="margin-bottom: 15px;">{!! nl2br(e($order->notes)) !!}</div>
            @endif
            
            @if($order->terms)
            <div class="label">TERMS:</div>
            <div>{!! nl2br(e($order->terms)) !!}</div>
            @endif
        </div>
        @endif
        
        <div style="margin-top: 40px; font-size: 10px; color: #aaa; text-align: center;">
            Powered by <strong>MTT ERP</strong>
        </div>
    </div>
</body>
</html>
