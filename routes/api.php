<?php

use App\Http\Controllers\Frontend\Order\PaymentController;
use App\Http\Controllers\Frontend\Parking\FavoriteController;
use App\Http\Controllers\Frontend\Parking\MapController;
use App\Http\Controllers\Frontend\Parking\AppointmentController;
use App\Http\Controllers\Frontend\User\CardController;
use App\Http\Controllers\Frontend\User\LoginController;
use App\Http\Controllers\Frontend\User\RegisterController;
use App\Http\Controllers\Frontend\Parking\ChargingController;
use App\Http\Controllers\Frontend\Parking\PileChargingController;
use App\Http\Controllers\Frontend\Brand\BrandController;
use App\Http\Controllers\Frontend\User\InvoiceController;
use App\Http\Controllers\Frontend\Parking\PowerController;
use App\Http\Controllers\Frontend\Index\IndexController;
use App\Http\Controllers\Frontend\Parking\FaultController;
use App\Http\Controllers\Frontend\Index\QuestionController;
use App\Http\Controllers\Frontend\DiningController;
use App\Http\Controllers\Frontend\User\MyController;
use App\Http\Controllers\Frontend\User\HistoryController;
use App\Http\Controllers\Frontend\Message\MessageController;
use App\Http\Controllers\Frontend\User\FirebaseController;
use App\Http\Middleware\AuthUser;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('notify', [PaymentController::class, 'notify'])->name('backend_notify_url');
Route::post('notify_bind', [PaymentController::class, 'notifyBind'])->name('backend_bind_notify_url');
Route::any('redirect_bind', [PaymentController::class, 'redirectBind'])->name('frontend_bind_redirect_url');
Route::post('dining_notify', [PaymentController::class, 'diningDealNotify'])->name('payment_dining_notify');
Route::post('dining_has_not_left_notify', [PaymentController::class, 'diningHasNotLeftNotify'])->name('payment_dining_has_not_left_notify');
Route::post('invoice_notify', [PaymentController::class, 'invoiceNotify'])->name('invoice_notify');

// test
// Route::post('test-sms', [IndexController::class, 'test']);

Route::any('sms-response', [IndexController::class, 'smsResponse'])->name('sms_response');



