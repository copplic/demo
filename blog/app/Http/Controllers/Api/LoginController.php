<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use App\User;
use Validator;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;


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
    public function logout()
    {
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
            return response()->json(['message' => '这个邮箱已经注册过啦！', 'status_code' => 300, 'data' => null]);exit;
        }

        $user = new User();
        $user->email = $email;
        $user->name = $name;
        $user->password = bcrypt($password);
        $user->save();
        $uid = $user->id;

//        var_dump($is_registed);exit;

        $code = mt_rand(10000,99999);

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

    }
}
