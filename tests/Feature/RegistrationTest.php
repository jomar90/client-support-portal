<?php

namespace Tests\Feature;

use App\Mail\RegistrationConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function registrationData(): array
    {
        return ['first_name' => 'New', 'last_name' => 'Customer', 'email' => 'new@example.com',
            'phone_number' => '+31 12345678', 'password' => 'strong-password', 'password_confirmation' => 'strong-password'];
    }

    public function test_registration_hashes_password_ignores_forged_role_and_sends_confirmation(): void
    {
        Mail::fake();
        $data = $this->registrationData();
        $this->postJson('/register', array_merge($data, ['admin' => true, 'email_verified_at' => now()]))
            ->assertCreated()->assertJsonStructure(['message']);
        $user = User::sole();
        $this->assertFalse($user->admin);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check($data['password'], $user->password));
        $this->assertSame($data['phone_number'], $user->phone_number);
        Mail::assertSent(RegistrationConfirmation::class, fn ($mail) => $mail->hasTo($user->email) && $mail->user->id === $user->id);
        Mail::assertSentCount(1);
        $this->assertGuest();
        $this->postJson('/login', ['email' => $user->email, 'password' => $data['password']])->assertOk();
        $this->getJson('/api/tickets')->assertOk()->assertExactJson([]);
        $this->getJson('/api/users')->assertForbidden();
    }

    public function test_registration_validates_required_fields_duplicate_email_and_confirmation(): void
    {
        Mail::fake();
        $this->postJson('/register', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password']);
        $data = $this->registrationData();
        $this->postJson('/register', array_merge($data, ['password_confirmation' => 'different']))
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/register', array_merge($data, ['password' => 'short', 'password_confirmation' => 'short']))
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/register', array_merge($data, ['email' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('users', 0);
        User::factory()->create(['email' => $data['email']]);
        $this->postJson('/register', $data)->assertUnprocessable()->assertJsonValidationErrors('email');
        Mail::assertNothingSent();
    }

    public function test_registration_allows_an_omitted_phone_number(): void
    {
        Mail::fake();
        $data = $this->registrationData();
        unset($data['phone_number']);
        $this->postJson('/register', $data)->assertCreated();
        $this->assertNull(User::sole()->phone_number);
    }

    public function test_confirmation_mail_renders_login_link_and_no_password(): void
    {
        $user = User::factory()->create(['first_name' => 'WelcomeTest']);
        $mail = new RegistrationConfirmation($user);
        $mail->assertSeeInHtml('WelcomeTest');
        $mail->assertSeeInHtml(url('/login'));
        $mail->assertDontSeeInHtml($user->password);
    }

    public function test_mail_failure_rolls_back_registration_so_the_user_can_retry(): void
    {
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $this->postJson('/register', $this->registrationData())->assertStatus(500);
        $this->assertDatabaseCount('users', 0);
    }
}
