<?php
/**
 * Created by PhpStorm.
 * User: pp
 * Date: 2017/11/17
 * Time: 10:41
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticateApi extends Authenticate
{
    protected function authenticate(array $guards)
    {

        if ($this->auth->guard('api')->check()) {
            return $this->auth->shouldUse('api');
        }

        throw new UnauthorizedHttpException('', 'Unauthenticated');
    }
}