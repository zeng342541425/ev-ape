
<!--   '姓名', '手機號碼(帳號)', 'Email', '註冊時間', '目前點數', '車用品牌', '充電預約', '黑名單'> -->
<table>
    <thead>
    <tr>
        <th>姓名</th>
        <th>手機號碼(帳號)</th>
        <th>Email</th>
        <th>註冊時間</th>
        <th>目前點數</th>
        <th>車用品牌</th>
        <th>充電預約</th>
        <th>黑名單</th>
    </tr>
    </thead>
    <tbody>

        <tr>
            <td>{{ $user_info->name }}</td>
            <td>{{ $user_info->phone }}</td>
            <td>{{ $user_info->email }}</td>
            <td>{{ $user_info['created_at'] }}</td>
            <td>{{ $user_info->points }}</td>
            <td>{{ $user_info['brand']['brand_name'] ?? '' }}</td>
            <td>{{ $appointment_status_map[$user_info->appointment_status] }}</td>
            <td>{{ $status_map[$user_info->status]}}</td>
        </tr>

    </tbody>
</table>



<table>
    <thead>
    <tr>
        <th>發票類型</th>
        <th>發票抬頭</th>
        <th>統一編號</th>
        <th>新增時間</th>
    </tr>
    </thead>
    <tbody>

    @php
        $invoice_type = [1=> "手機條碼",2=> "自然人憑證",3=> "三聯發票",];
    @endphp
    @foreach($user_invoices as $v)
        <tr>
            <td>{{ $invoice_type[$v['type']] }}</td>
            <td>{{ $v->title }}</td>
            <td>{{ $v->tax_id }}</td>
            <td>{{ $v->updated_at }}</td>
        </tr>
    @endforeach


    </tbody>
</table>

<table>
    <thead>
    <tr>
        <th>訂單ID</th>
        <th>站點區域</th>
        <th>充電站名稱</th>
        <th>充電日期</th>
        <th>充電時段</th>
        <th>充電樁編號</th>
        <th>充電度數（kWh）</th>
        <th>充電時長（分鐘）</th>
    </tr>
    </thead>
    <tbody>

    @foreach($user_orders as $v)
    <tr>
        <td>{{ $v->id }}</td>
        <td>{{ $v['parking']['region']['name']??'' . $v['parking']['village']['name']??''}}</td>
        <td>{{ $v->parking_lot_name }}</td>
        <td>{{ $v->trade_date }}</td>
        <td>{{ date('H:i',strtotime($v->starting_time))."-".date('H:i',strtotime($v->ending_time)) }}</td>
        <td>{{ $v->pile_no }}</td>
        <td>{{ $v->degree }}</td>
        <td>{{ $v->duration }}</td>
    </tr>
    @endforeach

    </tbody>
</table>


<table>
    <thead>
    <tr>

        <th>站點區域</th>
        <th>充電站名稱</th>
        <th>充電樁編號</th>
        <th>預約日期</th>
        <th>預約時間</th>
        <th>狀態</th>
    </tr>
    </thead>
    <tbody>

    @php
        $appointment_type = ['0'=>"待處理",'1'=> "已抵達",'2'=> "已取消",'3'=> "系統取消",];
    @endphp
    @foreach($user_appointments as $v)
    <tr>
        <td>{{ $v['parking']['region']['name'] ?? ''.$v['parking']['village']['name'] ?? '' }}</td>
        <td>{{ $v['parking']['parking_lot_name'] ?? '' }}</td>
        <td>{{ $v->pile_no }}</td>
        <td>{{ date('Y-m-d',strtotime($v->appointment_at)) }}</td>
        <td>{{ date('H:i',strtotime($v->appointment_at)) }}</td>
        <td>{{ $appointment_type[$v['status']] }}</td>

    </tr>
    @endforeach

    </tbody>
</table>
