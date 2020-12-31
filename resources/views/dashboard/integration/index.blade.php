@extends('layouts.base',[
    'page' => 'Integration'
])

@section('content')
<div class="m-portlet m-portlet--mobile">
	<div class="clearfix"></div>
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					List of Integration
				</h3>
			</div>
		</div>
	</div>
	<div class="m-portlet__body">
        <div class="m-portlet__head-tools">
            <a href="integration/jubelio" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air" id="jubelio-integration">
                <span>
                    <i class="la la-plus"></i>
                    <span>Jubelio</span>
                </span>
            </a>
		</div>
	</div>
</div>
@endsection