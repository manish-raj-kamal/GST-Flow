<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
        }
        .header,
        .meta,
        .totals {
            width: 100%;
            margin-bottom: 18px;
        }
        .header td,
        .meta td,
        .totals td {
            vertical-align: top;
        }
        .heading {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .muted {
            color: #6b7280;
            font-size: 11px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        table.items th,
        table.items td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }
        table.items th {
            background: #f3f4f6;
        }
        .signature {
            margin-top: 26px;
            border-top: 1px dashed #9ca3af;
            padding-top: 12px;
            text-align: right;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td width="60%">
                <div class="heading">{{ $businessProfile->business_name }}</div>
                <div>{{ $businessProfile->legal_name }}</div>
                <div>{{ $businessProfile->address }}, {{ $businessProfile->city }}, {{ $businessProfile->state }} - {{ $businessProfile->pincode }}</div>
                <div>GSTIN: {{ $businessProfile->gstin }}</div>
                <div>PAN: {{ $businessProfile->pan }}</div>
            </td>
            <td width="40%">
                <div class="heading">Tax Invoice</div>
                <div>Invoice No: {{ $invoice->invoice_number }}</div>
                <div>Invoice Date: {{ optional($invoice->invoice_date)->toDateString() }}</div>
                <div>Transaction Type: {{ strtoupper($invoice->transaction_type) }}</div>
                <div>Status: {{ strtoupper($invoice->status) }}</div>
                <div>Place of Supply: {{ $invoice->place_of_supply }}</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td width="50%">
                <strong>Seller</strong><br>
                {{ $businessProfile->business_name }}<br>
                GSTIN: {{ $invoice->seller_gstin }}<br>
                Email: {{ $businessProfile->email }}<br>
                Phone: {{ $businessProfile->phone }}
            </td>
            <td width="50%">
                <strong>Buyer</strong><br>
                {{ $customer?->customer_name }}<br>
                GSTIN: {{ $invoice->buyer_gstin ?: 'N/A' }}<br>
                {{ $customer?->address }}<br>
                {{ $customer?->state }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th>HSN</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>Tax Rate</th>
                <th>Tax Amount</th>
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items ?? [] as $item)
                <tr>
                    <td>{{ $item['product_name'] ?? '' }}</td>
                    <td>{{ $item['hsn_code'] ?? '' }}</td>
                    <td>{{ $item['quantity'] ?? 0 }}</td>
                    <td>{{ number_format((float) ($item['rate'] ?? 0), 2) }}</td>
                    <td>{{ number_format((float) ($item['tax_rate'] ?? 0), 2) }}%</td>
                    <td>{{ number_format((float) ($item['tax_amount'] ?? 0), 2) }}</td>
                    <td>{{ number_format((float) ($item['line_total'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td width="50%"></td>
            <td width="50%">
                <table width="100%">
                    <tr><td>Taxable Value</td><td align="right">{{ number_format((float) $invoice->taxable_value, 2) }}</td></tr>
                    <tr><td>CGST</td><td align="right">{{ number_format((float) $invoice->cgst, 2) }}</td></tr>
                    <tr><td>SGST</td><td align="right">{{ number_format((float) $invoice->sgst, 2) }}</td></tr>
                    <tr><td>IGST</td><td align="right">{{ number_format((float) $invoice->igst, 2) }}</td></tr>
                    <tr><td>Total Tax</td><td align="right">{{ number_format((float) $invoice->total_tax, 2) }}</td></tr>
                    <tr><td><strong>Grand Total</strong></td><td align="right"><strong>{{ number_format((float) $invoice->total_amount, 2) }}</strong></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="signature">
        <div class="muted">Authorised Signatory</div>
        <div style="margin-top: 24px;">________________________</div>
    </div>
</body>
</html>
