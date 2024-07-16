<?php

namespace App\Models;

use App\Models\Traits\CommonScope;
use App\Models\Traits\SerializeDate;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;


class BaseModel extends Model
{
    use SerializeDate, CommonScope;

    public function table(): string
    {
        return $this->table;
    }
}
