<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
  <div class="sidebar-header border-bottom">
    <div class="sidebar-brand">
      {{-- Thay logo CoreUI bằng tên hệ thống --}}
      <span class="sidebar-brand-full fw-semibold fs-5">Warehouse System</span>
      <span class="sidebar-brand-narrow fw-bold">WS</span>
    </div>
    <button class="btn-close d-lg-none" type="button"
      data-coreui-theme="dark"
      aria-label="Close"
      onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
    </button>
  </div>

  <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>

    {{-- TỔNG QUAN --}}
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
         href="{{ route('dashboard') }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-speedometer') }}"></use>
        </svg>
        Dashboard
      </a>
    </li>

    {{-- NGHIỆP VỤ KHO --}}
    <li class="nav-title">Nghiệp vụ kho</li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}"
         href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use>
        </svg>
        Nhập kho
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('deliveries.*') ? 'active' : '' }}"
         href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-top') }}"></use>
        </svg>
        Xuất kho
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('transfers.*') ? 'active' : '' }}"
         href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-transfer') }}"></use>
        </svg>
        Chuyển kho
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('stocktakes.*') ? 'active' : '' }}"
         href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clipboard') }}"></use>
        </svg>
        Kiểm kê
      </a>
    </li>

    {{-- TỒN KHO --}}
    <li class="nav-title">Tồn kho</li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}"
         href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
        </svg>
        Tồn kho hiện tại
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}"
         href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-map') }}"></use>
        </svg>
        Vị trí kho
      </a>
    </li>

    {{-- MASTER DATA --}}
    <li class="nav-title">Danh mục</li>

    <li class="nav-group {{ request()->routeIs('products.*', 'categories.*', 'units.*') ? 'show' : '' }}">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-tags') }}"></use>
        </svg>
        Hàng hóa
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"
             href="{{ route('master.product.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Danh sách hàng hóa
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"
             href="{{ route('master.category.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Danh mục
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('units.*') ? 'active' : '' }}"
             href="{{ route('master.uom.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Đơn vị tính
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.uom_conversion.*') ? 'active' : '' }}"
            href="{{ route('master.uom_conversion.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Quy đổi đơn vị
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.bom.*') ? 'active' : '' }}"
            href="{{ route('master.bom.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Công thức BOM
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.putaway_rule.*') ? 'active' : '' }}"
            href="{{ route('master.putaway_rule.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Putaway Rules
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.reorder_rule.*') ? 'active' : '' }}"
            href="{{ route('master.reorder_rule.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Reorder Rules
            {{-- Badge cảnh báo số lượng dưới ngưỡng — render từ controller hoặc view composer --}}
            {{-- @if(($belowReorderCount ?? 0) > 0)
              <span class="badge bg-danger ms-auto">{{ $belowReorderCount }}</span>
            @endif --}}
          </a>
        </li>
      </ul>
    </li>

    <li class="nav-group {{ request()->routeIs('suppliers.*', 'warehouses.*') ? 'show' : '' }}">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-building') }}"></use>
        </svg>
        Đối tác & Kho
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"
             href="{{ route('master.supplier.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Nhà cung cấp
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}"
             href="{{ route('master.location.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Kho hàng
          </a>
        </li>
      </ul>
    </li>

    {{-- BÁO CÁO --}}
    <li class="nav-title">Báo cáo</li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
         href="{{ route('reports.index') }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-chart-pie') }}"></use>
        </svg>
        Báo cáo tổng hợp
      </a>
    </li>

    {{-- HỆ THỐNG --}}
{{-- @role('admin') --}}
    <li class="nav-divider"></li>
    <li class="nav-title">Hệ thống</li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
         href="{{ route('master.employee.index') }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-people') }}"></use>
        </svg>
        Quản lý người dùng
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('activity-log.*') ? 'active' : '' }}"
         href="{{ route('activity-log.index') }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
        </svg>
        Nhật ký hệ thống
      </a>
    </li>
{{-- @endrole --}}

  </ul>

  <div class="sidebar-footer border-top d-none d-md-flex">
    <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
  </div>
</div>
