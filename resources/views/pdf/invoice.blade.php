<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
      @page {
        margin: 15mm;
      }
      body {
        margin: 0;
        padding: 0;
        background: #fff;
        font-family: "DejaVu Sans", sans-serif;
        font-size: 12px;
        color: #333;
      }
      table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
      }
      td {
        vertical-align: top;
        word-wrap: break-word;
      }
      .text-right {
        text-align: right;
      }
      .text-center {
        text-align: center;
      }
      .bold {
        font-weight: bold;
      }
      .gray {
        color: #555;
      }
      .brand-color {
        color: #fc2d42;
      }
      .bg-light {
        background-color: #e5e7eb;
      }
    </style>
  </head>

  <body>
    <!-- Main Content Wrapper -->
    <div style="width: 100%;">
      
      <!-- Header Section -->
      <table style="margin-bottom: 30px;">
        <tr>
          <td style="width: 50%;">
            @php
              $logo = App\Models\BusinessSetting::getValue('site_logo');
              $logoPath = $logo ? storage_path('app/public/' . $logo) : null;
            @endphp
            @if($logoPath && file_exists($logoPath))
              <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" style="max-height: 80px; max-width: 250px;">
            @else
              <h2 class="brand-color" style="margin: 0;">valokichu</h2>
            @endif
          </td>
          <td class="text-right" style="width: 50%;">
            <div style="font-size: 24px;" class="bold">INVOICE</div>
            <div class="gray"># {{ $order->order_number }}</div>
            <div style="margin-top: 5px;">
              <span class="brand-color bold" style="text-transform: uppercase;">{{ $order->payment_status }}</span>
              @if($order->payment_status !== 'paid')
                <span style="color: #84c529;"> - PAY INVOICE</span>
              @endif
            </div>
          </td>
        </tr>
      </table>

      <!-- Address Section -->
      <table style="margin-bottom: 40px;">
        <tr>
          <td style="width: 50%;">
            <div class="bold">valokichu</div>
            <div class="gray">House 3, Road 3A, 1st & 2nd Floor, Sector 15,</div>
            <div class="gray">Uttara Model Town, Dhaka 1230</div>
            <div class="gray">Bangladesh</div>
          </td>
          <td class="text-right" style="width: 50%;">
            <div class="bold">Bill To:</div>
            <div>{{ $order->name }}</div>
            @if($order->contact_number)
              <div class="gray">{{ $order->contact_number }}</div>
            @endif
            <div class="gray">Invoice Date: {{ $order->created_at->format('d-m-Y') }}</div>
          </td>
        </tr>
      </table>

      <!-- Items Table -->
      <table style="margin-top: 20px;">
        <thead>
          <tr class="bg-light">
            <th style="width: 40px; padding: 8px; text-align: left; border-bottom: 1px solid #000;">#</th>
            <th style="padding: 8px; text-align: left; border-bottom: 1px solid #000;">Item Description</th>
            <th style="width: 60px; padding: 8px; text-align: center; border-bottom: 1px solid #000;">Qty</th>
            <th style="width: 100px; padding: 8px; text-align: center; border-bottom: 1px solid #000;">Rate</th>
            <th style="width: 100px; padding: 8px; text-align: right; border-bottom: 1px solid #000;">Amount</th>
          </tr>
        </thead>
        <tbody>
          @foreach($order->items as $index => $item)
          <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $index + 1 }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
              <div class="bold">{{ $item->product_name }}</div>
              @if($item->variation_snapshot)
                <div class="gray" style="font-size: 12px;">{{ $item->variation_snapshot }}</div>
              @endif
            </td>
            <td class="text-center" style="padding: 8px; border-bottom: 1px solid #eee;">{{ $item->quantity }}</td>
            <td class="text-center" style="padding: 8px; border-bottom: 1px solid #eee;">{{ number_format($item->unit_price, 2) }}</td>
            <td class="text-right" style="padding: 8px; border-bottom: 1px solid #eee;">{{ number_format($item->total_price, 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <!-- Totals Section -->
      <table style="margin-top: 30px;">
        <tr>
          <td style="width: 60%;"></td>
          <td style="width: 40%;">
            <table style="width: 100%;">
              <tr>
                <td style="padding: 5px 0;">Sub Total</td>
                <td class="text-right" style="padding: 5px 0;">&#2547;{{ number_format($order->subtotal, 2) }}</td>
              </tr>
              @if($order->shipping_cost > 0)
              <tr>
                <td style="padding: 5px 0;">Shipping</td>
                <td class="text-right" style="padding: 5px 0;">&#2547;{{ number_format($order->shipping_cost, 2) }}</td>
              </tr>
              @endif
              @if($order->discount > 0)
              <tr>
                <td style="padding: 5px 0;">Discount</td>
                <td class="text-right" style="padding: 5px 0;">- &#2547;{{ number_format($order->discount, 2) }}</td>
              </tr>
              @endif
              <tr class="bg-light bold">
                <td style="padding: 8px;">Total</td>
                <td class="text-right" style="padding: 8px;">&#2547;{{ number_format($order->total_price, 2) }}</td>
              </tr>
              <tr class="bg-light bold" style="margin-top: 5px;">
                <td style="padding: 8px;">Amount Due</td>
                <td class="text-right brand-color" style="padding: 8px;">&#2547;{{ $order->payment_status === 'paid' ? '0.00' : number_format($order->total_price, 2) }}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <!-- Footer / Notes -->
      <div style="margin-top: 50px;">
        <div>
          <span class="bold">With words:</span> 
          @php
            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
          @endphp
          {{ ucfirst($f->format($order->total_price)) }} Only
        </div>
      </div>

      <!-- Payment Info -->
      <div style="margin-top: 40px; font-size: 13px;">
        <div class="bold" style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Offline Payment Information:</div>
        
        <table style="width: 100%;">
          <tr>
            <td style="width: 50%;">
              <div class="bold">Bank Transfer</div>
              <div class="gray">Account Number: 1510202723129001</div>
              <div class="gray">Account Name: valokichu</div>
              <div class="gray">Bank: BRAC Bank PLC</div>
              <div class="gray">Branch: UTTARA, Routing: 060264631</div>
            </td>
            <td style="width: 50%;">
              <div class="bold">Mobile Banking</div>
              <div class="gray">Bkash Personal: 01712643138</div>
              <div class="gray">Bkash Merchant: 01969101010</div>
              <div class="brand-color" style="font-size: 11px;">*2% extra for Bkash payments</div>
            </td>
          </tr>
        </table>
      </div>

      <div style="margin-top: 60px;">
        <div style="width: 200px; border-top: 1px solid #000; padding-top: 5px;" class="text-center bold">Authorized Signature</div>
      </div>

    </div>
  </body>
</html>
