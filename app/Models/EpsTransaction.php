<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * EpsTransaction Model
 * 
 * Stores all EPS Payment Gateway transactions with full audit trail.
 * Provides deduplication via idempotency_key and internal transaction tracking
 * via merchant_transaction_id.
 * 
 * Status Flow:
 *   initiated -> pending -> verified -> completed
 *                        -> failed
 *                        -> cancelled
 */
class EpsTransaction extends Model
{
    use HasFactory;

    protected $table = 'eps_transactions';

    // Status constants
    const STATUS_INITIATED  = 'initiated';   // Payment record created, EPS API not yet called
    const STATUS_PENDING    = 'pending';      // EPS API called, redirect URL received, waiting for user
    const STATUS_VERIFIED   = 'verified';     // Server-side verification passed (CheckPaymentStatus)
    const STATUS_COMPLETED  = 'completed';    // Order created, payment fully processed
    const STATUS_FAILED     = 'failed';       // Payment failed (from callback or verification)
    const STATUS_CANCELLED  = 'cancelled';    // Payment cancelled by user

    /**
     * Valid status transitions map.
     * Key = current status, Value = array of allowed next statuses.
     */
    const STATUS_TRANSITIONS = [
        self::STATUS_INITIATED => [self::STATUS_PENDING, self::STATUS_FAILED],
        self::STATUS_PENDING   => [self::STATUS_VERIFIED, self::STATUS_FAILED, self::STATUS_CANCELLED],
        self::STATUS_VERIFIED  => [self::STATUS_COMPLETED, self::STATUS_FAILED],
        self::STATUS_COMPLETED => [],  // Terminal state
        self::STATUS_FAILED    => [],  // Terminal state
        self::STATUS_CANCELLED => [],  // Terminal state
    ];

    protected $fillable = [
        'idempotency_key',
        'merchant_transaction_id',
        'eps_transaction_id',
        'order_id',
        'payment_info_id',
        'user_id',
        'amount',
        'currency',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'eps_request_payload',
        'eps_response_payload',
        'eps_callback_payload',
        'eps_verification_payload',
        'redirect_url',
        'ip_address',
        'user_agent',
        'initiated_at',
        'pending_at',
        'verified_at',
        'completed_at',
        'failed_at',
        'cancelled_at',
        'verification_attempts',
    ];

    protected $casts = [
        'amount'                    => 'decimal:2',
        'eps_request_payload'       => 'array',
        'eps_response_payload'      => 'array',
        'eps_callback_payload'      => 'array',
        'eps_verification_payload'  => 'array',
        'initiated_at'              => 'datetime',
        'pending_at'                => 'datetime',
        'verified_at'               => 'datetime',
        'completed_at'              => 'datetime',
        'failed_at'                 => 'datetime',
        'cancelled_at'              => 'datetime',
        'verification_attempts'     => 'integer',
    ];

    /**
     * Hidden fields (sensitive data should not appear in API responses)
     */
    protected $hidden = [
        'eps_request_payload',
        'eps_response_payload',
        'eps_callback_payload',
        'eps_verification_payload',
        'ip_address',
        'user_agent',
    ];

    // ─── Relationships ─────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentInfo()
    {
        return $this->belongsTo(PaymentInfo::class, 'payment_info_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Status Helpers ────────────────────────────────────────

    /**
     * Check if a status transition is valid.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowed);
    }

    /**
     * Transition to a new status with validation.
     * Throws exception if transition is invalid.
     */
    public function transitionTo(string $newStatus): self
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Invalid status transition: {$this->status} -> {$newStatus} " .
                "for EPS transaction #{$this->id} (merchant_txn: {$this->merchant_transaction_id})"
            );
        }

        $this->status = $newStatus;

        // Set corresponding timestamp
        $timestampField = "{$newStatus}_at";
        if (in_array($timestampField, $this->fillable)) {
            $this->{$timestampField} = now();
        }

        return $this;
    }

    /**
     * Check if this transaction is in a terminal (final) state.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Check if this transaction was successfully verified.
     */
    public function isVerified(): bool
    {
        return in_array($this->status, [
            self::STATUS_VERIFIED,
            self::STATUS_COMPLETED,
        ]);
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeByIdempotencyKey($query, string $key)
    {
        return $query->where('idempotency_key', $key);
    }

    public function scopeByMerchantTransactionId($query, string $id)
    {
        return $query->where('merchant_transaction_id', $id);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeNotTerminal($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ]);
    }
}
