<?php

namespace App\Models\Backend\System;

use App\Models\Traits\CommonScope;
use App\Models\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * @method static static|Builder notifiableType($type) 通知對象類型
 * @method static static|Builder notifiableId($type) 通知對象 ID
 */
class Notifications extends DatabaseNotification
{

    use CommonScope, SerializeDate;

    /**
     * 獲取純文本
     * @return Attribute
     * @var $plain_text
     */
    public function plainText(): Attribute
    {
        return Attribute::make(get: function ($value, $attributes) {
            return Str::limit(strip_tags($this->data['message'] ?? ''), 100);
        });
    }


    /**
     * @method static static|Builder notifiableType($type) 通知對象類型
     * @param $query
     * @param $model
     * @return mixed
     */
    public function scopeNotifiableType($query, $model)
    {
        return $query->where('notifiable_type', $model);
    }

    /**
     * 範圍 - 通知對象
     * @method static static|Builder user($user) 通知對象
     * @param $query
     * @param $user
     * @return void
     */
    public function scopeUser($query, $user)
    {
        $query->notifiableType(get_class($user))->notifiableId($user->id);
    }


    /**
     * @method static static|Builder notifiableId($type) 通知對象 ID
     * @param $query
     * @param $model
     * @return mixed
     */
    public function scopeNotifiableId($query, $model)
    {
        return $query->where('notifiable_id', $model);
    }

}
