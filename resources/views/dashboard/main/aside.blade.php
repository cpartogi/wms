<div id="m_aside_left" class="m-grid__item  m-aside-left  m-aside-left--skin-dark ">
    <!-- BEGIN: Aside Menu -->
    <div id="m_ver_menu" class="m-aside-menu  m-aside-menu--skin-dark m-aside-menu--submenu-skin-dark m-aside-menu--dropdown " data-menu-vertical="true" m-menu-dropdown="1" m-menu-scrollable="0" m-menu-dropdown-timeout="500">   
        <ul class="m-menu__nav  m-menu__nav--dropdown-submenu-arrow ">
            <li class="m-menu__item  @if($page == 'Dashboard'){{'m-menu__item--active'}}@endif">
                <a  href="{{ url('/') }}" class="m-menu__link">
                    <span class="m-menu__item-here"></span>
                    <i class="m-menu__link-icon flaticon-line-graph"></i>
                    <span class="m-menu__link-text">
                        Dashboard
                    </span>
                </a>
            </li>
            <li class="m-menu__section text-center">
                <h4 class="m-menu__section-text">APPS</h4>
                <i class="m-menu__section-icon flaticon-more-v2"></i>
            </li>
            @if(\Auth::user()->roles != 'client')
            <li class="m-menu__item  @if($page == 'Client'){{'m-menu__item--active'}}@endif">
                <a  href="{{ url('client') }}" class="m-menu__link">
                    <span class="m-menu__item-here"></span>
                    <i class="m-menu__link-icon flaticon-suitcase"></i>
                    <span class="m-menu__link-text">
                        Client
                    </span>
                </a>
            </li>
            @endif
            <li class="m-menu__item  m-menu__item--submenu @if($page == 'Product'){{'m-menu__item--active'}}@endif" aria-haspopup="true" m-menu-submenu-toggle="hover" m-menu-link-redirect="1"><a href="javascript:;" class="m-menu__link m-menu__toggle"><span class="m-menu__item-here"></span><i class="m-menu__link-icon la la-cubes"></i><span class="m-menu__link-text">Product</span><i class="m-menu__ver-arrow la la-angle-right"></i></a>
                <div class="m-menu__submenu "><span class="m-menu__arrow"></span>
                    <ul class="m-menu__subnav">
                        <li class="m-menu__item  m-menu__item--parent" aria-haspopup="true" m-menu-link-redirect="1"><span class="m-menu__link"><span class="m-menu__item-here"></span><span class="m-menu__link-text">Product</span></span></li>
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('product') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Manage Product</span></a></li>
                        @if(\Auth::user()->roles != 'client')
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('product/adjustment') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Adjusted Product</span></a></li>
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('productType') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Product Type Settings</span></a></li>
                        @endif
                    </ul>
                </div>
            </li>
            @if(\Auth::user()->roles != 'client')
            <li class="m-menu__item  @if($page == 'Warehouse'){{'m-menu__item--active'}}@endif">
                <a  href="{{ url('warehouse') }}" class="m-menu__link">
                    <span class="m-menu__item-here"></span>
                    <i class="m-menu__link-icon flaticon-buildings"></i>
                    <span class="m-menu__link-text">
                        Warehouse
                    </span>
                </a>
            </li>
            @endif
            <li class="m-menu__item  @if($page == 'Inbound'){{'m-menu__item--active'}}@endif">
                <a  href="{{url('inbound')}}" class="m-menu__link">
                    <span class="m-menu__item-here"></span>
                    <i class="m-menu__link-icon flaticon-open-box"></i>
                    <span class="m-menu__link-text">
                        Inbound
                    </span>
                </a>
            </li>
            <li class="m-menu__item  m-menu__item--submenu @if($page == 'Order' || $page == 'Outbound'){{'m-menu__item--active'}}@endif" aria-haspopup="true" m-menu-submenu-toggle="hover" m-menu-link-redirect="1"><a href="javascript:;" class="m-menu__link m-menu__toggle"><span class="m-menu__item-here"></span><i class="m-menu__link-icon flaticon-notes"></i><span class="m-menu__link-text">Order</span><i class="m-menu__ver-arrow la la-angle-right"></i></a>
                <div class="m-menu__submenu "><span class="m-menu__arrow"></span>
                    <ul class="m-menu__subnav">
                        <li class="m-menu__item  m-menu__item--parent" aria-haspopup="true" m-menu-link-redirect="1"><span class="m-menu__link"><span class="m-menu__item-here"></span><span class="m-menu__link-text">Order</span></span></li>
                        @if(Auth::user()->roles != 'investor')
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('order/add') }}" class="m-menu__link "><i class="m-menu__link-icon flaticon-plus"><span></span></i><span class="m-menu__link-text">New Order</span></a></li>
                        @endif
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('order') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Order List</span></a></li>
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('order/pending/list') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Pending Order</span></a></li>
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('order/shipping/label') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Shipping Label</span></a></li>
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('order/airwaybill/edit') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Update Airwaybill</span></a></li>
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('order/issue/list/index') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Order Issue List</span></a></li>
                    </ul>
                </div>
            </li>
            @if(\Auth::user()->roles != 'client')
            <li class="m-menu__item  m-menu__item--submenu @if($page == 'Tools'){{'m-menu__item--active'}}@endif" aria-haspopup="true" m-menu-submenu-toggle="hover" m-menu-link-redirect="1"><a href="javascript:;" class="m-menu__link m-menu__toggle"><span class="m-menu__item-here"></span><i class="m-menu__link-icon flaticon-app"></i><span class="m-menu__link-text">Tools</span><i class="m-menu__ver-arrow la la-angle-right"></i></a>
                <div class="m-menu__submenu "><span class="m-menu__arrow"></span>
                    <ul class="m-menu__subnav">
                        <li class="m-menu__item  m-menu__item--parent" aria-haspopup="true" m-menu-link-redirect="1"><span class="m-menu__link"><span class="m-menu__item-here"></span><span class="m-menu__link-text">Tools</span></span></li>
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('package') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Package</span></a></li>
                    </ul>
                </div>
            </li>
            @endif
            <li class="m-menu__item  m-menu__item--submenu @if($page == 'Report'){{'m-menu__item--active'}}@endif" aria-haspopup="true" m-menu-submenu-toggle="hover" m-menu-link-redirect="1"><a href="javascript:;" class="m-menu__link m-menu__toggle"><span class="m-menu__item-here"></span><i class="m-menu__link-icon flaticon-statistics"></i><span class="m-menu__link-text">Report</span><i class="m-menu__ver-arrow la la-angle-right"></i></a>
                <div class="m-menu__submenu "><span class="m-menu__arrow"></span>
                    <ul class="m-menu__subnav">
                        <li class="m-menu__item  m-menu__item--parent" aria-haspopup="true" m-menu-link-redirect="1"><span class="m-menu__link"><span class="m-menu__item-here"></span><span class="m-menu__link-text">Report</span></span></li>
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('report') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Overview</span></a></li>
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('report/trace') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Trace &amp; Track</span></a></li>
                        @if(\Auth::user()->roles != 'client' && Auth::user()->roles != 'investor' )
                        <!-- <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('report/outbound') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">Outbound Analytics</span></a></li>  -->
                        @endif
                    </ul>
                </div>
            </li>
            @if(\Auth::user()->roles != 'crew')
            <li class="m-menu__item  @if($page == 'User'){{'m-menu__item--active'}}@endif">
                <a  href="{{ url('user') }}" class="m-menu__link">
                    <span class="m-menu__item-here"></span>
                    <i class="m-menu__link-icon flaticon-users"></i>
                    <span class="m-menu__link-text">
                        User
                    </span>
                </a>
            </li>
            @endif
            @if(Auth::user()->roles == 'client')
            <li class="m-menu__item  m-menu__item--submenu @if($page == 'Integration'){{'m-menu__item--active'}}@endif" aria-haspopup="true" m-menu-submenu-toggle="hover" m-menu-link-redirect="1"><a href="javascript:;" class="m-menu__link m-menu__toggle"><span class="m-menu__item-here"></span><i class="m-menu__link-icon la la-plug"></i><span class="m-menu__link-text">Integration</span><i class="m-menu__ver-arrow la la-angle-right"></i></a>
                <div class="m-menu__submenu "><span class="m-menu__arrow"></span>
                    <ul class="m-menu__subnav">
                        <li class="m-menu__item  m-menu__item--parent" aria-haspopup="true" m-menu-link-redirect="1"><span class="m-menu__link"><span class="m-menu__item-here"></span><span class="m-menu__link-text">Integration</span></span></li>
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('integration') }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">List</span></a></li>
                        @foreach(\Config::get('constants.partners') as $key => $opt) 
                        <li class="m-menu__item " aria-haspopup="true" m-menu-link-redirect="1"><a href="{{ url('integration/'.$opt['ID']) }}" class="m-menu__link "><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-text">{{ ucfirst($key) }}</span></a></li>
                        @endforeach
                    </ul>
                </div>
            </li>
            @endif
        </ul>
    </div>
    <!-- END: Aside Menu -->
</div>
<!-- END: Left Aside -->