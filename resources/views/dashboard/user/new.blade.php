@extends('layouts.base',[
    'page' => 'User'
])

@section('content')
<!--begin::Portlet-->
<div class="m-portlet">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<span class="m-portlet__head-icon m--hide">
					<i class="la la-gear"></i>
				</span>
				<h3 class="m-portlet__head-text">
					Add New User
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('user') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to List</span>
						</span>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form" method="post" action="{{ url('user/create') }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">
			@include('notif')
			<div class="m-form__section m-form__section--first">
				<div class="form-group m-form__group">
					<label for="name">Username / Name:</label>
					<input type="text" class="form-control m-input" placeholder="Enter full name" name="name" value="{{ old('name') }}">
					<span class="m-form__help">Please enter your full name</span>
				</div>
				<div class="form-group m-form__group">
					<label for="email">Email address:</label>
					<input type="email" class="form-control m-input" placeholder="Enter email" name="email" value="{{ old('email') }}">
					<span class="m-form__help">We'll never share your email with anyone else</span>
				</div>
				<div class="form-group m-form__group">
					<label for="email">Phone:</label>
					<input type="text" class="form-control m-input" placeholder="Enter phone number" name="phone" value="{{ old('phone') }}">
				</div>
				@if(Auth::user()->roles == 'client')
					<input type="hidden" name="roles" value="client"/>
				@else
				<div class="form-group m-form__group">
					<label for="roles">Role:</label>
					<select class="form-control m-input m-input--square m-select2" name="roles">
						<option value="admin" @if(old('roles') == 'admin'){{'selected'}}@endif>Admin</option>
						<option value="crew" @if(old('roles') == 'crew'){{'selected'}}@endif>Crew</option>
						<option value="head" @if(old('roles') == 'head'){{'selected'}}@endif>Warehouse Head</option>
						<option value="investor" @if(old('roles') == 'investor'){{'selected'}}@endif>Investor</option>
					</select>
					<span class="m-form__help">Admin roles is the master of role</span>
				</div>
				@endif
				<div class="form-group m-form__group">
					<label for="warehouse_id">Warehouse</label>
					<select class="form-control m-input m-input--square m-select2" name="warehouse_id">
						@foreach(\App\Warehouse::all() as $warehouse)
							<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
						@endforeach
					</select>
					<span class="m-form__help">Warehouse base of this user</span>
				</div>
				<div class="form-group m-form__group">
					<label for="password">Password:</label>
					<input type="password" class="form-control m-input" name="password" />
				</div>
				<div class="form-group m-form__group">
					<label for="status">Status:</label>
					<select class="form-control m-input m-input--square m-select2" name="status">
						<option value="A" @if(old('status') == 'A') selected @endif>Active</option>
						<option value="I" @if(old('status') == 'I') selected @endif>Inactive</option>
					</select>
					<span class="m-form__help">If user status is inactive, it cannot login</span>
				</div>
			</div>
		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Add User</button>
			</div>
		</div>
	</form>
	<!--end::Form-->
</div>
<!--end::Portlet-->
@endsection

@section('script')
<script type="text/javascript">
	$(".m-select2").select2({ width: '100%' }); 
</script>
@endsection