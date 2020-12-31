@extends('layouts.app')

@section('content')
<div class="m-login__forget-password"  style="display:block;">
    <div class="m-login__head">
        <h3 class="m-login__title">
            Reset Password
        </h3>
    </div>
    <form class="m-login__form m-form" action="{{ route('password.request') }}" method="post">
        {{ csrf_field() }}
        <div class="form-group m-form__group{{ $errors->has('email') ? ' has-error' : '' }}">
            <input class="form-control m-input" type="text" placeholder="Email" name="email" autocomplete="off" value="{{ $email or old('email') }}" required autofocus>
            @if ($errors->has('email'))
                <span class="help-block">
                    <strong>{{ $errors->first('email') }}</strong>
                </span>
            @endif
        </div>
        <div class="form-group m-form__group{{ $errors->has('password') ? ' has-error' : '' }}">
            <input class="form-control m-input" type="password" placeholder="New Password" name="password" id="password" required>
            @if ($errors->has('password'))
                <span class="help-block">
                    <strong>{{ $errors->first('password') }}</strong>
                </span>
            @endif
        </div>
        <div class="form-group m-form__group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
            <input class="form-control m-input" type="password" placeholder="Confirm Password" name="password_confirmation" id="password-confirm" required>
            @if ($errors->has('password_confirmation'))
                <span class="help-block">
                    <strong>{{ $errors->first('password_confirmation') }}</strong>
                </span>
            @endif
        </div>
        <div class="m-login__form-action">
            <button type="submit" class="btn btn-primary m-btn m-btn--pill m-btn--custom m-btn--air m-login__btn m-login__btn--primaryr">
                Reset Password
            </button>
        </div>
    </form>
</div>
@endsection
