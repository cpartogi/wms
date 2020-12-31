@if(Session::has('success'))
<div class="m-alert m-alert--icon m-alert--air alert alert-success alert-dismissible fade show" role="alert">
	<div class="m-alert__icon">
		<i class="la la-warning"></i>
	</div>
	<div class="m-alert__text">
		<strong>Success!</strong> {{ Session::get('success') }}.
	</div>
	<div class="m-alert__close">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
		</button>
	</div>
</div>
@endif

@if($errors->count() > 0)
<div class="m-alert m-alert--icon m-alert--icon-solid m-alert--outline alert alert-danger alert-dismissible fade show" role="alert">
	<div class="m-alert__icon">
		<i class="flaticon-exclamation-1"></i>
		<span></span>
	</div>
	<div class="m-alert__text">
		<strong>Uh oh!</strong>
		<ul class="list-styled">
		@foreach ($errors->all() as $error)
			<li>{{ $error }}</li>
		@endforeach
		</ul>
	</div>
	<div class="m-alert__close">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
		</button>
	</div>
</div>
@endif

@if(Session::has('error'))
<div class="m-alert m-alert--icon m-alert--icon-solid m-alert--outline alert alert-danger alert-dismissible fade show" role="alert">
	<div class="m-alert__icon">
		<i class="flaticon-exclamation-1"></i>
		<span></span>
	</div>
	<div class="m-alert__text">
		<strong>Uh oh!</strong> {{ Session::get('error') }}.
	</div>
	<div class="m-alert__close">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
		</button>
	</div>
</div>
@endif