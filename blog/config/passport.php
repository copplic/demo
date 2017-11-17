<?php
/**
 * Created by PhpStorm.
 * User: pp
 * Date: 2017/11/17
 * Time: 10:49
 */

return [
    'grant_type' => env('OAUTH_GRANT_TYPE'),
    'client_id' => env('OAUTH_CLIENT_ID'),
    'client_secret' => env('OAUTH_CLIENT_SECRET'),
    'scope' => env('OAUTH_SCOPE', '*'),
];