<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_unauthenticated_user_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('ميزان فلو');
        $response->assertSee('تسجيل الدخول للنظام');
    }

    public function test_users_can_authenticate_using_username(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => 'secret123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'testuser',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_can_authenticate_using_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@mizanflow.test',
            'password' => 'secret123',
            'role' => 'manager',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'test@mizanflow.test',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => 'correct-password',
        ]);

        $response = $this->post('/login', [
            'login' => 'testuser',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'username' => 'inactiveuser',
            'password' => 'secret123',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'login' => 'inactiveuser',
            'password' => 'secret123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_unauthenticated_users_are_redirected_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'سالم المدير',
            'username' => 'salem',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('سالم المدير');
        $response->assertSee('المدير العام');
        $response->assertSee('لوحة التحكم والمؤشرات التشغيلية');
        $response->assertSee('المستودع الرئيسي (المركزي)');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
