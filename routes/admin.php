<?php

use App\Http\Controllers\Backend\Admin\ActivityLogController;
use App\Http\Controllers\Backend\Admin\AdminController;
use App\Http\Controllers\Backend\Admin\LoginController;
use App\Http\Controllers\Backend\Admin\NotificationController;
use App\Http\Controllers\Backend\Admin\PermissionController;
use App\Http\Controllers\Backend\Admin\RoleController;
use App\Http\Controllers\Backend\DiningHotelTypeController;
use App\Http\Controllers\Backend\EmailNoticesController;
use App\Http\Controllers\Backend\System\DictDataController;
use App\Http\Controllers\Backend\System\DictTypeController;
use App\Http\Controllers\Backend\System\ExceptionErrorController;
use App\Http\Controllers\Backend\System\FileSystemController;
use App\Http\Controllers\Backend\System\GenTableController;
use App\Http\Controllers\Backend\System\NginxController;
use App\Http\Controllers\Backend\BrandsController;
use App\Http\Controllers\Backend\ParkingLotsController;
use App\Http\Controllers\Backend\ChargingPowersController;
use App\Http\Controllers\Backend\ChargingPilesController;
use App\Http\Controllers\Backend\FaultCategoriesController;
use App\Http\Controllers\Backend\FaultsController;
use App\Http\Controllers\Backend\ReportingController;
use App\Http\Controllers\Backend\QuestionCategoryController;
use App\Http\Controllers\Backend\QuestionsController;
use App\Http\Controllers\Backend\InvoiceDonationController;
use App\Http\Controllers\Backend\AppointmentReasonController;
use App\Http\Controllers\Backend\AppointmentController;
use App\Http\Controllers\Backend\ContactUsController;
use App\Http\Controllers\Backend\IndexController;
use App\Http\Controllers\Backend\BannerController;
use App\Http\Controllers\Backend\PrivacyController;
use App\Http\Controllers\Backend\WebsiteController;
use App\Http\Controllers\Backend\WelcomePagesController;
use App\Http\Controllers\Backend\GuidePageController;
use App\Http\Controllers\Backend\AdvertisementController;
use App\Http\Controllers\Backend\User\UserController;
use App\Http\Controllers\Backend\DiningHotelController;
use App\Http\Controllers\Backend\DiningBookingController;
use App\Http\Controllers\Backend\OrderController;
use App\Http\Controllers\Backend\OrderRefundController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\FirebaseNoticeController;
use App\Http\Controllers\Backend\MessageController;
use App\Http\Controllers\Backend\FirebasePushController;
use App\Http\Controllers\Backend\VersionControlController;
use App\Http\Middleware\AuthAdmin;
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

