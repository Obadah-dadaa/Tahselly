<?php

namespace App\Models;

use App\Enums\Role;
use App\Mail\AccountLinkMail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

#[Fillable(['company_id', 'name', 'email', 'password', 'role', 'phone', 'locale', 'theme', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Branded, bilingual password-reset email instead of the framework default.
     */
    public function sendPasswordResetNotification($token): void
    {
        $minutes = (int) config('auth.passwords.users.expire', 60);
        $url = route('password.reset', ['token' => $token, 'email' => $this->email]);
        Mail::to($this->email)->locale($this->locale ?? app()->getLocale())->send(new AccountLinkMail($this, 'reset', $url, $minutes));
    }

    /**
     * Branded, bilingual email-confirmation message with a signed link.
     */
    public function sendEmailVerificationNotification(): void
    {
        $minutes = (int) config('auth.verification.expire', 60);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), ['id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification())]);
        Mail::to($this->email)->locale($this->locale ?? app()->getLocale())->send(new AccountLinkMail($this, 'verify', $url, $minutes));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isDeveloper(): bool
    {
        return $this->role === Role::Developer;
    }

    /**
     * Platform staff (developer or platform owner) see every tenant.
     */
    public function isPlatformStaff(): bool
    {
        return in_array($this->role, [Role::Developer, Role::Platform], true);
    }

    public function isOwner(): bool
    {
        return $this->role === Role::Owner;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role->value, $roles, true);
    }
}
