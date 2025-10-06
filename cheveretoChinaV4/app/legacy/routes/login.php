<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use Chevereto\Legacy\Classes\Login;
use Chevereto\Legacy\Classes\RequestLog;
use Chevereto\Legacy\Classes\TwoFactor;
use Chevereto\Legacy\Classes\User;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\G\redirect;
use function Chevereto\Legacy\get_recaptcha_component;
use function Chevereto\Legacy\getSettings;
use function Chevereto\Legacy\must_use_recaptcha;
use function Chevereto\Legacy\recaptcha_check;
use function Chevereto\Vars\post;
use function Chevereto\Vars\request;
use function Chevereto\Vars\session;
use function Chevereto\Vars\sessionVar;

return function (Handler $handler) {
    if (post() !== [] && !$handler::checkAuthToken(request()['auth_token'] ?? '')) {
        $handler->issueError(403);

        return;
    }
    if ($handler->isRequestLevel(2)) {
        $handler->issueError(404);

        return;
    } // Allow only 1 level
    $logged_user = Login::getUser();
    User::statusRedirect($logged_user['status'] ?? null);
    if ($logged_user) {
        redirect(User::getUrl($logged_user));
    }
    $request_log_insert = ['type' => 'login', 'user_id' => null];
    $failed_access_requests = $handler::var('failed_access_requests');
    $SAFE_POST = $handler::var('safe_post');
    $is_error = false;
    $captcha_needed = $handler::cond('captcha_needed');
    $error_message = null;
    if ($captcha_needed && !empty(post())) {
        $captcha = recaptcha_check();
        if (!$captcha->is_valid) {
            $is_error = true;
            $error_message = _s('%s says you are a robot', 'reCAPTCHA');
        }
    }
    if (post() !== [] && !$is_error) {
        $login_by = filter_var(post()['login-subject'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if (trim(post()['login-subject']) == '' || trim(post()['password']) == '') {
            $is_error = true;
        }
        if (!$is_error) {
            $user = User::getSingle(
                trim(post()['login-subject']),
                $login_by,
                true
            );
            if ($user !== []) {
                $user['id'] = (int) $user['id'];
                $request_log_insert['user_id'] = $user['id'];
                switch ($user['status']) {
                    case 'awaiting-confirmation':
                        Login::setSignup([
                            'status' => 'awaiting-confirmation',
                            'email' => $user['email']
                        ]);
                        redirect('account/awaiting-confirmation');

                        break;
                    case 'banned':
                        $handler->issueError(403);

                        return;
                }
                $is_login = Login::checkPassword($user['id'], post()['password']);
            }
            if ($is_login ?? false) {
                $request_log_insert['result'] = 'success';
                RequestLog::insert($request_log_insert);
                $logged_user = Login::login($user['id']);
                Login::insertCookie('cookie', $user['id']);
                $redirect_to = User::getUrl(Login::getUser());
                if (TwoFactor::hasFor($user['id'])) {
                    sessionVar()->put('challenge_two_factor', $user['id']);
                    $redirect_to = 'account/two-factor';
                } elseif (isset(session()['last_url'])) {
                    $redirect_to = session()['last_url'];
                }
                if ($user['status'] == 'awaiting-email') {
                    $redirect_to = 'account/email-needed';
                }

                redirect($redirect_to);
            } else {
                $is_error = true;
            }
        }
        if ($is_error) {
            $request_log_insert['result'] = 'fail';
            RequestLog::insert($request_log_insert);
            $error_message = _s('Wrong Username/Email password combination');
            if (getSettings()['recaptcha'] && must_use_recaptcha($failed_access_requests['day'] + 1)) {
                $captcha_needed = true;
            }
        }
    }
    $handler::setCond('error', $is_error);
    if ($captcha_needed) {
        $handler::setCond('captcha_show', true);
        $handler::setVar(...get_recaptcha_component());
    }
    $handler::setCond('captcha_needed', $captcha_needed);
    $handler::setVar('pre_doctitle', _s('Sign in'));
    $handler::setVar('error', $error_message);
};
