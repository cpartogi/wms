@extends('layouts.base',[
    'page' => 'Integration'
])

@section('json')
<script>
	var params = {current:"{{ $user->secret_key }}",url:"{{ url('integration/ajax-generate') }}"};
</script>
@endsection

@section('content')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Force Refresh Key</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<p>Are you sure to refresh current active key? You need to change it on your environment too, the auto refresh is not applied.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" id="cancel-btn">Cancel</button>
				<button type="button" class="btn btn-primary">Confirm</button>
			</div>
		</div>
	</div>
</div>
<!--end::Modal-->
<!--begin::Portlet-->
<div class="m-portlet">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<span class="m-portlet__head-icon m--hide">
					<i class="la la-gear"></i>
				</span>
				<h3 class="m-portlet__head-text">
					Configuration
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="http://api.clientname.co.id/docs" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air" target="_blank">
						<span>
							<i class="la la-file-code-o"></i>
							<span>Api Documentation</span>
						</span>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form" method="post" action="{{ url('integration/update') }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">
			@include('notif')
			<div class="form-group m-form__group">
				<div class="row">
					<div class="col-sm-6 col-xs-12">
						<label>Client ID:</label>
						<div class="input-group">
							<input type="text" class="form-control to-copy" name="client_key" value="{{ $user->client_key }}" readonly="" />
							<div class="input-group-append">
								<button class="btn btn-warning copy_btn" type="button"><i class="la la-copy"></i> Copy</button>
							</div>
						</div>
						<span class="m-form__help text-copy" style="display:none;"><span class="m--font-success">Copied!</span></span>
					</div>
					<div class="col-sm-6 col-xs-12">
						<label>Client Secret:</label>
						<div class="input-group">
							<div class="input-group-prepend">
								<button class="btn btn-primary" type="button" id="generate_key">Generate Key</button>
							</div>
							<input type="text" class="form-control to-copy" name="api_key" placeholder="Consumer key goes in here..." readonly="" value="{{ $user->secret_key }}">
							<div class="input-group-append">
								<button class="btn btn-warning copy_btn" type="button" id="copy_btn"><i class="la la-copy"></i> Copy</button>
							</div>
						</div>
						<span class="m-form__help text-copy" style="display:none;"><span class="m--font-success">Copied!</span></span>
						@if($user->key_expired != null)
						<span class="m-form__help">Expired Until : <span class="m--font-brand">{{ date('d M Y',strtotime($user->key_expired)) }}</span></span>
						@endif
					</div>
				</div>
			</div>
			<div class="form-group m-form__group">
				<div class="row">
					<div class="col-sm-6 col-xs-12">
						<label>Callback URL:</label>
						<input type="text" class="form-control" name="allowed_url" value="{{ $user->allowed_url }}"/>
						<span class="m-form__help">Enter your callback URL, this will be called on certain endpoint</span>
					</div>
					<div class="col-sm-6 col-xs-12">
						<label for="auto_refresh">Auto-Refresh (Secure Client Secret):</label><br>
						<span class="m-switch m-switch--icon m-switch--primary">
							<label>
								<input type="checkbox" @if($user->auto_refresh == 1){{'checked="checked"'}}@endif name="auto_refresh" value="on">
								<span></span>
							</label>
						</span><br>
						<span class="m-form__help">Turn on this option will auto-refresh your expired <i>Secret Key</i>. Keep it mind to update.</span>
					</div>
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

@section('script')
<script src="{{ asset('js/pages/integrate.min.js') }}"></script>
<script>jQuery(document).ready(function(){I.init();});</script>
@endsection