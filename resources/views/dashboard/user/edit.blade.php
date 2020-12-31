@extends('layouts.base',[
    'page' => 'User'
])

@section('content')
@if(Auth::user()->roles != 'investor' && Auth::user()->roles != 'crew')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="{{ url('user/delete').'/'.$user->id.'?e=1' }}">
			{{ csrf_field() }}
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete User</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>Are you sure to delete this user?</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-primary">Confirm</button>
				</div>
			</div>
		</form>
	</div>
</div>
<!--end::Modal-->
@endif
<!--begin::Portlet-->
<div class="m-portlet">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<span class="m-portlet__head-icon m--hide">
					<i class="la la-gear"></i>
				</span>
				<h3 class="m-portlet__head-text">
					Edit User
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
	<form class="m-form" method="post" action="{{ url('user/update').'/'.$user->id }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">
			@include('notif')
			<div class="m-form__section m-form__section--first">
				<div class="form-group m-form__group">
					<label for="id">Employee ID</label>
					<input type="id" class="form-control m-input" name="id" value="{{ $user->id }}" readonly>
				</div>
				<div class="form-group m-form__group">
					<label for="name">Username / Name:</label>
					<input type="text" class="form-control m-input" placeholder="Enter full name" name="name" value="{{ $user->name }}">
					<span class="m-form__help">Please enter your full name</span>
				</div>
				<div class="form-group m-form__group">
					<label for="email">Email address:</label>
					<input type="email" class="form-control m-input" placeholder="Enter email" name="email" value="{{ $user->email }}">
					<span class="m-form__help">We'll never share your email with anyone else</span>
				</div>
				<div class="form-group m-form__group">
					<label for="email">Phone:</label>
					<input type="text" class="form-control m-input" placeholder="Enter phone number" name="phone" value="{{ $user->phone }}">
				</div>
				@if(Auth::user()->roles == 'client' || $user->roles == 'client')
					<input type="hidden" name="roles" value="client"/>
				@else
					<div class="form-group m-form__group">
						<label for="roles">Role:</label>
						<select class="form-control m-input m-input--square m-select2" name="roles">
							<option value="admin" @if($user->roles == 'admin'){{'selected'}}@endif>Admin</option>
							<option value="crew" @if($user->roles == 'crew'){{'selected'}}@endif>Crew</option>
							<option value="head" @if($user->roles == 'head'){{'selected'}}@endif>Warehouse Head</option>
							<option value="investor" @if($user->roles == 'investor'){{'selected'}}@endif>Investor</option>
						</select>
					</div>
				@endif
				<div class="form-group m-form__group">
					<label for="warehouse_id">Warehouse</label>
					<select class="form-control m-input m-input--square m-select2" name="warehouse_id">
						@foreach(\App\Warehouse::all() as $warehouse)
							<option value="{{ $warehouse->id }}" @if($user->warehouse_id == $warehouse->id){{'selected'}}@endif>{{ $warehouse->name }}</option>
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
						<option value="A" @if($user->status == 'A') selected @endif>Active</option>
						<option value="I" @if($user->status == 'I') selected @endif>Inactive</option>
					</select>
					<span class="m-form__help">If user status is inactive, it cannot login</span>
				</div>
			</div>
		</div>
		@if(Auth::user()->roles != 'investor')
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Save</button>
				<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#m_modal_1">Delete</button>
			</div>
		</div>
		@endif
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