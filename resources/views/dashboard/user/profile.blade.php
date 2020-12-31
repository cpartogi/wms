@extends('layouts.base',[
    'page' => 'Profile'
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
					Edit Profile
				</h3>
			</div>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form" method="post" action="{{ url('profile/update') }}" enctype="multipart/form-data">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first">
				<input type="hidden" name="user_id" value="{{ $user->id }}"/>
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
					<label for="password">Password:</label>
					<input type="password" class="form-control m-input" name="password" />
				</div>
				<div class="form-group m-form__group">
					<label for="pictures">Profile Picture:</label>
					@if(isset($user->pictures))
						<img src="https://s3-ap-southeast-1.amazonaws.com/static-pakde/{{ str_replace(' ','+',$user->pictures) }}" width="100"/>
					@else
						<img src="{{ asset('mt/default/assets/app/media/img/users/default.jpg') }}" width="100"/>
					@endif
					<input type="file" name="pictures" class="product-img" accept="image/*" >
				</div>
			</div>
		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Save</button>
			</div>
		</div>
	</form>
	<!--end::Form-->
</div>
<!--end::Portlet-->
@endsection