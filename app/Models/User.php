<?php

namespace App\Models;

use App\Notifications\CustomResetPasswordNotification;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, UsesUuidPrimaryKey;

    public const TEST_ACCOUNT_EMAILS = [
        'admin@gstplatform.com',
        'demo@gstplatform.com',
        'manager@gstplatform.com',
    ];

    protected $connection = 'mongodb';

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'google_id',
        'avatar',
        'email_verified_at',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function businessProfiles()
    {
        return $this->hasMany(BusinessProfile::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDemoUser(): bool
    {
        $email = Str::lower((string) $this->email);

        return $email === 'demo@gstplatform.com'
            || Str::startsWith(Str::before($email, '@'), 'demo');
    }

    public function isManager(): bool
    {
        $email = Str::lower((string) $this->email);
        $name = Str::lower((string) $this->name);

        return $this->role === 'manager'
            || $email === 'manager@gstplatform.com'
            || Str::contains(Str::before($email, '@'), 'manager')
            || Str::contains($name, 'manager');
    }

    public function isPasswordChangeRestricted(): bool
    {
        return $this->isAdmin() || $this->isDemoUser() || $this->isManager();
    }

    public function isTestAccount(): bool
    {
        return in_array(Str::lower((string) $this->email), self::TEST_ACCOUNT_EMAILS, true);
    }

    public function isTestAdmin(): bool
    {
        return $this->isAdmin() && Str::lower((string) $this->email) === 'admin@gstplatform.com';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }
}
