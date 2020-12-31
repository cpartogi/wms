@extends('layouts.base',[
    'page' => 'Order'
])

@section('content')
    <div class="m-portlet m-portlet--mobile">
        <div class="m-portlet__body">

            @include('notif')
            <!--begin: Datatable -->
            <table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
                <thead>
                <tr>
                    <th width="10%">Problem</th>
                </tr>
                </thead>
                <tbody>
                @foreach($problems as $problem)
                    <tr>
                        <td>{{$request_id}} {{ $problem->problem }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <th width="10%">Problem</th>
                </tr>
                </tfoot>
            </table>
            <a href="{{ route('order-issue-detail-index') }}" class="btn btn-secondary">Back</a>
            <a href="/order/detail/revalidate/{{ $request_id }}" class="btn btn-primary">Revalidate Order</a>
        </div>
    </div>
@endsection

@section('style')
    <link href="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
@endsection

@section('script')
    <script src="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/vendors/jquery-throttle-debounce/jquery.ba-throttle-debounce.js') }}" type="text/javascript"></script>
@endsection