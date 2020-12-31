@extends('layouts.app')

@section('content')
<div class="m-login__signup" style="display:block;">
    <div class="m-login__head">
        <h3 class="m-login__title">
            Sign Up
        </h3>
        <div class="m-login__desc">
            Enter your details to create your account:
        </div>
    </div>
    <form class="m-login__form m-form" action="{{ route('register') }}" method="post">
        {{ csrf_field() }}
        <div class="form-group m-form__group{{ $errors->has('name') ? ' has-error' : '' }}">
            <input class="form-control m-input" type="text" placeholder="Fullname" id="name" name="name" value="{{ old('name') }}" required autofocus>
            @if ($errors->has('name'))
                <span class="help-block">
                    <strong>{{ $errors->first('name') }}</strong>
                </span>
            @endif
        </div>
        <div class="form-group m-form__group{{ $errors->has('email') ? ' has-error' : '' }}">
            <input id="email" class="form-control m-input" type="text" placeholder="Email" name="email" autocomplete="off" required>
            @if ($errors->has('email'))
                <span class="help-block">
                    <strong>{{ $errors->first('email') }}</strong>
                </span>
            @endif
        </div>
        <div class="form-group m-form__group{{ $errors->has('password') ? ' has-error' : '' }}">
            <input class="form-control m-input" type="password" placeholder="Password" id="password" name="password" required>
            @if ($errors->has('password'))
                <span class="help-block">
                    <strong>{{ $errors->first('password') }}</strong>
                </span>
            @endif
        </div>
        <div class="form-group m-form__group">
            <input id="password-confirm" class="form-control m-input m-login__form-input--last" type="password" placeholder="Confirm Password" name="password_confirmation" required>
        </div>
        <div class="row form-group m-form__group m-login__form-sub">
            <div class="col m--align-left">
                <label class="m-checkbox m-checkbox--primary">
                    <input type="checkbox" name="agree">
                    I Agree the
                    <a href="#" class="m-link m-link--focus">
                        terms and conditions
                    </a>
                    .
                    <span></span>
                </label>
                <span class="m-form__help"></span>
            </div>
        </div>
        <div class="m-login__form-action">
            <button class="btn btn-primary m-btn m-btn--pill m-btn--custom m-btn--air m-login__btn" type="submit">
                Sign Up
            </button>
            &nbsp;&nbsp;
            <a href="{{ route('login') }}" class="btn btn-outline-primary m-btn m-btn--pill m-btn--custom  m-login__btn">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
