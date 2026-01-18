<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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

    /**
     * Get the travel requests created by the user.
     */
    public function travelRequests(): HasMany
    {
        return $this->hasMany(TravelRequest::class, 'requester_user_id');
    }

    /**
     * Get the travel requests approved by the user.
     */
    public function approvedTravelRequests(): HasMany
    {
        return $this->hasMany(TravelRequest::class, 'approved_by');
    }

    /**
     * Get the travel requests cancelled by the user.
     */
    public function cancelledTravelRequests(): HasMany
    {
        return $this->hasMany(TravelRequest::class, 'cancelled_by');
    }
}
