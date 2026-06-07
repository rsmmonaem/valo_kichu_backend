<?php
\nnamespace App\Services;
\nuse App\Models\User;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
\nclass WalletService
{
    /**
     * Credit a user's wallet and create a transaction record
     */
    public static function creditWallet($userId, $amount, $type, $description, $orderId = null)
    {
        if ($amount <= 0) return null;
\n        return DB::transaction(function () use ($userId, $amount, $type, $description, $orderId) {
            $transaction = WalletTransaction::create([
                'user_id' => $userId,
                'amount' => $amount,
                'type' => 'credit',
                'description' => $description,
                'reference_type' => 'order',
                'reference_id' => $orderId,
            ]);
\n            // Note: If we had a 'wallet_balance' column on users table, we'd update it here.
            // For now, balances are calculated from transactions sum.
\n            return $transaction;
        });
    }
\n    /**
     * Distribute commissions to the dropshipper and their parent hierarchy
     */
    public static function distributeCommissions(Order $order)
    {
        $order->load(['items.product', 'user.parent.parent']);
\n        if (!$order->user->isAnyDropshipper()) {
            return;
        }
\n        foreach ($order->items as $item) {
            $product = $item->product;
            if (!$product) continue;
\n            $retailPrice = (float) $product->base_price;
            $purchasePrice = (float) $item->purchase_price ?: (float) $product->purchase_price;
            $profitPool = max(0, $retailPrice - $purchasePrice);
\n            if ($profitPool <= 0) continue;
\n            $seller = $order->user;
            $qty = $item->quantity;
\n            // Tiered Margins (percentage of profit pool)
            $margins = [
                'dropshipper' => (float) getBusinessSetting('dropshipper_global_margin', 70),
                'sub_dropshipper' => (float) getBusinessSetting('sub_dropshipper_global_margin', 60),
                'sub_sub_dropshipper' => (float) getBusinessSetting('sub_sub_dropshipper_global_margin', 50),
            ];
\n            if ($seller->role === 'sub_sub_dropshipper') {
                // Sub-Sub gets 10%
                $subSubProfit = $profitPool * ($margins['sub_sub_dropshipper'] / 100) * $qty;
                self::creditWallet($seller->id, $subSubProfit, 'commission', "Commission for Order #{$order->order_number} (Item: {$item->product_name})", $order->id);
\n                // Parent (Sub) gets (20% - 10%) = 10%
                if ($seller->parent && $seller->parent->role === 'sub_dropshipper') {
                    $subProfit = $profitPool * (($margins['sub_dropshipper'] - $margins['sub_sub_dropshipper']) / 100) * $qty;
                    self::creditWallet($seller->parent->id, $subProfit, 'referral_commission', "Indirect Commission from {$seller->first_name} for Order #{$order->order_number}", $order->id);
                    
                    // Grand-parent (Dropshipper) gets (30% - 20%) = 10%
                    if ($seller->parent->parent && $seller->parent->parent->role === 'dropshipper') {
                        $mainProfit = $profitPool * (($margins['dropshipper'] - $margins['sub_dropshipper']) / 100) * $qty;
                        self::creditWallet($seller->parent->parent->id, $mainProfit, 'referral_commission', "Indirect Commission from {$seller->first_name} for Order #{$order->order_number}", $order->id);
                    }
                }
            } elseif ($seller->role === 'sub_dropshipper') {
                // Sub gets 20%
                $subProfit = $profitPool * ($margins['sub_dropshipper'] / 100) * $qty;
                self::creditWallet($seller->id, $subProfit, 'commission', "Commission for Order #{$order->order_number} (Item: {$item->product_name})", $order->id);
\n                // Parent (Dropshipper) gets (30% - 20%) = 10%
                if ($seller->parent && $seller->parent->role === 'dropshipper') {
                    $mainProfit = $profitPool * (($margins['dropshipper'] - $margins['sub_dropshipper']) / 100) * $qty;
                    self::creditWallet($seller->parent->id, $mainProfit, 'referral_commission', "Indirect Commission from {$seller->first_name} for Order #{$order->order_number}", $order->id);
                }
            } elseif ($seller->role === 'dropshipper') {
                // Main dropshipper gets full 30%
                $mainProfit = $profitPool * ($margins['dropshipper'] / 100) * $qty;
                self::creditWallet($seller->id, $mainProfit, 'commission', "Commission for Order #{$order->order_number} (Item: {$item->product_name})", $order->id);
            }
        }
    }
}
