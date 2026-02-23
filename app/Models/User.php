<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'phone_number',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'image',
        'address',
        'fcm_token',
        'role',
        'is_active',
        'is_staff',
        'staff_type',
        'is_verified',
        'phone_number_verified_at',
        'email_verified_at',
        'password',
        "refer_code",
        "refer_by"
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected function casts(): array
    {
        return [
            "id" => "integer",
            'email' => 'string', //unique and nullable
            'phone_number' => 'string', //required and unique
            'first_name' => 'string', //required
            'last_name' => 'string', //required
            'gender' => 'string', //required
            'date_of_birth' => 'date', //required
            'image' => 'string', //nullable
            'address' => 'string', //nullable
            'fcm_token' => 'string', //nullable
            'role' => 'string', //enum: admin, sub_admin, user
            'is_active' => 'boolean', //required
            'is_verified' => 'boolean',
            "refer_code" => "string",
            "refer_by" => "integer", // relation with user
            'phone_number_verified_at' => 'datetime',
            'email_verified_at' => 'datetime', //nullable
            'password' => 'hashed', //required
            'created_at' => 'datetime', //nullable
            'updated_at' => 'datetime', //nullable
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isChildAdmin(): bool
    {
        return $this->role === 'child_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'super_admin' || $this->role === 'child_admin';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isDropshipper(): bool
    {
        return $this->role === 'dropshipper';
    }

    public function isSubDropshipper(): bool
    {
        return $this->role === 'sub_dropshipper';
    }

    public function isSubSubDropshipper(): bool
    {
        return $this->role === 'sub_sub_dropshipper';
    }

    public function isAnyDropshipper(): bool
    {
        return in_array($this->role, ['dropshipper', 'sub_dropshipper', 'sub_sub_dropshipper']);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'refer_by');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'refer_by');
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function dropshipperProfile()
    {
        return $this->hasOne(DropShiper::class, 'customer_id');
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return asset('assets/images/placeholder.png');
        }
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }
        return asset('storage/users/' . $this->image);
    }

    public function getStoreNameAttribute()
    {
        if ($this->isAnyDropshipper()) {
            return $this->dropshipperProfile?->name ?? ($this->first_name . ' ' . $this->last_name);
        }
        return $this->first_name . ' ' . $this->last_name;
    }
}
