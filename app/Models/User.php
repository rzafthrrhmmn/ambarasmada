<?php

namespace App\Models;

use App\Notifications\AccountActivationNotification;
use App\Notifications\PasswordResetNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

#[Fillable(['username', 'name', 'email', 'password', 'role', 'is_active', 'status', 'foto', 'email_verified_at', 'settings'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    protected $fillable = ['username', 'name', 'email', 'password', 'role', 'is_active', 'status', 'foto', 'email_verified_at', 'settings'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function skuSubmissions(): HasMany
    {
        return $this->hasMany(SkuSubmission::class, 'verified_by');
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'verified_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Kirim email aktivasi akun.
     */
    public function sendActivationEmail(): bool
    {
        if (blank($this->email) || $this->hasVerifiedEmail()) {
            return false;
        }

        try {
            $this->notify(new AccountActivationNotification);

            Log::info('[activation-email] Terkirim ke '.$this->email, [
                'user_id' => $this->getKey(),
                'username' => $this->username,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[activation-email] Gagal: '.$e->getMessage(), [
                'user_id' => $this->getKey(),
                'email' => $this->email,
            ]);

            return false;
        }
    }

    public function sendEmailVerificationNotification()
    {
        $this->sendActivationEmail();
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordResetNotification($token));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'status' => 'string',
            'password' => 'hashed',
            'settings' => 'array',
        ];
    }
}
