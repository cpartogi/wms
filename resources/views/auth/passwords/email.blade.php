@extends('layouts.app')

@section('content')
<div class="m-login__forget-password" style="display:block;">
    <div class="m-login__head">
        <h3 class="m-login__title">
            Forgotten Password ?
        </h3>
        <div class="m-login__desc">
            Enter your email to reset your password:
        </div>
    </div>
    <form class="m-login__form m-form" action="{{ route('password.email') }}" method="post">
        {{ csrf_field() }}
        <div class="form-group m-form__group{{ $errors->has('email') ? ' has-error' : '' }}">
            <input class="form-control m-input" type="text" placeholder="Email" name="email" autocomplete="off" value="{{ old('email') }}" required>
            @if ($errors->has('email'))
                <span class="help-block">
                    <strong>{{ $errors->first('email') }}</strong>
                </span>
            @endif
        </div>
        <div class="m-login__form-action">
            <button type="submit" class="btn btn-primary m-btn m-btn--pill m-btn--custom m-btn--air  m-login__btn m-login__btn--primaryr">
                Request
            </button>
            &nbsp;&nbsp;
            <a href="{{ route('login') }}" class="btn btn-outline-primary m-btn m-btn--pill m-btn--custom m-login__btn">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