Route::name('admin.')->group(function () {
    Route::post('login', [LoginController::class, 'login']);
    Route::post('found_notification', [LoginController::class, 'sendFoundNotification']);

    Route::middleware(['jwt.role:admin', AuthAdmin::class])->group(function () {
        Route::post('refresh', [LoginController::class, 'refresh']);
        Route::post('me', [LoginController::class, 'me']);
        Route::post('logout', [LoginController::class, 'logout']);

        // 地區列表
        Route::post('region', [IndexController::class, 'index']);

        // 上傳
        Route::post('upload', [IndexController::class, 'upload']);

        // 會員權益聲明和隱私權條款
        Route::post('privacy/list', [PrivacyController::class, 'index']);
        Route::post('privacy/update', [PrivacyController::class, 'update']);

        Route::post('website/list', [WebsiteController::class, 'list']);
        Route::post('website/detail', [WebsiteController::class, 'detail']);
        Route::post('website/update', [WebsiteController::class, 'update']);

        // 通知
        Route::post('notification/list', [NotificationController::class, 'list']);
        Route::post('notification/detail', [NotificationController::class, 'detail']);
        Route::post('notification/unReadCount', [NotificationController::class, 'unReadCount']);
        Route::post('notification/allRead', [NotificationController::class, 'allRead']);
        Route::post('notification/read', [NotificationController::class, 'read']);
        Route::post('notification/admins', [NotificationController::class, 'admins']);
        Route::post('notification/send', [NotificationController::class, 'send']);

        // 管理員
        Route::post('admin/create', [AdminController::class, 'create'])
            ->middleware('permission:admin.create');
        Route::post('admin/update', [AdminController::class, 'update'])
            ->middleware('permission:admin.update');
        Route::post('admin/delete', [AdminController::class, 'delete'])
            ->middleware('permission:admin.delete');
        Route::post('admin/syncRoles', [AdminController::class, 'syncRoles'])
            ->middleware('permission:role.syncRoles');
        Route::post('admin/syncPermissions', [AdminController::class, 'syncPermissions']);
            // ->middleware('permission:admin.syncPermissions');
        Route::post('admin/updateSelf', [AdminController::class, 'updateSelf']);
        Route::post('admin/list', [AdminController::class, 'list'])
            ->middleware('permission:admin.admins');
        Route::post('admin/detail', [AdminController::class, 'detail']);
            // ->middleware('permission:admin.admin');
        Route::post('admin/all', [AdminController::class, 'all']);

        Route::post('nav', [AdminController::class, 'nav']);
        Route::post('nav/set/noCache', [AdminController::class, 'navSetNoCache']);
        Route::post('nav/set/affix', [AdminController::class, 'navSetAffix']);

        // 操作記錄
        Route::post('activityLog/list', [ActivityLogController::class, 'list'])
            ->middleware('permission:activeLog.activeLogs');
        Route::post('activityLog/queryType', [ActivityLogController::class, 'queryType'])
            ->middleware('permission:activeLog.activeLogs');


        // 角色
        Route::post('role/create', [RoleController::class, 'create'])
            ->middleware('permission:role.create');
        Route::post('role/update', [RoleController::class, 'update'])
            ->middleware('permission:role.update');
        Route::post('role/delete', [RoleController::class, 'delete'])
            ->middleware('permission:role.delete');
        Route::post('role/syncPermissions', [RoleController::class, 'syncPermissions'])
            ->middleware('permission:role.syncPermissions');
        Route::post('role/detail', [RoleController::class, 'detail'])
            ->middleware('permission:role.roles');
        Route::post('role/list', [RoleController::class, 'list'])
            ->middleware('permission:role.roles');
        Route::post('role/all', [RoleController::class, 'allRoles']);
            // ->middleware('permission:role.roles');

        // 權限
        Route::post('permission/create', [PermissionController::class, 'create'])
            ->middleware('permission:permission.create');
        Route::post('permission/update', [PermissionController::class, 'update'])
            ->middleware('permission:permission.update');
        Route::post('permission/delete', [PermissionController::class, 'delete'])
            ->middleware('permission:permission.delete');
        Route::post('permission/detail', [PermissionController::class, 'detail'])
            ->middleware('permission:permission.permission');
        Route::post('permission/list', [PermissionController::class, 'list'])
            ->middleware('permission:permission.permissions');
        Route::post('permission/tree', [PermissionController::class, 'tree'])
            ->middleware('permission:permission.permissions|role.roles');
        Route::post('permission/drop', [PermissionController::class, 'drop'])
            ->middleware('permission:permission.update');


        // 異常記錄
        Route::post('exceptionError/list', [ExceptionErrorController::class, 'list'])
            ->middleware('permission:exceptionError.exceptionErrors');
        Route::post('exceptionError/amended', [ExceptionErrorController::class, 'amended'])
            ->middleware('permission:exceptionError.amended');


        // 文件
        Route::post('file/list', [FileSystemController::class, 'list'])
            ->middleware('permission:file.files');
        Route::post('file/makeDirectory', [FileSystemController::class, 'makeDirectory'])
            ->middleware('permission:file.makeDirectory');
        Route::post('file/deleteDirectory', [FileSystemController::class, 'deleteDirectory'])
            ->middleware('permission:file.deleteDirectory');
        Route::post('file/upload', [FileSystemController::class, 'upload'])
            ->middleware('permission:file.upload');
        Route::post('file/upload_md', [FileSystemController::class, 'upload_md'])
            ->middleware('permission:file.upload');
        Route::post('file/uploadUnPermission', [FileSystemController::class, 'upload']);
        Route::post('file/uploadImage', [FileSystemController::class, 'uploadImage']);
        Route::post('file/uploadFile', [FileSystemController::class, 'uploadFile']);
        Route::post('file/removeFile', [FileSystemController::class, 'removeFile']);
        Route::post('file/download', [FileSystemController::class, 'download'])
            ->middleware('permission:file.download');
        Route::post('file/delete', [FileSystemController::class, 'delete'])
            ->middleware('permission:file.delete');

        // 日誌
        Route::post('file/log/list', [FileSystemController::class, 'logList'])
            ->middleware('permission:exceptionError.logFiles');
        Route::post('file/log/detail', [FileSystemController::class, 'logDetail'])
            ->middleware('permission:exceptionError.logFiles');


        // NGINX
        Route::post('nginx/logs', [NginxController::class, 'logs']);// ->middleware('permission:nginx.logs')

        // 字典
        Route::post('dictType/list', [DictTypeController::class, 'list'])
            ->middleware('permission:dict');
        Route::post('dictType/detail', [DictTypeController::class, 'detail'])
            ->middleware('permission:dict');
        Route::post('dictType/all', [DictTypeController::class, 'all'])
            ->middleware('permission:dict');
        Route::post('dictType/create', [DictTypeController::class, 'create'])
            ->middleware('permission:dict');
        Route::post('dictType/update', [DictTypeController::class, 'update'])
            ->middleware('permission:dict');
        Route::post('dictType/delete', [DictTypeController::class, 'delete'])
            ->middleware('permission:dict');

        Route::post('dictData/list', [DictDataController::class, 'list'])
            ->middleware('permission:dict');
        Route::post('dictData/detail', [DictDataController::class, 'detail'])
            ->middleware('permission:dict');
        Route::post('dictData/all', [DictDataController::class, 'all']);
        Route::post('dictData/create', [DictDataController::class, 'create'])
            ->middleware('permission:dict');
        Route::post('dictData/update', [DictDataController::class, 'update'])
            ->middleware('permission:dict');
        Route::post('dictData/delete', [DictDataController::class, 'delete'])
            ->middleware('permission:dict');
        Route::post('dictData/listClass', [DictDataController::class, 'listClass'])
            ->middleware('permission:dict');

        // 數據表生成
        Route::post('genTable/list', [GenTableController::class, 'list'])
            ->middleware('permission:genTable.genTables');
        Route::post('genTable/detail', [GenTableController::class, 'detail'])
            ->middleware('permission:genTable.genTables');
        Route::post('genTable/all', [GenTableController::class, 'select']);
        Route::post('genTable/importTable', [GenTableController::class, 'importTable'])
            ->middleware('permission:genTable.genTables');
        Route::post('genTable/columnConfig', [GenTableController::class, 'columnConfig'])
            ->middleware('permission:genTable.genTables');
        Route::post('genTable/create', [GenTableController::class, 'create'])
            ->middleware('permission:genTable.genTables');
        Route::post('genTable/update', [GenTableController::class, 'update'])
            ->middleware('permission:genTable.genTables');
        Route::post('genTable/delete', [GenTableController::class, 'delete'])
            ->middleware('permission:genTable.genTables');
        Route::post('genTable/gen', [GenTableController::class, 'gen'])
            ->middleware('permission:genTable.genTables');
        Route::post('genTable/genOld', [GenTableController::class, 'genOld'])
            ->middleware('permission:genTable.genTables');
        Route::post('genTable/download', [GenTableController::class, 'download'])
            ->middleware('permission:genTable.genTables');


        // 用戶管理
        Route::post('user/list', [UserController::class, 'list'])
            ->middleware('permission:user.list');
        Route::post('user/detail', [UserController::class, 'detail'])
            ->middleware('permission:user.list');
        Route::post('user/update', [UserController::class, 'update'])
            ->middleware('permission:user.update');
        Route::post('user/invoices', [UserController::class, 'invoices'])
            ->middleware('permission:user.list');
        Route::post('user/cards', [UserController::class, 'cards'])
            ->middleware('permission:user.list');
        Route::post('user/export', [UserController::class, 'export'])
            ->middleware('permission:user.list');
        Route::post('user/exportDatum', [UserController::class, 'exportDatum'])
            ->middleware('permission:user.list');

        // 車用品牌
        Route::post('brands/list', [BrandsController::class, 'list'])
            ->middleware('permission:brands.list');
        Route::post('brands/detail', [BrandsController::class, 'detail'])
            ->middleware('permission:brands.list');
        Route::post('brands/create', [BrandsController::class, 'create'])
            ->middleware('permission:brands.create');
        Route::post('brands/update', [BrandsController::class, 'update'])
            ->middleware('permission:brands.update');
        Route::post('brands/delete', [BrandsController::class, 'delete'])
            ->middleware('permission:brands.delete');

        // 餐廳類型
        Route::post('dining_hotel_type/list', [DiningHotelTypeController::class, 'list'])
            ->middleware('permission:dining_hotel_type.list');
        Route::post('dining_hotel_type/all', [DiningHotelTypeController::class, 'all'])
            ->middleware('permission:dining_hotel_type.list');
        Route::post('dining_hotel_type/detail', [DiningHotelTypeController::class, 'detail'])
            ->middleware('permission:dining_hotel_type.list');
        Route::post('dining_hotel_type/create', [DiningHotelTypeController::class, 'create'])
            ->middleware('permission:dining_hotel_type.create');
        Route::post('dining_hotel_type/update', [DiningHotelTypeController::class, 'update'])
            ->middleware('permission:dining_hotel_type.update');
        Route::post('dining_hotel_type/delete', [DiningHotelTypeController::class, 'delete'])
            ->middleware('permission:dining_hotel_type.delete');

        // Email自動發信
        Route::post('email_notices/list', [EmailNoticesController::class, 'list'])
            ->middleware('permission:email_notices.list');
        Route::post('email_notices/detail', [EmailNoticesController::class, 'detail'])
            ->middleware('permission:email_notices.list');
        Route::post('email_notices/update', [EmailNoticesController::class, 'update'])
            ->middleware('permission:email_notices.update');





        // 充電場域管理
        Route::post('parkingLots/list', [ParkingLotsController::class, 'list'])
            ->middleware('permission:parkingLots.list');
        Route::post('parkingLots/export', [ParkingLotsController::class, 'export'])
            ->middleware('permission:parkingLots.list');
        Route::post('parkingLots/detail', [ParkingLotsController::class, 'detail'])
            ->middleware('permission:parkingLots.list');
        Route::post('parkingLots/create', [ParkingLotsController::class, 'create'])
            ->middleware('permission:parkingLots.create');
        Route::post('parkingLots/update', [ParkingLotsController::class, 'update'])
            ->middleware('permission:parkingLots.update');
        Route::post('parkingLots/audit', [ParkingLotsController::class, 'audit'])
            ->middleware('permission:parkingLots.audit');
        Route::post('parkingLots/final', [ParkingLotsController::class, 'final'])
            ->middleware('permission:parkingLots.final');
        Route::post('parkingLots/delete', [ParkingLotsController::class, 'delete'])
            ->middleware('permission:parkingLots.delete');

        // 功率規格管理
        Route::post('chargingPowers/list', [ChargingPowersController::class, 'list']);
        Route::post('chargingPowers/detail', [ChargingPowersController::class, 'detail'])
            ->middleware('permission:chargingPowers.list');
        Route::post('chargingPowers/create', [ChargingPowersController::class, 'create'])
            ->middleware('permission:chargingPowers.create');
        Route::post('chargingPowers/update', [ChargingPowersController::class, 'update'])
            ->middleware('permission:chargingPowers.update');
        Route::post('chargingPowers/delete', [ChargingPowersController::class, 'delete'])
            ->middleware('permission:chargingPowers.delete');

        // 充電樁管理
        Route::post('chargingPiles/list', [ChargingPilesController::class, 'list'])
            ->middleware('permission:chargingPiles.list');
        Route::post('chargingPiles/export', [ChargingPilesController::class, 'export'])
            ->middleware('permission:chargingPiles.list');
        Route::post('chargingPiles/detail', [ChargingPilesController::class, 'detail'])
            ->middleware('permission:chargingPiles.list');
        // Route::post('chargingPiles/all', [ChargingPilesController::class, 'all']);
        Route::post('chargingPiles/create', [ChargingPilesController::class, 'create'])
            ->middleware('permission:chargingPiles.create');
        Route::post('chargingPiles/update', [ChargingPilesController::class, 'update'])
            ->middleware('permission:chargingPiles.update');
        Route::post('chargingPiles/audit', [ChargingPilesController::class, 'audit'])
            ->middleware('permission:chargingPiles.audit');
        Route::post('chargingPiles/final', [ChargingPilesController::class, 'final'])
            ->middleware('permission:chargingPiles.final');
        Route::post('chargingPiles/delete', [ChargingPilesController::class, 'delete'])
            ->middleware('permission:chargingPiles.delete');

        // 表單問題類型
        Route::post('faultCategories/list', [FaultCategoriesController::class, 'list'])
            ->middleware('permission:faultCategories.list');
        Route::post('faultCategories/detail', [FaultCategoriesController::class, 'detail'])
            ->middleware('permission:faultCategories.list');
        // Route::post('faultCategories/all', [FaultCategoriesController::class, 'all']);
        Route::post('faultCategories/create', [FaultCategoriesController::class, 'create'])
            ->middleware('permission:faultCategories.create');
        Route::post('faultCategories/update', [FaultCategoriesController::class, 'update'])
            ->middleware('permission:faultCategories.update');
        Route::post('faultCategories/delete', [FaultCategoriesController::class, 'delete'])
            ->middleware('permission:faultCategories.delete');

        // 客服表單
        Route::post('faults/list', [FaultsController::class, 'list'])
            ->middleware('permission:faults.list');
        Route::post('faults/detail', [FaultsController::class, 'detail'])
            ->middleware('permission:faults.list');
        // Route::post('faults/all', [FaultsController::class, 'all']);
        Route::post('faults/update', [FaultsController::class, 'update'])
            ->middleware('permission:faults.update');

        // 充電樁報修列表
        Route::post('reporting/list', [ReportingController::class, 'list'])
            ->middleware('permission:reporting.list');
        Route::post('reporting/detail', [ReportingController::class, 'detail'])
            ->middleware('permission:reporting.list');
        Route::post('reporting/create', [ReportingController::class, 'create'])
            ->middleware('permission:reporting.create');
        Route::post('reporting/update', [ReportingController::class, 'update'])
            ->middleware('permission:reporting.update');

        // 常見問題分類
        Route::post('questionCategory/list', [QuestionCategoryController::class, 'list'])
            ->middleware('permission:questions.list');
        Route::post('questionCategory/detail', [QuestionCategoryController::class, 'detail'])
            ->middleware('permission:questions.list');
        Route::post('questionCategory/create', [QuestionCategoryController::class, 'create'])
            ->middleware('permission:questions.create');
        Route::post('questionCategory/update', [QuestionCategoryController::class, 'update'])
            ->middleware('permission:questions.update');
        Route::post('questionCategory/delete', [QuestionCategoryController::class, 'delete'])
            ->middleware('permission:questions.delete');

        // 問題管理
        Route::post('questions/list', [QuestionsController::class, 'list'])
            ->middleware('permission:questions.list');
        Route::post('questions/detail', [QuestionsController::class, 'detail'])
            ->middleware('permission:questions.list');
        Route::post('questions/create', [QuestionsController::class, 'create'])
            ->middleware('permission:questions.create');
        Route::post('questions/update', [QuestionsController::class, 'update'])
            ->middleware('permission:questions.update');
        Route::post('questions/delete', [QuestionsController::class, 'delete'])
            ->middleware('permission:questions.delete');

        // 發票捐贈設定
        Route::post('invoiceDonation/list', [InvoiceDonationController::class, 'list'])
            ->middleware('permission:invoiceDonation.list');
        Route::post('invoiceDonation/detail', [InvoiceDonationController::class, 'detail'])
            ->middleware('permission:invoiceDonation.list');
        Route::post('invoiceDonation/create', [InvoiceDonationController::class, 'create'])
            ->middleware('permission:invoiceDonation.create');
        Route::post('invoiceDonation/update', [InvoiceDonationController::class, 'update'])
            ->middleware('permission:invoiceDonation.update');
        Route::post('invoiceDonation/delete', [InvoiceDonationController::class, 'delete'])
            ->middleware('permission:invoiceDonation.delete');

        // 取消充電預約原因管理
        Route::post('appointmentReason/list', [AppointmentReasonController::class, 'list'])
            ->middleware('permission:appointmentReason.list');
        Route::post('appointmentReason/detail', [AppointmentReasonController::class, 'detail'])
            ->middleware('permission:appointmentReason.list');
        Route::post('appointmentReason/create', [AppointmentReasonController::class, 'create'])
            ->middleware('permission:appointmentReason.create');
        Route::post('appointmentReason/update', [AppointmentReasonController::class, 'update'])
            ->middleware('permission:appointmentReason.update');
        Route::post('appointmentReason/delete', [AppointmentReasonController::class, 'delete'])
            ->middleware('permission:appointmentReason.delete');

        // 充電樁預約紀錄
        Route::post('appointment/list', [AppointmentController::class, 'list'])
            ->middleware('permission:appointment.list');

        // 聯絡我們管理
        Route::post('contactUs/list', [ContactUsController::class, 'list'])
            ->middleware('permission:contactUs.list');
        Route::post('contactUs/detail', [ContactUsController::class, 'detail'])
            ->middleware('permission:contactUs.list');
        Route::post('contactUs/update', [ContactUsController::class, 'update'])
            ->middleware('permission:contactUs.update');

        // 首頁輪播圖
        Route::post('banner/list', [BannerController::class, 'list'])
            ->middleware('permission:banner.list');
        Route::post('banner/detail', [BannerController::class, 'detail'])
            ->middleware('permission:banner.list');
        Route::post('banner/create', [BannerController::class, 'create'])
            ->middleware('permission:banner.create');
        Route::post('banner/update', [BannerController::class, 'update'])
            ->middleware('permission:banner.update');
        Route::post('banner/delete', [BannerController::class, 'delete'])
            ->middleware('permission:banner.delete');

        // APP歡迎頁
        Route::post('welcomePages/list', [WelcomePagesController::class, 'list'])
            ->middleware('permission:welcomePages.list');
        Route::post('welcomePages/update', [WelcomePagesController::class, 'update'])
            ->middleware('permission:welcomePages.update');
        Route::post('welcomePages/delete', [WelcomePagesController::class, 'delete'])
            ->middleware('permission:welcomePages.delete');

        // APP引導頁
        Route::post('guidePage/list', [GuidePageController::class, 'list'])
            ->middleware('permission:guidePage.list');
        Route::post('guidePage/create', [GuidePageController::class, 'create'])
            ->middleware('permission:guidePage.create');
        Route::post('guidePage/update', [GuidePageController::class, 'update'])
            ->middleware('permission:guidePage.update');
        Route::post('guidePage/delete', [GuidePageController::class, 'delete'])
            ->middleware('permission:guidePage.delete');

        // 廣告蓋版設定
        Route::post('advertisement/list', [AdvertisementController::class, 'list'])
            ->middleware('permission:advertisement.list');
        Route::post('advertisement/detail', [AdvertisementController::class, 'detail'])
            ->middleware('permission:advertisement.list');
        Route::post('advertisement/create', [AdvertisementController::class, 'create'])
            ->middleware('permission:advertisement.create');
        Route::post('advertisement/update', [AdvertisementController::class, 'update'])
            ->middleware('permission:advertisement.update');
        Route::post('advertisement/delete', [AdvertisementController::class, 'delete'])
            ->middleware('permission:advertisement.delete');

        // 餐旅設定
        Route::post('diningHotel/list', [DiningHotelController::class, 'list'])
            ->middleware('permission:diningHotel.list');
        Route::post('diningHotel/detail', [DiningHotelController::class, 'detail'])
            ->middleware('permission:diningHotel.list');
        Route::post('diningHotel/create', [DiningHotelController::class, 'create'])
            ->middleware('permission:diningHotel.create');
        Route::post('diningHotel/update', [DiningHotelController::class, 'update'])
            ->middleware('permission:diningHotel.update');
        Route::post('diningHotel/delete', [DiningHotelController::class, 'delete'])
            ->middleware('permission:diningHotel.delete');
        Route::post('diningHotel/audit', [DiningHotelController::class, 'audit'])
            ->middleware('permission:diningHotel.audit');
        Route::post('diningHotel/final', [DiningHotelController::class, 'final'])
            ->middleware('permission:diningHotel.final');
        Route::post('diningHotel/updateIntroduce', [DiningHotelController::class, 'updateIntroduce'])
            ->middleware('permission:diningHotel.update');
        Route::post('diningHotel/updateKnow', [DiningHotelController::class, 'updateKnow'])
            ->middleware('permission:diningHotel.update');

        // 餐旅預約列表
        Route::post('diningBooking/list', [DiningBookingController::class, 'list'])
            ->middleware('permission:diningBooking.list');
        Route::post('diningBooking/detail', [DiningBookingController::class, 'detail'])
            ->middleware('permission:diningBooking.list');
        Route::post('diningBooking/update', [DiningBookingController::class, 'update'])
            ->middleware('permission:diningBooking.update');
        Route::post('diningBooking/cancel', [DiningBookingController::class, 'cancel'])
            ->middleware('permission:diningBooking.update');
        Route::post('diningBooking/export', [DiningBookingController::class, 'export'])
            ->middleware('permission:diningBooking.list');
        Route::post('diningBooking/payment', [DiningBookingController::class, 'payment'])
            ->middleware('permission:diningBooking.update');

        // 充電繳費紀錄報表
        Route::post('order/list', [OrderController::class, 'list'])
            ->middleware('permission:order.list');
        Route::post('order/detail', [OrderController::class, 'detail'])
            ->middleware('permission:order.list');
        Route::post('order/save', [OrderController::class, 'save'])
            ->middleware('permission:order.save');
        Route::post('order/update', [OrderController::class, 'update'])
            ->middleware('permission:order.update');
        Route::post('order/export', [OrderController::class, 'export'])
            ->middleware('permission:order.list');

        // 退款紀錄
        Route::post('orderRefund/list', [OrderRefundController::class, 'list'])
            ->middleware('permission:orderRefund.list');
        Route::post('orderRefund/detail', [OrderRefundController::class, 'detail'])
            ->middleware('permission:orderRefund.list');
        Route::post('orderRefund/export', [OrderRefundController::class, 'export'])
            ->middleware('permission:orderRefund.list');

        // 儀表板
        Route::post('dashboard/pile_number', [DashboardController::class, 'pileNumber'])
            ->middleware('permission:home.list');
        Route::post('dashboard/order_number', [DashboardController::class, 'orderNumber'])
            ->middleware('permission:home.list');
        Route::post('dashboard/amount_line', [DashboardController::class, 'amountLine'])
            ->middleware('permission:home.list');
        Route::post('dashboard/degree_line', [DashboardController::class, 'degreeLine'])
            ->middleware('permission:home.list');

        // 固定推播
        Route::post('firebaseNotice/list', [FirebaseNoticeController::class, 'list'])
            ->middleware('permission:firebaseNotice.list');
        Route::post('firebaseNotice/detail', [FirebaseNoticeController::class, 'detail'])
            ->middleware('permission:firebaseNotice.list');
        Route::post('firebaseNotice/update', [FirebaseNoticeController::class, 'update'])
            ->middleware('permission:firebaseNotice.update');

        // 最新消息
        Route::post('message/list', [MessageController::class, 'list'])
            ->middleware('permission:message.list');
        Route::post('message/detail', [MessageController::class, 'detail'])
            ->middleware('permission:message.list');
        Route::post('message/create', [MessageController::class, 'create'])
            ->middleware('permission:message.create');
        Route::post('message/preview', [MessageController::class, 'preview']);
        Route::post('message/update', [MessageController::class, 'update'])
            ->middleware('permission:message.update');
        Route::post('message/cancel', [MessageController::class, 'cancel'])
            ->middleware('permission:message.update');
        // Route::post('message/delete', [MessageController::class, 'delete'])
        //     ->middleware('permission:message.delete');

        // 公告推播
        Route::post('firebasePush/list', [FirebasePushController::class, 'list'])
            ->middleware('permission:firebasePush.list');
        Route::post('firebasePush/detail', [FirebasePushController::class, 'detail'])
            ->middleware('permission:firebasePush.list');
        Route::post('firebasePush/create', [FirebasePushController::class, 'create'])
            ->middleware('permission:firebasePush.create');
        Route::post('firebasePush/update', [FirebasePushController::class, 'update'])
            ->middleware('permission:firebasePush.update');
        Route::post('firebasePush/delete', [FirebasePushController::class, 'delete'])
            ->middleware('permission:firebasePush.delete');

        // 版本控制
        Route::post('versionControl/list', [VersionControlController::class, 'list'])
            ->middleware('permission:versionControl.list');
        Route::post('versionControl/update', [VersionControlController::class, 'update'])
            ->middleware('permission:versionControl.update');

        // 充電樁廠家
        Route::post('manufacturer/list', [App\Http\Controllers\Backend\ManufacturerController::class, 'list'])
            ->middleware('permission:manufacturer.list');
        Route::post('manufacturer/detail', [App\Http\Controllers\Backend\ManufacturerController::class, 'detail'])
            ->middleware('permission:manufacturer.list');
        Route::post('manufacturer/create', [App\Http\Controllers\Backend\ManufacturerController::class, 'create'])
            ->middleware('permission:manufacturer.create');
        Route::post('manufacturer/update', [App\Http\Controllers\Backend\ManufacturerController::class, 'update'])
            ->middleware('permission:manufacturer.update');
        Route::post('manufacturer/delete', [App\Http\Controllers\Backend\ManufacturerController::class, 'delete'])
            ->middleware('permission:manufacturer.delete');

        //AutoFillRoute

    });
});