Route::name('api.')->group(function () {

    // fireabase
    Route::group(['prefix' => 'firebase'], function () {

        // 綁定和解綁
        Route::post('bind', [FirebaseController::class, 'bind']);
        Route::post('unbind', [FirebaseController::class, 'unbind']);

    });

    // 充電樁交互 開始
    Route::group(['middleware' => ['pile_auth']], function () {
        Route::post('starting', [PileChargingController::class, 'starting']);
        Route::post('ending', [PileChargingController::class, 'ending']);
        Route::post('progress', [PileChargingController::class, 'progress']);
        Route::post('reporting', [PileChargingController::class, 'reporting']);
        // Route::post('reporting', [ChargingController::class, 'reporting'])->middleware(['pile_auth']);
    });
    // 充電樁交互 結束

    Route::group(['middleware' => ['frontend.device.auth']], function () {
        Route::post('login', [LoginController::class, 'login']);
        Route::post('register', [RegisterController::class, 'register']);

        // 版本控制
        Route::post('version', [IndexController::class, 'getVersion']);

        // web
        Route::group(['prefix' => 'parking'], function () {

            // 充電站列表
            Route::post('list', [MapController::class, 'list']);

        });

        // 場域
        Route::group(['prefix' => 'map'], function () {

            // 搜尋場域
            Route::post('index', [MapController::class, 'index']);

        });

        // 常見問題
        Route::group(['prefix' => 'question'], function () {

            // 問題列表
            Route::post('categories', [QuestionController::class, 'categories']);

            // 問題列表
            Route::post('list', [QuestionController::class, 'list']);

        });

        Route::post('advertisement', [IndexController::class, 'advertisement']);

        // web餐旅列表
        Route::post('dining/partners', [DiningController::class, 'partners']);

        // 可預約餐旅列表
        Route::post('dining/list', [DiningController::class, 'list']);
        Route::post('dining_type/list', [DiningController::class, 'typeList']);

        // 餐旅詳情
        Route::post('dining/detail', [DiningController::class, 'detail']);

        // 餐廳某時段可以預約的人數
        Route::post('dining/remain_number', [DiningController::class, 'remainNumber']);

        // 最新消息列表
        Route::post('message/list', [MessageController::class, 'list']);
        Route::post('message/detail', [MessageController::class, 'messageDetail']);

        // 聯絡我們
        Route::post('contact', [IndexController::class, 'contactUs']);

        // 獲取註冊/找回密碼驗證碼
        Route::post('phone_code', [RegisterController::class, 'sendCode']);

        // 驗證手機驗證碼
        Route::post('phone_code_validate', [RegisterController::class, 'check']);

        // 找回密碼
        Route::post('reset_password', [LoginController::class, 'resetPassword']);

        // APP歡迎頁
        Route::post('welcome_page', [IndexController::class, 'welcomePage']);

        // APP引導頁
        Route::post('guide_page', [IndexController::class, 'guidePage']);

        // 輪播圖
        Route::post('banner/list', [IndexController::class, 'bannerList']);

        // 會員權益聲明和隱私權條款
        Route::post('documents', [IndexController::class, 'documents']);

        // 地區列表
        Route::post('region', [IndexController::class, 'index']);

        // 車品牌
        Route::post('brand', [BrandController::class, 'index']);

        // 規格列表
        Route::post('power', [PowerController::class, 'index']);

        Route::middleware([AuthUser::class])->group(function () {
            // 上傳圖片
            Route::post('upload', [IndexController::class, 'upload']);

            Route::post('logout', [LoginController::class, 'logout']);

            // 個人資料
            Route::post('me', [LoginController::class, 'me']);

            // 大頭貼列表
            Route::post('avatar_list', [MyController::class, 'avatarList']);

            // 修改大頭貼
            Route::post('update_avatar', [MyController::class, 'updateAvatar']);

            // 修改背景圖片
            Route::post('update_background', [MyController::class, 'updateBackground']);

            // 修改個人資料
            Route::post('update_info', [MyController::class, 'updateInfo']);

            // 修改地址
            Route::post('update_address', [MyController::class, 'updateAddress']);

            // 郵箱驗證碼
            Route::post('email_code', [MyController::class, 'sendEmail']);

            // 修改郵箱
            Route::post('update_email', [MyController::class, 'updateEmail']);

            // 更改密碼
            Route::post('update_password', [MyController::class, 'updatePwd']);

            // 評分
            Route::post('order/score', [ChargingController::class, 'score']);

            // 場域
            Route::group(['prefix' => 'recharge'], function () {

                // 用戶今日充電和當月充電統計
                Route::post('dashboard', [HistoryController::class, 'dashboard']);

                // 充電次數
                Route::post('number', [HistoryController::class, 'chargeNumber']);
                Route::post('list', [HistoryController::class, 'list']);

                // 重新支付
                Route::post('re_pay', [HistoryController::class, 'rePay']);

            });

            // 最愛
            Route::group(['prefix' => 'favorite'], function () {

                // 添加/取消最愛
                Route::post('submit', [FavoriteController::class, 'submit']);

            });

            // 充電樁預約
            Route::group(['prefix' => 'appointment'], function () {

                // 提交預約
                Route::post('submit', [AppointmentController::class, 'submit']);

                // 獲取預約信息
                Route::post('detail', [AppointmentController::class, 'detail']);

                // 取消
                Route::post('cancel', [AppointmentController::class, 'cancel']);
                Route::post('list', [AppointmentController::class, 'list']);
                Route::post('reasons', [AppointmentController::class, 'reasons']);
            });

            // 信用卡
            Route::group(['prefix' => 'card'], function () {

                // 綁定卡
                Route::post('set_default', [CardController::class, 'setDefault']);

                // 設置默認支付卡號
                Route::post('bind', [CardController::class, 'bind']);

                // 解綁卡
                Route::post('unbind', [CardController::class, 'unbind']);

                // 卡列表
                Route::post('list', [CardController::class, 'list']);
            });

            // 發票
            Route::group(['prefix' => 'invoice'], function () {

                // 綁定發票
                Route::post('add', [InvoiceController::class, 'bind']);

                // 設置默認發票
                Route::post('set_default', [InvoiceController::class, 'setDefault']);

                // 解綁發票
                Route::post('remove', [InvoiceController::class, 'remove']);

                // 發票列表
                Route::post('list', [InvoiceController::class, 'list']);

                //
                Route::post('donations', [InvoiceController::class, 'donations']);
            });

            // 充電樁
            Route::group(['prefix' => 'charging'], function () {

                // 掃碼
                Route::post('pile', [ChargingController::class, 'pile'])->middleware(['precondition']);

                // 開始充電
                Route::post('starting', [ChargingController::class, 'starting'])->middleware(['precondition']);

                // 掃碼前檢測
                Route::post('pre_detection', [ChargingController::class, 'preDetection']);

                // 確認插槍
                Route::post('pre_device', [ChargingController::class, 'preDevice'])->middleware(['precondition']);

                // 結束充電
                Route::post('ending', [ChargingController::class, 'end']);

            });

            // 客服
            Route::group(['prefix' => 'fault'], function () {

                // 問題列表
                Route::post('no', [FaultController::class, 'no']);
                Route::post('categories', [FaultController::class, 'index']);

                // 提交
                Route::post('submit', [FaultController::class, 'submit']);

            });

            // 預約餐旅
            Route::group(['prefix' => 'dining'], function () {

                // 提交預約
                Route::post('submit', [DiningController::class, 'submit']);

                // 已預約餐旅列表
                Route::post('booking_list', [DiningController::class, 'bookingList']);

                // 已預約詳情
                Route::post('booking_detail', [DiningController::class, 'bookingDetail']);

                // 取消預約
                Route::post('cancel', [DiningController::class, 'cancel']);

                // 現場報到
                Route::post('deal', [DiningController::class, 'deal']);

                // 評分
                Route::post('score', [DiningController::class, 'score']);

                // 重新支付
                Route::post('re_pay', [DiningController::class, 'rePay']);

            });

            // 最新消息
            Route::group(['prefix' => 'message'], function () {

                Route::post('unread_number', [MessageController::class, 'noticeNumber']);
                Route::post('notices', [MessageController::class, 'notices']);
                Route::post('notice_detail', [MessageController::class, 'noticeDetail']);

            });



        });
    });

});
