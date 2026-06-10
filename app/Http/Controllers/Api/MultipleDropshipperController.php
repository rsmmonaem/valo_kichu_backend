<?php

namespace App\Http\Controllers\Api;

use App\Helpers\DropshipperOrderHelper;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class MultipleDropshipperController extends Controller
{
    public function placeOrder(Request $request)
    {
        $dropshipper = Order::where("order_number", $request->input('order_number'))->with(["items", "user", "items.product"])->first();
        if (!$dropshipper) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $poat_url = 'https://jsonplaceholder.typicode.com/posts';
        $total_items = $dropshipper->items->count();
        $products = [];

        foreach ($dropshipper->items as $item) {
            $products[] = [
                'id' => $item->product->api_id ?? null,
                'product_code' => $item->product->product_code ?? null,
                'product_name' => $item->product->name ?? null,
                'quantity' => $item->quantity,
                'price' => $item->unit_price,
                'total_price' => $item->total_price,
            ];
        }
        $user_first_name = $dropshipper->user->first_name ?? null;
        $user_last_name = $dropshipper->user->last_name ?? null;
        // dd($dropshipper);
        $payload = [
            //customer details
            'customer_name' => $user_first_name . ' ' . $user_last_name ?? null,
            'customer_phone' => $dropshipper->contact_number ?? null,
            'customer_address' => $dropshipper->shipping_address ?? null,
            'status' => $dropshipper->status ?? null,
            'payment_status' => $dropshipper->payment_status ?? null,

            //order details
            'invoice_no' => $dropshipper->order_number,
            'city_id' => $dropshipper['city'] ?? null,
            'sub_city_id' => $dropshipper['city'] ?? null,
            'shipping_cost' => $dropshipper->shipping_cost,
            'total_item' => $total_items,
            'discount' => $dropshipper->discount,
            'total_price' => $dropshipper->total_price + $dropshipper->shipping_cost - $dropshipper->discount,
            'order_note' => $request->input('order_note'),

            //product details
            'products' => $products,

            //Reseller Name
            'reseller_name' => "N.I.Biz software",
            'reseller_phone' => "01700000000" ,
            'reseller_email' => "reseller@nibiz.com",
            'reseller_address' => "123 Reseller St, City, Country",
        ];
        $headers = [
            'api_key' => "your_api_key_here",
            'api_secret' => "your_api_secret_here",
        ];

        $result = DropshipperOrderHelper::post(
            $poat_url,
            $payload
        );

        return response()->json($result);
    }
}
