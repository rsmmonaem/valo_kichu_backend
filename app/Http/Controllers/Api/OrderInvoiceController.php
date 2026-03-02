<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Mail\OrderInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderInvoiceController extends Controller
{
    /**
     * Download the invoice PDF.
     */
    public function download($orderId)
    {
        $order = Order::with('items')->where('id', $orderId)->orWhere('order_number', $orderId)->firstOrFail();

        $pdf = Pdf::loadView('pdf.invoice', compact('order'));

        return $pdf->download('invoice-' . $order->order_number . '.pdf');
    }

    /**
     * Send the invoice PDF via email.
     */
    public function sendInvoice($orderId, Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $order = Order::with('items')->where('id', $orderId)->orWhere('order_number', $orderId)->firstOrFail();

        // Update email if it was missing or different (optional, but requested by flow)
        if ($request->has('email') && $order->email !== $request->email) {
            $order->update(['email' => $request->email]);
        }

        $pdf = Pdf::loadView('pdf.invoice', compact('order'));
        $pdfData = $pdf->output();

        try {
            Mail::to($request->email)->send(new OrderInvoice($order, $pdfData));
            
            return response()->json([
                'status' => true,
                'message' => 'Invoice sent successfully to ' . $request->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send invoice: ' . $e->getMessage()
            ], 500);
        }
    }
}
