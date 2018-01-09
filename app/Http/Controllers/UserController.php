<?php
namespace App\Http\Controllers;

use App\User;
use Auth;
use Illuminate\Http\Request;
use Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;
use Jrean\UserVerification\Facades\UserVerification;
use Jrean\UserVerification\Exceptions\UserNotFoundException;
use Jrean\UserVerification\Exceptions\UserIsVerifiedException;
use Jrean\UserVerification\Exceptions\TokenMismatchException;

class UserController extends Controller
{
	public function register(Request $request)
	{
		$this->validate($request, [
			'name' => 'required',
			'email' => 'required|email|unique:users',
			'password' => 'required'
		]);

		$user = new User([
			'name' => $request->input('name'),
			'email' => $request->input('email'),
			'password' => bcrypt($request->input('password'))
		]);
		$user->save();
		UserVerification::generate($user);

		UserVerification::send($user, 'Nagg Email Verification', 'support@nagg.markmywordsmedia.com', 'Nagg Support');

		$token = JWTAuth::fromUser($user);

		return response()->json([
			'message' => 'Verification email has been sent.  Please verify your account to use the site.',
			'token' => $token
		], 201);
	}

	public function login(Request $request)
	{
		$this->validate($request, [
			'email' => 'required|email',
			'password' => 'required'
		]);
		$credentials = $request->only('email', 'password');
		try {
			if (!$token = JWTAuth::attempt($credentials)) {
				return response()->json([
					'error' => 'Invalid credentials!'
				], 422);
			}
		} catch (JWTException $e) {
			return response()->json([
				'error' => 'Could not create token!'
			], 500);
		}
		$user = User::whereEmail($credentials['email'])->first();
		return response()->json([
			'token' => $token,
			'verified' => $user->verified,
			'admin' => $user->hasRole('admin')
		], 200);
	}

	public function verify(Request $request)
	{
		$token = $request->input('token');
		$email = $request->input('email');
		try {
			UserVerification::process($request->input('email'), $token, 'users');
		} catch (UserNotFoundException $e) {
			return response()->json(['error' => 'User not found.']);
		} catch (UserIsVerifiedException $e) {
			return response()->json(['error' => 'Your account has already been verified.']);
		} catch (TokenMismatchException $e) {
			return response()->json(['error' => 'Invalid verification token.']);
		}
		$user = \App\User::whereEmail($email)->first();
		$token = JWTAuth::fromUser($user);
		return response()->json(['message' => 'Email successfully verified.', 'token' => $token]);
	}

	public function resend(Request $request)
	{
		$user = Auth::user();

		if ($user) {
			UserVerification::send($user, 'Nagg Email Verification', 'support@nagg.markmywordsmedia.com', 'Nagg Support');
			return response()->json(['message' => 'Verification email has been resent.']);
		}
		return response()->json(['error' => 'No user.']);
	}

	public function info()
	{
		$user = Auth::user();
		if (!$user)
			return ['verified' => false, 'admin' => false];
		else
			return ['verified' => $user->verified, 'admin' => $user->hasRole('admin')];
	}
}