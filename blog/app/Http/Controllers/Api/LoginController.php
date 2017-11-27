<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use App\User;
use App\UserEquipments;
use App\OpticalPowerMeter;
use Validator;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;


class LoginController extends ApiController
{

    function index(){
        return "222222";
    }
    //
    // 登录用户名标示为phone字段
    public function username()
    {
        return 'email';//'phone';
    }

    /**
     * 获取登录TOKEN  登录
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function token(Request $request)
    {
        $username = $request->post('username');
        $user = User::orWhere('email', $username)->orWhere('name', $username)->first();

        if (!$user) {//&& ($user->status == 0)
            return response()->json(['message' => '账号不存在', 'status_code' => 300, 'data' => null]);
        }elseif($user->activity!=1){
            return response()->json(['message' => '账号未激活,请先去邮箱激活', 'status_code' => 300, 'data' => null]);
        }

        $http = new Client();
//        return config('passport') + ['username'=>$username,'password'=>$request->post('password')];

        try {
            $response = $http->post(request()->root() . '/api/oauth/token', [
                'form_params' =>config('passport') + ['username'=>$username,'password'=>$request->post('password')],
            ]);
            return json_decode((string) $response->getBody(), true);
        } catch (RequestException $e) {
            return response()->json(['message' => '账号验证失败', 'status_code' => 300, 'data' => null]);
        }

        if ($request->getStatusCode() == 401) {
            return response()->json(['message' => '账号验证失败', 'status_code' => 300, 'data' => null]);
        }
    }

    /**
     * 退出登录
     */
    public function logout(Request $request)
    {
//        return $request->header();
        if (\Auth::guard('api')->check()) {
            \Auth::guard('api')->user()->token()->delete();
        }
        return response()->json(['message' => '登出成功', 'status_code' => 200, 'data' => null]);
    }

    /*
     * 用户注册
     */
    public function register(Request $request){
        $pramns = $request->all();

        $validator = Validator::make($pramns, [
            'mail' => 'required',
            'password' => 'required',
            'name' => 'required',
        ]);
        if ($validator->fails())
        {
            $warnings = $validator->messages();
            $show_warning = $warnings->first();
            return response()->json(['message' =>$show_warning , 'status_code' => 300, 'data' => null]);
        }

        $email = $pramns['mail'];
        $name = $pramns['name'];
        $password = $pramns['password'];

        $is_registed = User::orWhere('email', "{$email}")->count(); //->orWhere('name', $name)
        if($is_registed){
            return response()->json(['message' => '这个邮箱已经注册过啦！', 'status_code' => 300, 'data' => null]);
        }
        $is_registed = User::orWhere('name', "{$name}")->count(); //->orWhere('name', $name)
        if($is_registed){
            return response()->json(['message' => '这个用户名已经注册过啦！', 'status_code' => 300, 'data' => null]);
        }

        $user = new User();
        $user->email = $email;
        $user->name = $name;
        $user->password = bcrypt($password);
        $user->save();
        $uid = $user->id;

//        var_dump($is_registed);exit;

        $code = mt_rand(10000,99999);
        Cache::store('file')->put('code_'.$code, $uid, 10000);

//        var_dump(array($code,$value));
        $data = ['email'=>$email, 'name'=>$name, 'uid'=>$uid, 'activationcode'=>$code];
        Mail::send('activemail', $data, function($message) use($data)
        {
            $message->to($data['email'], $data['name'])->subject('欢迎注册仪表管家，请激活您的账号！');
        });
        return response()->json(['message' => '注册成功!快去邮箱激活吧！', 'status_code' => 200, 'data' => $user]);
    }

    /*
     * 激活帐号
     */
    function active(Request $request){
        $pramns = $request->all();
        $code = $pramns['activationcode'];
        $uid = $pramns['uid'];
        $value = Cache::store('file')->pull('code_'.$code);

        if($uid == $value){
            User::where('id',$uid)->update(['activity'=>1]);
            echo '激活成功！快去登录吧';
        }else{
            echo '注册失败!';
        }
    }

