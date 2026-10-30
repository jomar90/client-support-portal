<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout_use_the_session(): void
    {
        $user = User::factory()->create();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->getJson('/api/user')->assertOk()->assertJsonPath('id', $user->id);
        $this->postJson('/logout')->assertOk();
        $this->assertGuest('web');
        // Forget the guard instance between requests as a real HTTP request does.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/tickets')->assertUnauthorized();
    }

    public function test_password_reset_email_token_submission_and_new_login(): void
    {
        Notification::fake();
        Event::fake([PasswordReset::class]);
        $user = User::factory()->create();
        $this->postJson('/forgot-password', ['email' => $user->email])->assertOk();
        $token = '';
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token, $user) {
            $token = $notification->token;
            $mail = $notification->toMail($user);
            $this->assertStringContainsString('/reset-password/'.$token, $mail->actionUrl);
            $this->assertStringContainsString('email='.urlencode($user->email), $mail->actionUrl);

            return true;
        });
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        $this->postJson('/reset-password', [
            'email' => $user->email, 'token' => $token,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertOk();
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        Event::assertDispatched(PasswordReset::class);
        $this->assertGuest();
        // Reset tokens are single-use.
        $this->postJson('/reset-password', [
            'email' => $user->email, 'token' => $token,
            'password' => 'another-password', 'password_confirmation' => 'another-password',
        ])->assertUnprocessable();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])->assertUnprocessable();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'new-password-123'])->assertOk();
    }

    public function test_reset_rejects_invalid_expired_and_wrong_user_tokens(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = Password::createToken($user);
        $form = ['email' => $user->email, 'token' => 'invalid',
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->postJson('/reset-password', $form)->assertUnprocessable();
        $form['token'] = $token;
        $form['email'] = $other->email;
        $this->postJson('/reset-password', $form)->assertUnprocessable();
        $form['email'] = $user->email;
        $this->travel(61)->minutes();
        $this->postJson('/reset-password', $form)->assertUnprocessable();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_validates_password_and_confirmation(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->postJson('/reset-password', ['email' => $user->email, 'token' => $token,
            'password' => 'short', 'password_confirmation' => 'different'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/reset-password', ['email' => $user->email, 'token' => $token,
            'password' => 'long-enough-password', 'password_confirmation' => 'different-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/reset-password', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'token', 'password']);
        $this->postJson('/forgot-password', ['email' => 'invalid'])->assertUnprocessable();
    }

    public function test_unknown_email_does_not_disclose_accounts(): void
    {
        Notification::fake();
        $this->postJson('/forgot-password', ['email' => 'missing@example.com'])->assertOk()
            ->assertJsonPath('message', 'If this email address is registered and eligible, a password reset link will be sent.');
        Notification::assertNothingSent();
    }
}
