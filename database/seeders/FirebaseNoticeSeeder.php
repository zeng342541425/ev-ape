<?php

namespace Database\Seeders;

use App\Models\Common\FirebaseNotice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FirebaseNoticeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=FirebaseNoticeSeeder
     *
     * @return void
     */
    public function run(): void
    {

        $this->createSeeder();
        // $this->updateSeeder();

    }

    public function createSeeder()
    {
        $data = [
            [
                'key' => 'vip_sign_in',
                'send_type' => '1',
                'project' => '註冊成功',
                'title' => '註冊成功',
                'content' => '歡迎您使用EV APE充電站服務，請填寫會員、付款、發票資訊，讓最Powerful的充電樁加速您的每一天！',
                'able' => '1',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'card_bind_success',
                'send_type' => '1',
                'project' => '信用卡驗證成功',
                'title' => '信用卡驗證成功',
                'content' => '信用卡驗證成功',
                'able' => '1',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'appointment',
                'send_type' => '1',
                'project' => '預約充電成功',
                'title' => '預約充電成功！',
                'content' => '請於預約時間10分鐘內抵達並啟動充電樁，若未能及時啟動充電樁，您的預約將被取消，敬請見諒。',
                'able' => '1',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'appointment_30',
                'send_type' => '1',
                'project' => '預約充電時間30分鐘前',
                'title' => '預約充電樁已被使用！',
                'content' => '您預約的充電樁已被使用，請再次預約，感謝您的配合。',
                'able' => '1',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'appointment_not_starting',
                'send_type' => '1',
                'project' => '預約充電未到場',
                'title' => '預約充電未到場！',
                'content' => '您未於預約的充電時間內10分鐘抵達現場，預約已被系統取消。',
                'able' => '1',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'order_payment_success',
                'send_type' => '1',
                'project' => '充電完成支付成功',
                'title' => '充電完成支付成功',
                'content' => '{ending_datetime}充電費用支付成功，感謝您使用EV APE充電站。本次消費金額為{amount}元，交易編號為{order_no}。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'order_payment_error',
                'send_type' => '1',
                'project' => '充電完成支付失敗',
                'title' => '充電完成支付失敗',
                'content' => '{ending_datetime}充電費用支付失敗，感謝您使用EV APE充電站。本次消費金額為{amount}元，交易編號為{order_no}。請至會員中心充電紀錄頁面補繳費用，補繳前請先確認提交的信用卡資訊與發卡機構相符，並確保卡片額度充足，如有疑慮請與發卡機構聯絡，感謝您的配合。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'order_full_refund',
                'send_type' => '1',
                'project' => '全額退款',
                'title' => '全額退款',
                'content' => '交易編號{order_no}之費用已全額退款，退款金額為{amount}元。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'order_partial_refund',
                'send_type' => '1',
                'project' => '部分退款',
                'title' => '部分退款',
                'content' => '交易編號{order_no}之費用已部分退款，退款金額為{amount}元。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'order_payment_supplement',
                'send_type' => '1',
                'project' => '充電完成支付失敗，補扣款',
                'title' => '充電完成支付失敗，補扣款',
                'content' => '您的充電的費用支付失敗，系統已自動補扣款，{ending_datetime}充電費用{amount}元，交易編號為{order_no}，感謝您使用EV APE充電站。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'order_payment_supplement_success',
                'send_type' => '1',
                'project' => '充電完成扣款失敗，補扣款成功',
                'title' => '充電完成扣款失敗，補扣款成功',
                'content' => ' 補扣款{ending_datetime}充電費用支付成功，感謝您使用EV APE充電站。消費金額為{amount}元，交易編號為{order_no}。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_booking_success',
                'send_type' => '1',
                'project' => '訂位成功',
                'title' => '訂位成功',
                'content' => '已完成訂位，EV APE已為您保留{datetime}，餐廳名稱{shop_name}，{number}位，敬請留意下列資訊：「於本APP訂位即享有保留席位之權益，如需更改訂位資訊請詳閱取消時間與付款政策」。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_booking_cancel',
                'send_type' => '1',
                'project' => '訂位取消(自己取消)',
                'title' => '訂位取消(自己取消)',
                'content' => '您已取消{datetime}{shop_name}的預約席位。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_manage_cancel',
                'send_type' => '1',
                'project' => '訂位取消(後台取消)',
                'title' => '訂位取消(後台取消)',
                'content' => '已為您取消{datetime}{shop_name}的預約席位。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_payment_success',
                'send_type' => '1',
                'project' => '現場報到支付成功',
                'title' => '現場報到支付成功',
                'content' => '本次消費金額為{amount}元，交易編號為{order_no}。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_payment_error',
                'send_type' => '1',
                'project' => '現場報到支付失敗',
                'title' => '現場報到支付失敗',
                'content' => '本次消費金額為{amount}元，交易編號為{order_no}。請至會員中心充電紀錄頁面補繳費用，補繳前請先確認提交的信用卡資訊與發卡機構相符，並確保卡片額度充足，如有疑慮請與發卡機構聯絡，感謝您的配合。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_expired_payment_success',
                'send_type' => '1',
                'project' => '現場未到支付成功',
                'title' => '現場未到支付成功',
                'content' => '本次消費金額為{amount}元，交易編號為{order_no}。{booking_name}您好，感謝您使用EV APE預訂今日{shop_name}的席次，由於您不曾取消本次訂位，系統遵循取消時間與付款政策，已收取您本次消費款項。如有任何疑問請洽EV APE客服中心。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_expired_payment_error',
                'send_type' => '1',
                'project' => '現場未到支付失敗',
                'title' => '現場未到支付失敗',
                'content' => '本次消費金額為{amount}元，交易編號為{order_no}。{booking_name}您好，感謝您使用EV APE預訂今日{shop_name}的席次，由於您不曾取消本次訂位，系統遵循取消時間與付款政策，等待您信用卡資訊更新後，將收取您本次消費款項。請至會員中心充電紀錄頁面補繳費用，補繳前請先確認提交的信用卡資訊與發卡機構相符，並確保卡片額度充足，如有疑慮請與發卡機構聯絡，感謝您的配合。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_cancel_in_days_free',
                'send_type' => '1',
                'project' => '餐旅可免費取消天數通知',
                'title' => '餐旅可免費取消天數通知',
                'content' => '親愛的猩會員您好，提醒您，您有預定{booking_datetime}{shop_name}的訂位服務，如於訂位{days}天內因個人因素(臨時取消、誤觸取消訂位等)造成訂位取消，將不退回訂位費用，請多見諒。如有其他訂位相關問題，請您聯繫客服中心，得視情況並依照各餐旅宿規範進行退費。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_cancel_yesterday_tips',
                'send_type' => '1',
                'project' => '前三天提醒是否取消通知',
                'title' => '前三天提醒是否取消通知',
                'content' => '親愛的猩會員您好，您將於三天後享用{shop_name}的服務，訂位將保留至{booking_datetime}。如果您有任何因素要取消本次訂位服務，請您聯繫客服中心，得視情況並依照各餐旅宿規範進行退費。若當天未出席，按照部分餐旅宿規定，會產生相應的違約費，您須自行承擔，還請多加注意!',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_payment_supplement',
                'send_type' => '1',
                'project' => '預約餐旅扣款失敗，補扣款',
                'title' => '預約餐旅扣款失敗，補扣款',
                'content' => '您的用餐費用支付失敗，系統已自動補扣款，{datetime}用餐費用{amount}元，交易編號為{order_no}。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_payment_supplement_success',
                'send_type' => '1',
                'project' => '預約餐旅扣款失敗，補扣款成功',
                'title' => '預約餐旅扣款失敗，補扣款成功',
                'content' => '補扣款{datetime}餐廳費用支付成功，感謝您使用EV APE充電站。消費金額為{amount}元，交易編號為{order_no}。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_cancel_payment_success',
                'send_type' => '1',
                'project' => '餐旅超過免費天數取消扣款成功通知',
                'title' => '超過免費取消天數，餐旅支付成功',
                'content' => '您已取消{datetime}{shop_name}的預約席位，因為已超過可以免費取消日期，消費金額為{amount}元，交易編號為{order_no}。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            [
                'key' => 'dining_cancel_payment_fail',
                'send_type' => '1',
                'project' => '餐旅超過免費天數取消扣款失敗通知',
                'title' => '超過免費取消天數，餐旅支付失敗',
                'content' => '您已取消{datetime}{shop_name}的預約席位，因為已超過可以免費取消日期，消費金額為{amount}元，交易編號為{order_no}。請至會員中心充電紀錄頁面補繳費用，補繳前請先確認提交的信用卡資訊與發卡機構相符，並確保卡片額度充足，如有疑慮請與發卡機構聯絡，感謝您的配合。',
                'able' => '0',
                'jump_type' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

        ];

        foreach($data as $v) {
            !FirebaseNotice::query()->where('key', $v['key'])->exists() && FirebaseNotice::query()->where('key', $v['key'])->insert($v);

        }

    }

    public function updateSeeder()
    {
        $data = [

        ];

        foreach($data as $v) {
            FirebaseNotice::query()->where('key', $v['key'])->exists() && FirebaseNotice::query()->where('key', $v['key'])->update($v);

        }
    }
}
