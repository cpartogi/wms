<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <!-- begin::Head -->
    <head>
        <meta charset="utf-8" />
        <title>{{ config('app.name', 'Pakde Warehouse Management System') }}</title>
        <meta name="description" content="Latest updates and statistic charts">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!--begin::Web font -->
        <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.16/webfont.js"></script>
        <script>
          WebFont.load({
            google: {"families":["Montserrat:300,400,500,600,700","Roboto:300,400,500,600,700"]},
            active: function() {
                sessionStorage.fonts = true;
            }
          });
        </script>
        <!--end::Web font -->
        <!--begin::Base Styles -->  
        <link href="{{ asset('mt/default/assets/vendors/base/vendors.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('mt/demo3/assets/demo/demo3/base/style.bundle.css') }}" rel="stylesheet" type="text/css" />
        <!--end::Base Styles -->
        <!--begin::Extended Styles -->  
        @yield('style')
        <!--end::Extended Styles -->
        <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" />
    </head>
    <!-- end::Head -->
    <!-- end::Body -->
    <body  class="m-page--fluid m--skin- m-content--skin-light2 m-header--fixed m-header--fixed-mobile m-aside-left--enabled m-aside-left--skin-dark m-aside-left--offcanvas m-footer--push m-aside--offcanvas-default">
        @yield('json')
        @yield('modal')
        <!-- begin:: Page -->
        <div class="m-grid m-grid--hor m-grid--root m-page">
            @include('dashboard.main.header')     
            <!-- begin::Body -->
            <div class="m-grid__item m-grid__item--fluid m-grid m-grid--ver-desktop m-grid--desktop m-body">
                <!-- BEGIN: Left Aside -->
                <button class="m-aside-left-close m-aside-left-close--skin-dark" id="m_aside_left_close_btn">
                    <i class="la la-close"></i>
                </button>
                @include('dashboard.main.aside')
                <div class="m-grid__item m-grid__item--fluid m-wrapper">
                    <!-- BEGIN: Subheader -->
                    <div class="m-subheader ">
                        <div class="d-flex align-items-center">
                            <div class="mr-auto">
                                <h3 class="m-subheader__title ">
                                    {{ $page }}
                                </h3>
                            </div>
                        </div>
                    </div>
                    <!-- END: Subheader -->
                    <div class="m-content">
                        @yield('content')
                    </div>
                </div>
            </div>
            <!-- end:: Body -->
            @include('dashboard.main.footer')
        </div>
        <!-- end:: Page -->
        @include('dashboard.main.sidebar')
        <!-- begin::Scroll Top -->
        <div id="m_scroll_top" class="m-scroll-top">
            <i class="la la-arrow-up"></i>
        </div>
        <!-- end::Scroll Top -->
        <!--begin::Base Scripts -->
        <script src="{{ asset('mt/default/assets/vendors/base/vendors.bundle.js') }}" type="text/javascript"></script>
        <script src="{{ asset('mt/demo3/assets/demo/demo3/base/scripts.bundle.js') }}" type="text/javascript"></script>
        <!--end::Base Scripts -->   
        <!--begin::Extended Scripts -->
        @yield('script')
        <script>
            $.sessionTimeout({
                message: 'Your session will be locked in thirty seconds.',
                keepAlive: false,
                logoutUrl: '/logout',
                redirUrl: '/logout',
                warnAfter: 900000,
                redirAfter: 930000,
                ignoreUserActivity: true,
                countdownMessage: "Redirecting in {timer} seconds.",
                countdownBar: true
            });
        </script>
        <!--end::Extended Scripts -->
    </body>
    <!-- end::Body -->
</html>