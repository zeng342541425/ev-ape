<?php

namespace App\Models\Frontend\User;

use App\Models\Traits\CommonScope;
use App\Models\Traits\SerializeDate;
use App\Util\FunctionReturn;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use Notifiable, LogsActivity, CommonScope, SerializeDate, HasApiTokens;

    const JWT_CUSTOM_CLAIM_ROLE = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
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
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return ['role' => self::JWT_CUSTOM_CLAIM_ROLE];
    }

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logFillable()
            ->logUnguarded();
    }

    /**
     * 創建
     *
     * @param array $attributes
     * @return User
     */
    public static function create(array $attributes): User
    {
        $attributes['password'] = Hash::make($attributes['password']);
        return static::query()->create($attributes);
    }

    /**
     * 更新
     *
     * @param array $data
     * @return array
     */
    public static function updateSave(array $data): FunctionReturn
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = User::find($data['id']);
        unset($data['id']);

        return new FunctionReturn($user->update($data), '', [
            'user' => $user
        ]);
    }
}
