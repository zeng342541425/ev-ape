<?php

namespace App\Http\Controllers\Backend\User;

use App\Exports\BaseExport;
use App\Exports\UserDatumExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\User\User\CreateRequest;
use App\Http\Requests\Backend\User\User\IdRequest;
use App\Http\Requests\Backend\User\User\ListRequest;
use App\Http\Requests\Backend\User\User\UpdateRequest;
use App\Models\Backend\User\User;
use App\Models\Common\UserFirebase;
use App\Models\Frontend\User\User as LoginUser;
use App\Models\Order\Order;
use App\Models\User\CreditCard;
use App\Models\User\Invoice;
use App\Services\Common\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UserController extends Controller
{
    /**
     * 列表
     *
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->all();

        return $this->success($this->_list($param));
    }

    /**
     * 列表
     *
     */
    public function _list($param, $paginate=true)
    {

        $query = User::query()->with('brand:id,brand_name');

        if (isset($param['name']) && $param['name'] != '') {
            $query->like('name', $param['name']);
        }
        if (isset($param['phone']) && $param['phone'] != '') {
            $query->like('phone', $param['phone']);
        }
        if (isset($param['email']) && $param['email'] != '') {
            $query->like('email', $param['email']);
        }
        if (isset($param['status']) && $param['status'] != '') {
            $query->where('status', '=', $param['status']);
        }

        if (!empty($param['starting_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $query->where('created_at', '>=', $starting_time);
        }

        if (!empty($param['ending_time'])) {
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where('created_at', '<=', $ending_time);
        }

        $query->orderByDesc('created_at');

        if ($paginate) {
            $list = $query->paginate($param['limit'] ?? 10);

            $data = $list->items();

            if ($data) {
                foreach($data as $k => $v) {
                    $data[$k]['brand_name'] = $v['brand']['brand_name'] ?? '';
                    unset($data[$k]['brand']);
                }
            }

            return [
                'list' => $list->items(),
                'total' => $list->total()
            ];
        } else {
            return $query->get()->toArray();
        }

    }

    /**
     * 詳情
     *
     * @param IdRequest $request
     * @return Response
     */
    public function detail(IdRequest $request): Response
    {
        $id = $request->post('id');

        $item = User::query()->with('brand:id,brand_name')->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }


        $item['brand_name'] = $item['brand']['brand_name'] ?? '';
        unset($item['brand']);
//        $brand_list = Order::query()->where('user_id', $id)->orderBy('id', 'desc')->get('brand_name')->toArray();
//        if ($brand_list) {
//            $item['brand_name'] = implode(',', array_column($brand_list, 'brand_name'));
//        }

        return $this->success([
            'item' => $item
        ]);
    }

    /**
     * 更新
     *
     * @param UpdateRequest $request
     * @param LoginUser $userModel
     * @return Response
     */
    public function update(UpdateRequest $request, LoginUser $userModel): Response
    {
        $id = $request->post('id');
        $param = $request->only([
            'appointment_status', 'notes', 'status',
        ]);

        $item = User::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        DB::beginTransaction();

        try {
            // 加入黑名單，不能登入，會告知被封鎖要聯繫客服(也發郵箱告知該會員)
            if ($param['status'] == 2) {

                $login_user = $userModel::query()->find($id);
                $login_user->tokens()->delete();

                $firebase_list = UserFirebase::query()->where('user_id', $id)->get()->toArray();
                if ($firebase_list) {
                    (new FirebaseService())->unsubTopics(array_column($firebase_list, 'firebase_token'));
                    UserFirebase::query()->where('user_id', $id)->delete();
                }

            }

            if (!$item->update($param)) {
                return $this->error(__('message.common.update.fail'));
            }

            DB::commit();

            return $this->success(null, __('message.common.update.success'));

        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 發票資訊
     *
     * @param IdRequest $request
     * @return Response
     */
    public function invoices(IdRequest $request): Response
    {
        $id = $request->post('id');

        $list = Invoice::query()->select([
            'id',
            'type',
            'title',
            'tax_id',
            'default',
            'updated_at as created_at',
        ])->where('user_id', $id)->orderBy('id', 'desc')->get()->toArray();

        return $this->success([
            'list' => $list
        ]);
    }

    /**
     * 信用卡資訊
     *
     * @param IdRequest $request
     * @return Response
     */
    public function cards(IdRequest $request): Response
    {
        $id = $request->post('id');

        $list = CreditCard::query()->select('type', 'card_number')
            ->where('user_id', $id)->orderBy('id', 'desc')->get()->toArray();

        return $this->success([
            'list' => $list
        ]);
    }

    public function export(Request $request)
    {

        $param = $request->all();
        $list = $this->_list($param, false);

        $headings = [
            '姓名', '手機號碼(帳號)', 'Email', '註冊時間', '目前點數', '車用品牌', '充電預約', '黑名單'
        ];

        $data = [];
        if ($list) {
            $appointment_status_map = [
                0 => '否',
                1 => '是',
            ];
            $status_map = [
                1 => '否',
                2 => '是',
            ];
            foreach($list as $v) {
                $data[] = [
                    $v['name'],
                    $v['phone'],
                    $v['email'],
                    $v['created_at'],
                    $v['points'],
                    $v['brand']['brand_name'] ?? '',
                    $appointment_status_map[$v['appointment_status']] ?? '',
                    $status_map[$v['status']] ?? '',
                ];
            }
        }

        return Excel::download(new BaseExport($data, $headings), '會員管理.xlsx');
    }

    public function exportDatum(Request $request)
    {

        return Excel::download(new UserDatumExport($request->post('id')), '會員資料.xlsx');

    }

}
