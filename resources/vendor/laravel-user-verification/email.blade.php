Click here to verify your account:
<a href="{!! env('APP_URL') . '/verify?email=' . urlencode($user->email) . '&token=' . $user->verification_token !!}">{!! env('APP_URL') . '/verify?email=' . urlencode($user->email) . '&token=' . $user->verification_token !!}</a>
