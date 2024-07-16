<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends BaseModel
{

    protected $table = 'regions';

    protected $hidden = ['updated_at'];

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

    public function villages(): HasMany
    {

        return $this->hasMany(self::class, 'pid', 'id');
    }

}
