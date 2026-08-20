<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Invoice - {{ $order->order_number }}</title>
  <style>
    @page {
      margin: 12mm;
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
      text-align: start;
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
          <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}"
            style="max-height: 80px; max-width: 250px;">
          @else
          <h2 class="brand-color" style="margin: 0;">valokichu</h2>
          @endif
        </td>
        <td class="text-right" style="width: 50%;">
          <div style="font-size: 24px;" class="bold">INVOICE</div>
          <div class="gray"># {{ $order->order_number }}</div>
          <div class="gray" style="font-size: 11px; margin-top: 2px;">Order ID: {{ $order->id }}</div>
          <div style="margin-top: 5px;">
            @if(strtolower($order->payment_status) === 'paid' || strtolower($order->payment_status) === 'complete')
            <span class="bold" style="text-transform: uppercase; color: #28a745; background-color: #e8f5e9; padding: 3px 10px; border-radius: 4px; border: 1px solid #28a745; display: inline-block; font-size: 13px;">PAID</span>
            @else
            <span class="bold" style="text-transform: uppercase; color: #dc3545; background-color: #ffebee; padding: 3px 10px; border-radius: 4px; border: 1px solid #dc3545; display: inline-block; font-size: 13px;">UNPAID</span>
            <span style="color: #84c529; font-weight: bold; margin-left: 5px;"> - PAY INVOICE</span>
            @endif
          </div>
        </td>
      </tr>
    </table>

    <!-- Address Section -->
    <table style="margin-bottom: 40px;">
      <tr>
        <td style="width: 50%;">
          <div class="bold">valokichu.com</div>
          <div class="gray">House 3, Road 3A, 1st , 2nd & 3rd Floor, Sector 15 ,</div>
          <div class="gray">( Near Uttara North Metro Station), Uttara, Dhaka -1230</div>
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
   <!-- Items Table -->
    <table style="margin-top: 20px;">
      <thead>
        <tr class="bg-light">
          <th style="width: 5%; padding: 8px; text-align: left; border-bottom: 1px solid #000;">#</th>
          <th style="width: 50%; padding: 8px; text-align: left; border-bottom: 1px solid #000;">Item Description</th>
          <th style="width: 10%; padding: 8px; text-align: center; border-bottom: 1px solid #000;">Qty</th>
          <th style="width: 15%; padding: 8px; text-align: right; border-bottom: 1px solid #000;">Rate</th>
          <th style="width: 20%; padding: 8px; text-align: right; border-bottom: 1px solid #000;">Amount</th>
        </tr>
      </thead>
      <tbody>
        @foreach($order->items as $index => $item)
        <tr>
          <td style="vertical-align: top; padding: 8px 5px;">{{ $index + 1 }}</td>
          <td style="vertical-align: top; padding: 8px 5px;">
            <table style="width: 100%; border: none;">
              <tr>
                @php
                  $itemImagePath = null;
                  $imgName = null;
                  
                  // 1. Try to find the selected color name
                  $selectedColorName = null;
                  if ($item->variation && !empty($item->variation->color)) {
                      $selectedColorName = $item->variation->color;
                  } elseif ($item->variation_snapshot) {
                      if (preg_match('/Color:\s*([^,]+)/i', $item->variation_snapshot, $matches)) {
                          $selectedColorName = trim($matches[1]);
                      }
                  }

                  // 2. Try to get image from variation first
                  if ($item->variation) {
                      $varImg = $item->variation->images->first();
                      if ($varImg && !empty($varImg->image)) {
                          $imgName = $varImg->image;
                      }
                  }

                  // 3. If no image yet, but we have a color name, try to match it in product->colors
                  if (!$imgName && $selectedColorName && $item->product && is_array($item->product->colors)) {
                      foreach ($item->product->colors as $color) {
                          if (isset($color['name']) && strtolower(trim($color['name'])) === strtolower(trim($selectedColorName))) {
                              if (!empty($color['image'])) {
                                  $imgName = $color['image'];
                                  break;
                              } elseif (!empty($color['color_image'])) {
                                  $imgName = $color['color_image'];
                                  break;
                              }
                          }
                      }
                  }

                  // 4. Fallback to product base image
                  if (!$imgName && $item->product) {
                      $imgName = $item->product->image;
                  }

                  // 5. Build storage file path
                  if ($imgName) {
                      $cleanImgName = ltrim(str_replace(['/storage/products/', 'storage/products/', '/products/', 'products/'], '', $imgName), '/');
                      $path = storage_path('app/public/products/' . $cleanImgName);
                      if (file_exists($path)) {
                          $itemImagePath = $path;
                      } else {
                          $pathDirect = storage_path('app/public/' . $cleanImgName);
                          if (file_exists($pathDirect)) {
                              $itemImagePath = $pathDirect;
                          }
                      }
                  }
                @endphp
                @if($itemImagePath && file_exists($itemImagePath))
                  <td style="width: 45px; padding-right: 10px; border: none; vertical-align: top;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($itemImagePath)) }}"
                         style="width: 35px; height: 35px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;">
                  </td>
                @endif
                <td style="border: none; vertical-align: top; padding: 0;">
                  <div class="bold" style="color: #111827; font-size: 11px; word-wrap: break-word; white-space: normal;">{{ $item->product_name }}</div>
                  @if($item->variation_snapshot)
                  <div class="gray" style="font-size: 10px; margin-top: 2px; word-wrap: break-word; white-space: normal;">{{ $item->variation_snapshot }}</div>
                  @endif
                </td>
              </tr>
            </table>
          </td>
          <td class="text-center" style="vertical-align: top; padding: 8px 5px;">{{ $item->quantity }}</td>
          <td class="text-right" style="vertical-align: top; padding: 8px 5px;">TK {{ number_format($item->unit_price, 2) }}</td>
          <td class="text-right bold" style="vertical-align: top; padding: 8px 5px; color: #111827;">TK {{ number_format($item->total_price, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <!-- Totals Section -->
    @php
      $originalSubtotal = 0;
      foreach ($order->items as $item) {
          $originalItemPrice = 0;
          if ($item->product) {
              $originalItemPrice = $item->product->sale_price ?? $item->product->base_price;
              if ($item->variation) {
                  $originalItemPrice += $item->variation->price_modifier;
              }
          } else {
              $originalItemPrice = $item->unit_price;
          }
          $originalSubtotal += $originalItemPrice * $item->quantity;
      }
      
      $actualItemsTotal = 0;
      foreach ($order->items as $item) {
          $actualItemsTotal += $item->unit_price * $item->quantity;
      }

      $bulkDiscount = max(0, $originalSubtotal - $actualItemsTotal);
      $totalDiscountAmount = $bulkDiscount + $order->discount;
    @endphp
    <table style="margin-top: 30px;">
      <tr>
        <td style="width: 60%;"></td>
        <td style="width: 40%;">
          <table style="width: 100%;">
            <tr>
              <td style="padding: 5px 0;">Sub Total</td>
              <td class="text-right" style="padding: 5px 0;">TK {{ number_format($originalSubtotal, 2) }}</td>
            </tr>
            @if($order->shipping_cost > 0)
            <tr>
              <td style="padding: 5px 0;">Shipping</td>
              <td class="text-right" style="padding: 5px 0;">TK {{ number_format($order->shipping_cost, 2) }}</td>
            </tr>
            @endif
            @if($totalDiscountAmount > 0)
            <tr>
              <td style="padding: 5px 0;">Discount</td>
              <td class="text-right" style="padding: 5px 0;">- TK {{ number_format($totalDiscountAmount, 2) }}</td>
            </tr>
            @endif
            <tr class="bg-light bold">
              <td style="padding: 8px;">Total</td>
              <td class="text-right" style="padding: 8px;">TK {{ number_format($order->total_price, 2) }}</td>
            </tr>
            <tr class="bg-light bold" style="margin-top: 5px;">
              <td style="padding: 8px;">Amount Due</td>
              <td class="text-right" style="padding: 8px; color: {{ (strtolower($order->payment_status) === 'paid' || strtolower($order->payment_status) === 'complete') ? '#28a745' : '#dc3545' }};">
                TK {{ (strtolower($order->payment_status) === 'paid' || strtolower($order->payment_status) === 'complete') ? '0.00' : number_format($order->total_price, 2) }}</td>
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
    <div style="margin-top: 15px; font-size: 11px; color: #555; border-top: 1px dashed #ddd; padding-top: 10px; line-height: 1.5;">
      <strong>Track Your Order:</strong> Visit <strong>valokichu.com/track-order</strong> and track using Order ID: <strong>{{ $order->id }}</strong> (or Order Number: <strong>{{ $order->order_number }}</strong>) and Phone: <strong>{{ $order->contact_number }}</strong>
    </div>
    <div style="margin-top: 10px;">
      Thank you for your order!
      <p style="margin: 3px 0 0 0;">Note: If you have any complain please contact us 01410643138</p>
    </div>
    {{-- <!-- Payment Info -->
    <div style="margin-top: 40px; font-size: 13px;">
      <div class="bold" style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Offline Payment
        Information:</div>

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
    </div> --}}

    <div style="margin-top: 60px;">
      <div style="width: 200px; border-top: 1px solid #000; padding-top: 5px;" class="text-center bold">Authorized
        Signature</div>
    </div>

  </div>
</body>

</html>