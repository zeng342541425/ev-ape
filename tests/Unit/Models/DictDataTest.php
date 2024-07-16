<?php

namespace Models;

use App\Models\Backend\System\DictData;
use Tests\TestCase;

class DictDataTest extends TestCase
{

    public function testSelectAll()
    {
        dd(DictData::selectAll());
    }
}
