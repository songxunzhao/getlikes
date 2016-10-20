<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 10/3/2016
 * Time: 11:02 PM
 */
namespace App\Middleware;
use App\QB;

class Authentication {
    public function __invoke($request, $response, $next) {
        $token_list = $request->getHeader('authorization');
        $token_hash = md5($token_list[0]);
        $session = QB::table(
            'user_sessions'
        )->where(
            'token', '=', $token_hash
        )->where(
            QB::raw('expires_at > NOW()')
        )->first();
        if($session) {
            $user = QB::table('users')->find($session->user_id);
            if($user) {
                $request = $request->withAttribute('user', $user);
                return $next($request, $response);
            }
        }
        return $response->withJson(
            ['success' => false, 'message' => 'Authentication failure']
        );
    }
}