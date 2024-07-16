#**SUPERVISOR**
## 參考文章
[CentOS supervisor 安裝與配置 （Laravel 隊列示例）](https://learnku.com/articles/28919)
[supervisor 安裝配置使用](https://learnku.com/laravel/t/2126/supervisor-installation-configuration-use)

## 代碼設置
1. `QUEUE_CONNECTION=redis` 指定隊列驅動程序的連接配置
## LINUX CENTOS 安裝 SUPERVISOR
**安裝supervisor很簡單，通過easy_install就可以安裝**
1. `yum install python-setuptools`
2. `easy_install supervisor`
3. `echo_supervisord_conf > /etc/supervisord.conf`

**配置**

`` vim /etc/supervisord.conf ``
```
例
[program:ity-notification]
process_name=%(program_name)s_%(process_num)02d
command=php /data/wwwroot/default/artisan queue:work redis --tries=3 --queue=notification
autostart=true
autorestart=true
user=root
numprocs=3
redirect_stderr=true
stdout_logfile=/data/wwwroot/default/storage/logs/queue-notification.log
```
```
啟動 supervisor
supervisord -c /etc/supervisord.conf
```
```
關閉 supervisor
supervisorctl shutdown
```
```
重新載入配置
supervisorctl reload
```
