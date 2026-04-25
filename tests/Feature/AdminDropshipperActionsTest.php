<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AdminDropshipperActionsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $dropshipper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'phone_number' => '0987654321',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->dropshipper = User::create([
            'first_name' => 'Test',
            'last_name' => 'Dropshipper',
            'email' => 'test@example.com',
            'phone_number' => '1234567890',
            'password' => Hash::make('password'),
            'role' => 'dropshipper',
            'is_approved' => true,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_dropshipper()
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/v1/dropshipping/users/{$this->dropshipper->id}", [
                'first_name' => 'Updated',
                'margin' => 15,
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Updated', $this->dropshipper->fresh()->first_name);
        $this->assertEquals(15, $this->dropshipper->fresh()->dropshipper_margin);
    }

    public function test_admin_can_delete_dropshipper()
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/v1/dropshipping/users/{$this->dropshipper->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $this->dropshipper->id]);
    }

    public function test_admin_can_toggle_dropshipper_status()
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        // Banning
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/v1/dropshipping/users/{$this->dropshipper->id}/toggle-status");

        $response->assertStatus(200);
        $this->assertFalse($this->dropshipper->fresh()->is_active);

        // Unbanning
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/v1/dropshipping/users/{$this->dropshipper->id}/toggle-status");

        $response->assertStatus(200);
        $this->assertTrue($this->dropshipper->fresh()->is_active);
    }
}
