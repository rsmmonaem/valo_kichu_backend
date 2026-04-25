<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\DropshipperApprovedMail;

class DropshipperApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_dropshipper_is_not_approved_by_default()
    {
        $response = $this->postJson('/api/register', [
            'phone_number' => '1234567890',
            'password' => 'password',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => 'dropshipper'
        ]);

        $response->assertStatus(201);
        $user = User::where('phone_number', '1234567890')->first();
        $this->assertFalse($user->is_approved);
    }

    public function test_unapproved_dropshipper_cannot_login()
    {
        $user = User::create([
            'phone_number' => '1234567890',
            'password' => Hash::make('password'),
            'role' => 'dropshipper',
            'is_approved' => false,
            'is_active' => true,
            'is_verified' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'phone_number' => '1234567890',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error', 'Your dropshipper account is pending admin approval.');
    }

    public function test_admin_can_approve_dropshipper_and_email_is_sent()
    {
        Mail::fake();

        $admin = User::create([
            'phone_number' => '0987654321',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $user = User::create([
            'phone_number' => '1234567890',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'dropshipper',
            'is_approved' => false,
            'is_active' => true,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/v1/dropshipping/users/{$user->id}/approve");

        $response->assertStatus(200);
        $this->assertTrue($user->fresh()->is_approved);

        Mail::assertSent(DropshipperApprovedMail::class, function ($mail) use ($user) {
            return $mail->hasTo('test@example.com') && $mail->user->id === $user->id;
        });
    }
}
