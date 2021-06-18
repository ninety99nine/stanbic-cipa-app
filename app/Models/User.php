<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\CommonTraits;
use App\Traits\UserTraits;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use CommonTraits;
    use UserTraits;
    use HasRoles;

    protected $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /*
     *  Scope:
     *  Returns users that are being searched
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('name', 'like', '%'.$searchTerm.'%')->orWhere('email', 'like', '%'.$searchTerm.'%');
    }

    /*
     *  Scope:
     *  Returns users that match the given user roles
     */
    public function scopeUserRoles($query, $roles)
    {
        if( is_array($roles) ){
            return $query->whereHas('roles', function (Builder $query) use ($roles){
                $query->whereIn('name', $roles);
            });
        }else{
            return $query->whereHas('roles', function (Builder $query) use ($roles){
                $query->where('name', $roles);
            });
        }
    }

    /*
     *  Scope:
     *  Returns users that match the given user roles
     */
    public function scopeUserPermissions($query, $permissions)
    {
        if( is_array($permissions) ){
            return $query->whereHas('roles.permissions', function (Builder $query) use ($permissions){
                $query->whereIn('name', $permissions);
            });
        }else{
            return $query->whereHas('roles.permissions', function (Builder $query) use ($permissions){
                $query->where('name', $permissions);
            });
        }
    }

    public function extractPermissions(){
        return collect($this->getAllPermissions())->map(function($permission){
            return $permission['name'];
        });
    }

}
