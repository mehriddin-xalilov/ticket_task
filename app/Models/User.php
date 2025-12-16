<?php

namespace App\Models;

use App\Helpers\Traits\FileableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, FileableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $table = 'users';
    protected mixed $fileableAttributes = ['photo'];
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected $fillable = [
        "id",
        "name",
        "email_verified_at",
        "password",
        "email",
        "login",
        "phone",
        "status",
        "remember_token",
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
        ];
    }

    public function userRole(): HasOne
    {
        return $this->hasOne(UserRole::class, 'user_id');
    }

    public function getRoleAttribute()
    {
        return $this->userRole?->role;
    }

    public function detail(): BelongsTo
    {
        return $this->BelongsTo(Student::class);
    }

    public function vacancies()
    {
        return $this->hasMany(Vacancy::class);
    }

    public function vacancyViews()
    {
        return $this->hasMany(VacancyView::class);
    }

    public function vacancyUsers()
    {
        return $this->hasMany(VacancyUser::class);
    }

}







