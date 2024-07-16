# 非比基礎框架
## 技術
> [PHP](https://www.php.net/) ^8.0.2  
> [Laravel](https://laravel.com/) 9.x  
> [guzzlehttp/guzzle](https://github.com/guzzle/guzzle)  HTTP 客戶端  
> [spatie/laravel-permission](https://github.com/spatie/laravel-permission) 用戶角色權限  
> [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) 操作日誌

## 搭建
```shell
# 1. 安裝依賴包
composer install

# 2. 復制 `.env` & 設置環境變量
cp .env.example .env

# 3. 生成新的 `APP_KEY` AND `JWT_SECRET`
php artisan key:generate

# 4. 數據遷移, 創建初始數據表
php artisan db:seed --class=CreateDatabaseSeeder


# 5. 創建存儲軟連接
php artisan storage:link

# Docker下 Ubuntu系統： 
ln -sr ./storage/app/public ./public/storage


# 6. 修改 .env
APP_NAME="項目英文名稱"
APP_URL="項目域名地址"

```

### `.env`
```dotenv
APP_NAME="項目英文名稱"
APP_URL="項目域名地址"

# 日誌 Log
# 請求日誌
REQUEST_LOG=true
# Sql 日誌
SQL_DEBUG=true
# 自定義日誌器
LOG_CHANNEL=custom
LOG_LEVEL=debug


# 操作日誌 Activity Log
# 如果設置為 false，則不會將任何活動保存到數據庫中。
ACTIVITY_LOGGER_ENABLED=true

```

## 本地化
```
"zh-TW" => "中文繁體"
"en"    => "English"

默認為 => "zh-TW"

```

### 代碼生成器
新增代碼生成器(測試版)。可進行 CURD ，加快開發效率。
![系統異常](.github/代碼生成器.png)

### 部署
1. APP_ENV=production
2. APP_DEBUG=false
3. QUEUE_CONNECTION=redis
4. REDIS_CLIENT=phpredis (可選)
5. `composer install --optimize-autoloader --no-dev` 自動加載器改進
6. `php artisan config:cache` 優化配置加載
7. `php artisan route:cache` 優化路由加載
8. `php artisan event:cache` 優化事件加載
9. `composer dump-autoload --optimize` 優化自動加載

### 維護
1.  `php artisan down` 維護模式
    1.  `php artisan down --secret="1630542a-246b-4b66-afa1-dd72a4c43515"` 指定維護模式的繞過令牌
    2. 訪問 `https://example.com/1630542a-246b-4b66-afa1-dd72a4c43515`
2. `php artisan up` 關閉維護模式
3. `php artisan activitylog:clean --days=7` 清理操作日誌
4. `php artisan exceptionerror:clean --days=7` 清理異常日誌

### WebSocket
1. WINDOWS: `start_for_win.bat`
2. LINUX: `php artisan workerman start --d`
3. URI: `ws://IP:2346?lang=LANG&token=TOKEN`
4. SEND: `{"route": "route.name", "data": data}`
