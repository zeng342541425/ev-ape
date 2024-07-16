<?php

namespace App\Models\Backend\User;

use App\Models\BaseModel;
use App\Models\Parking\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static static|Builder phone($phone) 手機號碼
 * @method static static|Builder email($email) 信箱
 */
class User extends BaseModel
{
    use LogsActivity;

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'users';


    /**
     * 指示模型是否主動維護時間戳.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * 不可批量賦值的屬性
     *
     * @var array
     */
    protected $guarded = [
        'id'
    ];

    protected $hidden = [
        'password', 'remember_token', 'updated_at'
    ];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Register')
            ->logFillable()
            ->logUnguarded();
    }

    /**
     * 範圍 - 手機號碼
     * @method static static|Builder phone($phone) 手機號碼
     * @param $query
     * @param $phone
     * @return mixed
     */
    public function scopePhone($query, $phone)
    {
        return $query->where('phone', $phone);
    }

    /**
     * 範圍 - 信箱
     * @method static static|Builder email($email) 信箱
     * @param $query
     * @param $email
     * @return mixed
     */
    public function scopeEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public function brand(): HasOne
    {
        return $this->hasOne(Brand::class, 'id', 'brand_id');
    }

}

