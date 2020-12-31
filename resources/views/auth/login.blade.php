@extends('layouts.app')

@section('content')
<div class="m-login__signin">
    <div class="m-login__head">
        <h3 class="m-login__title">
            Sign In To Admin
        </h3>
    </div>
    <form class="m-login__form m-form" action="{{ route('login') }}" method="post">
        {{ csrf_field() }}
        <div class="form-group m-form__group{{ $errors->has('email') ? ' has-error' : '' }}">
            <input class="form-control m-input" id="email" value="{{ old('email') }}" type="text" placeholder="Email" name="email" autocomplete="off" required autofocus>
            @if ($errors->has('email'))
                <span class="help-block">
                    <strong>{{ $errors->first('email') }}</strong>
                </span>
            @endif
        </div>
        <div class="form-group m-form__group{{ $errors->has('password') ? ' has-error' : '' }}">
            <input class="form-control m-input m-login__form-input--last" type="password" placeholder="Password" name="password" id="password" required>
            @if ($errors->has('password'))
                <span class="help-block">
                    <strong>{{ $errors->first('password') }}</strong>
                </span>
            @endif
        </div>
        <div class="row m-login__form-sub">
            <div class="col m--align-left m-login__form-left">
                <label class="m-checkbox  m-checkbox--primary">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    Remember me
                    <span></span>
                </label>
            </div>
            <div class="col m--align-right m-login__form-right">
                <a href="{{ route('password.request') }}" class="m-link">
                    Forget Password ?
                </a>
            </div>
        </div>
        <div class="m-login__form-action">
            <button class="btn btn-primary m-btn m-btn--pill m-btn--custom m-btn--air m-login__btn m-login__btn--primary">
                Sign In
            </button>
        </div>
    </form>
</div>
<!--<div class="m-login__account">
    <span class="m-login__account-msg">
        Don't have an account yet ?
    </span>
    &nbsp;&nbsp;
    <a href="{{ route('register') }}" class="m-link m-link--light m-login__account-link">
        Sign Up
    </a>
</div>-->
@endsection
