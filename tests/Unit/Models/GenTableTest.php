<?php

namespace Models;

use App\Models\Backend\System\DictType;
use App\Models\Backend\System\GenTable;
use App\Models\Backend\System\GenTableColumn;
use App\Util\Gen;
use Tests\TestCase;

class GenTableTest extends TestCase
{

    public function testGetImportTableList()
    {
        dd(Gen::getImportTableList());
    }

    public function testImportTableList()
    {
        dd(Gen::importTable('test_db'));
    }

    public function testGen()
    {
        dd(Gen::gen('test_db', 0, '小學生'));
    }

    public function testSetDict()
    {
        $m = GenTableColumn::where('name', 'sex')->first();
        $d = DictType::where('type', 'sex')->first();
        $m->setDict($d);
        dd($m);
    }

    public function testSetType()
    {
        $m = GenTableColumn::where('name', 'avatar')->first();
        $m->setType('file');
        $m = GenTableColumn::where('name', 'content')->first();
        $m->setType('editor');
    }

    public function testGenTest()
    {
        Gen::importTable('test_db');
        $m = GenTableColumn::where('name', 'sex')->first();
        $d = DictType::where('type', 'sex')->first();
        $m->setDict($d);
        $m = GenTableColumn::where('name', 'avatar')->first();
        $m->setType('image');
        $m = GenTableColumn::where('name', 'dangan')->first();
        $m->setType('file');
        $m = GenTableColumn::where('name', 'content')->first();
        $m->setType('editor');
        $m = GenTableColumn::where('name', 'admin_id')->first();
        $m->setForeignShow(['name', 'type']);
        $m = GenTableColumn::where('name', 'number')->first();
        $m->setUnique();
        dd(Gen::gen('test_db', 0, '小學生'));
    }

    public function testSetForeignShow()
    {
        $m = GenTableColumn::where('name', 'admin_id')->first();
        $m->setForeignShow(['name', 'type']);
    }

    public function testGenTableTest()
    {
        $data = Gen::getTableInfo('exception_errors');
        Gen::importTable('exception_errors');
        Gen::importTable('password_resets');
//        dd(Gen::gen('gen_tables', 0, '代碼生成'));
    }
}