    /*
     * 获取所有用户的接口
     */
    function getUsers(){
        $users = User::all();
        return  response()->json(['message' => 'success！.', 'status_code' => 200, 'data' => $users]);
    }

    /*
     * 获取所有该用户的设备的接口
     */
    function getUserEquipments(){
        $userEquipments = UserEquipments::all();
        return  response()->json(['message' => 'success！.', 'status_code' => 200, 'data' => $userEquipments]);

    }

    /*
     * 设备绑定到用户
     */
    function addEquipments(Request $request){
        $pramns = $request->all();
        $validator = Validator::make($pramns, [
            'serial_num' => 'required',
            'e_name' => 'required',
        ]);
        if ($validator->fails())
        {
            $warnings = $validator->messages();
            $show_warning = $warnings->first();
            return response()->json(['message' =>$show_warning , 'status_code' => 300, 'data' => null]);
        }

        $serial_num = $pramns['serial_num'];
        $name = $pramns['e_name'];

        if (\Auth::guard('api')->check()) {
            $uid = \Auth::guard('api')->user()->id;
            $is_bind = \App\UserEquipments::where('uid',$uid)->where('serial_num',$serial_num)->count();
            if($is_bind){
                return response()->json(['message' => '这个设备已经和您绑定过啦！', 'status_code' => 300, 'data' => null]);
            }
            $userEq = new UserEquipments();
            $userEq->e_name = $name;
            $userEq->serial_num = $serial_num;
            $userEq->uid = $uid;
            $userEq->save();
            return response()->json(['message' => '绑定成功！.', 'status_code' => 200, 'data' => null]);
        }else
            return response()->json(['message' => 'token失效请重新登录.', 'status_code' => 400, 'data' => null]);
    }

    /*
     * 获取所有该用户的数据的接口
     */
    function getEqMeterData(Request $request){
        $pramns = $request->all();
        $validator = Validator::make($pramns, [
            'serial_num' => 'required',
        ]);
        if ($validator->fails())
        {
            $warnings = $validator->messages();
            $show_warning = $warnings->first();
            return response()->json(['message' =>$show_warning , 'status_code' => 300, 'data' => null]);
        }
        $serial_num = $pramns['serial_num'];

        $OpticalPowerMeter = OpticalPowerMeter::where('serial_num',$serial_num)->orderBy('id','desc')->take(100)->get();
        return  response()->json(['message' => 'success！.', 'status_code' => 200, 'data' => $OpticalPowerMeter]);
    }

    /*
     * 上传数据单条
     */
    function addData(Request $request){
        $pramns = $request->all();
        $validator = Validator::make($pramns, [
            'serial_num' => 'required',
            'name' => 'required',
        ]);
        if ($validator->fails())
        {
            $warnings = $validator->messages();
            $show_warning = $warnings->first();
            return response()->json(['message' =>$show_warning , 'status_code' => 300, 'data' => null]);
        }

        $wavelength = $pramns['wavelength'];
        $mode = $pramns['mode'];
        $ref = $pramns['ref'];
        $name = $pramns['name'];
        $serial_num = $pramns['serial_num'];
        $dbm = $pramns['dbm'];

        if (\Auth::guard('api')->check()) {
            $uid = \Auth::guard('api')->user()->id;
            $is_bind = \App\UserEquipments::where('uid',$uid)->where('serial_num',$serial_num)->count();
            if(!$is_bind)
                return response()->json(['message' => '未查询到该设备！', 'status_code' => 300, 'data' => null]);

            $uid = \Auth::guard('api')->user()->id;
//            return ['serial_num'=>$serial_num,'name'=>$name,'wavelength'=>$wavelength,'dbm'=>$dbm,'ref'=>$ref,'mode'=>$mode,'uid'=>$uid];
            \App\OpticalPowerMeter::create(['serial_num'=>$serial_num,'name'=>$name,'wavelength'=>$wavelength,'dbm'=>$dbm,'ref'=>$ref,'mode'=>$mode,'uid'=>$uid]);
            return response()->json(['message' => '保存成功！.', 'status_code' => 200, 'data' => null]);
        }else
            return response()->json(['message' => 'token失效请重新登录.', 'status_code' => 400, 'data' => null]);
    }
}
