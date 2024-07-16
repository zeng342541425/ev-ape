<?php

namespace App\Util;

use App\Constants\Constant;
use App\Models\Backend\Admin\Admin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Str;

class Routes
{
    private Admin $admin;

    /**
     * Routes constructor.
     * @param Admin $admin
     */
    public function __construct(Admin $admin)
    {
        $this->setAdmin($admin);
    }

    /**
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function nav(): Collection
    {
        $permissions = $this->getAdmin()->getAllPermissions()
            ->where('hidden', Constant::COMMON_IS_NO)
            ->where('component', '<>', 'layout/Layout')
            ->where('component', '<>', 'rview')
            ->filter(function (Permission $permission): bool {
                return Str::startsWith($permission->path, '/');
            })
            ->values();
        $noCache = $this->getNoCache($permissions);
        $affix = $this->getAffix($permissions);
        return $permissions->map(function (Permission $permission) use ($noCache, $affix): Permission {
            $permission->no_cache = $noCache->get($permission->name, false);
            $permission->affix = $affix->get($permission->name, false);
            return $permission;
        });
    }

    public function cacheKey(): string
    {
        return "PERMISSION:NOCACHE:{$this->getAdmin()->id}";
    }

    /**
     * @param Collection $permissions
     * @return Collection
     * @throws InvalidArgumentException
     */
    private function getNoCache(Collection $permissions): Collection
    {
        $data = Cache::store('redis')->get($this->cacheKey(), collect());
        if ($data->count() === 0) {
            $data = $permissions->mapWithKeys(function (Permission $permission): array {
                return [$permission->name => false];
            });
            Cache::store('redis')->forever($this->cacheKey(), $data);
        }
        return $data;
    }

    public function affixKey(): string
    {
        return "PERMISSION:AFFIX:{$this->getAdmin()->id}";
    }

    /**
     * @param Collection $permissions
     * @return Collection
     * @throws InvalidArgumentException
     */
    private function getAffix(Collection $permissions): Collection
    {
        $data = Cache::store('redis')->get($this->affixKey(), collect());
        if ($data->count() === 0) {
            $data = $permissions->mapWithKeys(function (Permission $permission): array {
                return [$permission->name => false];
            });
            Cache::store('redis')->forever($this->affixKey(), $data);
        }
        return $data;
    }

    /**
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function routes(): Collection
    {
        if ($this->getAdmin()->status === 1) {
            $permissions = $this->permissionCollect();
            $permissions = $this->sortByDesc($permissions);
            $permissions = $this->formatRoutes($permissions);
            $permissions = $this->formatRoutesChildren($permissions);
        } else {
            $permissions = collect();
        }
        return $permissions->merge([[
            'path' => '*',
            'redirect' => '/404',
            'hidden' => true
        ]]);
    }

    /**
     * @return Collection
     * @throws InvalidArgumentException
     */
    private function permissionCollect(): Collection
    {
        $permissions = $this->getAdmin()->getAllPermissions();

        $permissions = $permissions->toArray();
        $collect = [];
        $pivots = [];
        foreach ($permissions as $permission) {
            $pivots[$permission['id']][] = $permission['pivot'];
            $permission['pivots'] = $pivots[$permission['id']];
            $collect[$permission['id']] = $permission;
        }
        return collect($collect);

    }

    private function sortByDesc(Collection $permissions): Collection
    {
        return $permissions->sortByDesc('sort');
    }

    /**
     * @param Collection $permissions
     * @return Collection
     * @throws InvalidArgumentException
     */
    private function formatRoutes(Collection $permissions): Collection
    {
        $noCache = Cache::store('redis')->get($this->cacheKey(), collect());
        $affix = Cache::store('redis')->get($this->affixKey(), collect());
        return $permissions->map(function ($value) use ($noCache, $affix): array {
            $info = [];
            $info['id'] = $value['id'];
            $info['pid'] = $value['pid'];
            $info['path'] = $value['path'];
            $info['component'] = $value['component'];
            $info['name'] = $value['name']; // 設定路由的名字，壹定要填寫不然使用<keep-alive>時會出現各種問題
            $info['active_menu'] = $value['active_menu'];
            $roles = [];
            if (isset($value['pivots'])) {
                foreach ($value['pivots'] as $pivot) {
                    if (isset($pivot['role_id'])) {
                        $roles[] = $pivot['role_id'];
                    } else {
                        $roles[] = $pivot['model_type'] . '\\' . $pivot['model_id'];
                    }
                }
            }
            $info['meta'] = [
                'title' => $value['name'], // 設置該路由在側邊欄和面包屑中展示的名字
                'icon' => $value['icon'], // 設置該路由的圖標，支持 svg-class，也支持 el-icon-x element-ui 的 icon
                // 設置該路由進入的權限，支持多個權限疊加
                'roles' => $roles,
                'activeMenu' => $value['active_menu'],
                'noCache' => $noCache->get($value['name'], false), // 如果設置為true，則不會被 <keep-alive> 緩存(默認 false)
                'breadcrumb' => true, //  如果設置為false，則不會在breadcrumb面包屑中顯示(默認 true)
                'affix' => $affix->get($value['name'], false), // 若果設置為true，它則會固定在tags-view中(默認 false)
            ];
            // 當設置 true 的時候該路由不會在側邊欄出現 如401，login等頁面，或者如壹些編輯頁面/edit/1
            $info['hidden'] = $value['hidden'] == Constant::COMMON_IS_YES;
            // 當設置 noRedirect 的時候該路由在面包屑導航中不可被點擊
            if ($value['component'] === 'layout/Layout' || $value['component'] === 'rview') {
                $info['redirect'] = 'noRedirect';
            }
            return $info;
        });
    }

    private function formatRoutesChildren(Collection $permissions): Collection
    {
        $permissions = ArrayTool::setChildrenInParentNew($permissions->toArray());

        foreach ($permissions as $key => $value) {
            if ($value['pid'] === 0 && $value['component'] !== 'layout/Layout' && $value['hidden'] === false) {
                $component = $value['component'];
                $permissions[$key]['component'] = 'layout/Layout';
                $permissions[$key]['redirect'] = 'noRedirect';
                $permissions[$key]['meta']['breadcrumb'] = false;
                $permissions[$key]['children'][] = [
                    'path' => 'index',
                    'component' => $component,
                    'name' => $value['name'],
                    'hidden' => $value['hidden'],
                    'meta' => [
                        'title' => $value['meta']['title'],
                        'icon' => $value['meta']['icon'],
                        'roles' => $value['meta']['roles'],
                        'activeMenu' => $value['meta']['activeMenu'],
                        'noCache' => $value['meta']['noCache'],
                        'breadcrumb' => $value['meta']['breadcrumb'],
                        'affix' => $value['meta']['affix'],
                    ]
                ];
                unset($permissions[$key]['name']);
            }
        }

        return collect($permissions);
    }

    /**
     * @return Admin
     */
    public function getAdmin(): Admin
    {
        return $this->admin;
    }

    /**
     * @param Admin $admin
     * @return void
     */
    private function setAdmin(Admin $admin)
    {
        $this->admin = $admin;
    }
}
