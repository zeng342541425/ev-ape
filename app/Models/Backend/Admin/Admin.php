<?php

namespace App\Models\Backend\Admin;

use App\Models\Traits\CommonScope;
use App\Models\Traits\SerializeDate;
use App\Util\FunctionReturn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use stdClass;


class Admin extends Authenticatable
{
    use Notifiable, HasRoles, LogsActivity, SerializeDate, CommonScope, HasApiTokens;

    const JWT_CUSTOM_CLAIM_ROLE = 'admin';

    protected $table = 'admins';

    /**
     * 超級管理員
     * @var string
     */
    public static $super_admin = 'admin';

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
        'password', 'remember_token'
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
     * 管理員-關聯-角色
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function modelHasRoles()
    {
        return $this->hasMany(ModelHasRoles::class, 'model_id', 'id')
            ->where('model_type', self::class);
    }

    /**
     * 角色 Ids
     * @return Attribute
     */
    public function roleIds(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return $this->roles->pluck('id');
        });
    }

    /**
     * 排除超級管理員
     * @method static Builder notSuperAdmin()
     * @param Builder $query
     * @return Builder
     */
    public function scopeNotSuperAdmin(Builder $query)
    {
        return $query->where('username', '<>', Admin::$super_admin);
    }

    /**
     * 超級管理員
     * @method static Builder superAdmin()
     * @param Builder $query
     * @return Builder
     */
    public function scopeSuperAdmin(Builder $query)
    {
        return $query->where('username', Admin::$super_admin);
    }


    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('admin')
            ->logFillable()
            ->logUnguarded();
    }

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
     * 範圍 - 帳號
     * @method static static|Builder username($username) 帳號
     * @param $query
     * @param $username
     * @return mixed
     */
    public function scopeUsername($query, $username)
    {
        return $query->where('username', $username);
    }

    /**
     * 範圍 - 信箱
     * @method static static|Builder email($email) 信箱
     * @param $query
     * @param $username
     * @return mixed
     */
    public function scopeEmail($query, $email)
    {
        return $query->where('email', $email);
    }


    /**
     * 範圍 - 帳號或信箱
     * @method static static|Builder usernameOrEmail($username, $email = null) 帳號或信箱
     * @param $query
     * @param $username
     * @param $email
     * @return mixed
     */
    public function scopeUsernameOrEmail($query, $username, $email = null)
    {
        $email = $email ?? $username;
        return $query->where(function ($query) use ($username, $email) {
            $query->where('username', $username)
                ->orWhere('email', $email);
        });
    }


    public static function selectAll(): Collection
    {
        return Admin::select(['id', 'name'])->get();
    }
}
