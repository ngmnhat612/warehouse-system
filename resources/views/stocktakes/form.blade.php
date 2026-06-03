@extends('layouts.app')

@section('title', 'Tạo phiếu kiểm kê')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.index') }}">Kiểm kê kho</a></li>
  <li class="breadcrumb-item active">Tạo phiếu kiểm kê</li>
@endsection

@section('content')

<div class="row justify-content-center">
  <div class="col-lg-8">

    <div class="d-flex align-items-center gap-3 mb-4">
      <a href="{{ route('stocktakes.index') }}" class="btn btn-outline-secondary btn-sm">
        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use></svg>
      </a>
      <div>
        <h4 class="mb-0 fw-semibold">Tạo phiếu kiểm kê</h4>
        <small class="text-body-secondary">Điền thông tin và chọn phạm vi kiểm kê</small>
      </div>
    </div>

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible mb-4">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
        <strong>Vui lòng kiểm tra lại:</strong>
        <ul class="mb-0 mt-1 ps-3">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
      </div>
    @endif

    <form method="POST" action="{{ route('stocktakes.store') }}" id="stocktakeForm">
      @csrf

      {{-- THÔNG TIN CHUNG --}}
      <div class="card mb-4">
        <div class="card-header fw-semibold">Thông tin chung</div>
        <div class="card-body">
          <div class="row g-3">

            {{-- Loại kiểm kê --}}
            <div class="col-12">
              <label class="form-label fw-semibold">
                Loại kiểm kê <span class="text-danger">*</span>
              </label>
              <div class="d-flex gap-3 flex-wrap">
                @foreach([1 => ['Toàn kho', 'primary', 'cil-storage'], 2 => ['Theo khu vực', 'info', 'cil-location-pin'], 3 => ['Theo mặt hàng', 'secondary', 'cil-tags']] as $val => [$label, $color, $icon])
                <div class="flex-fill" style="min-width:160px">
                  <input type="radio" class="btn-check" name="check_type" id="type_{{ $val }}"
                         value="{{ $val }}" {{ old('check_type', 1) == $val ? 'checked' : '' }}>
                  <label class="btn btn-outline-{{ $color }} w-100 d-flex align-items-center gap-2 justify-content-center"
                         for="type_{{ $val }}">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $icon) }}"></use></svg>
                    {{ $label }}
                  </label>
                </div>
                @endforeach
              </div>
              @error('check_type')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- Ngày kiểm --}}
            <div class="col-sm-6">
              <label class="form-label" for="check_date">
                Ngày kiểm kê <span class="text-danger">*</span>
              </label>
              <input type="date" class="form-control @error('check_date') is-invalid @enderror"
                     id="check_date" name="check_date"
                     value="{{ old('check_date', now()->toDateString()) }}">
              @error('check_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Người phụ trách --}}
            <div class="col-sm-6">
              <label class="form-label" for="assigned_to">Người phụ trách</label>
              <select class="form-select @error('assigned_to') is-invalid @enderror"
                      id="assigned_to" name="assigned_to">
                <option value="">— Chọn nhân viên —</option>
                @foreach($users as $user)
                  <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                  </option>
                @endforeach
              </select>
              @error('assigned_to')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Ghi chú --}}
            <div class="col-12">
              <label class="form-label" for="note">Ghi chú</label>
              <textarea class="form-control" id="note" name="note"
                        rows="2" placeholder="Ghi chú thêm...">{{ old('note') }}</textarea>
            </div>

          </div>
        </div>
      </div>

      {{-- PHẠM VI: THEO KHU VỰC --}}
      <div class="card mb-4 scope-card" id="scope-location" style="display:none">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
          <span>Chọn khu vực / vị trí kiểm kê</span>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllLocations()">Chọn tất cả</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearLocations()">Bỏ chọn</button>
          </div>
        </div>
        <div class="card-body">
          @error('location_ids')
            <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
          @enderror
          <div class="row g-2" style="max-height:300px; overflow-y:auto">
            @foreach($locations as $loc)
            <div class="col-sm-6 col-lg-4">
              <div class="form-check">
                <input class="form-check-input location-cb" type="checkbox"
                       name="location_ids[]" value="{{ $loc->id }}"
                       id="loc_{{ $loc->id }}"
                       {{ in_array($loc->id, old('location_ids', [])) ? 'checked' : '' }}>
                <label class="form-check-label small" for="loc_{{ $loc->id }}">
                  <span class="fw-semibold">{{ $loc->code }}</span>
                  @if($loc->name !== $loc->code)
                    <span class="text-body-secondary">— {{ $loc->name }}</span>
                  @endif
                </label>
              </div>
            </div>
            @endforeach
            @if($locations->isEmpty())
              <div class="col-12 text-body-secondary small">Không có vị trí nào.</div>
            @endif
          </div>
        </div>
      </div>

      {{-- PHẠM VI: THEO MẶT HÀNG --}}
      <div class="card mb-4 scope-card" id="scope-product" style="display:none">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
          <span>Chọn mặt hàng kiểm kê</span>
          <div class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" id="productSearch"
                   placeholder="Tìm mặt hàng..." style="width:180px">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllProducts()">Chọn tất cả</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearProducts()">Bỏ chọn</button>
          </div>
        </div>
        <div class="card-body">
          @error('product_ids')
            <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
          @enderror
          <div class="row g-2" id="productList" style="max-height:300px; overflow-y:auto">
            @foreach($products as $prod)
            <div class="col-sm-6 col-lg-4 product-item">
              <div class="form-check">
                <input class="form-check-input product-cb" type="checkbox"
                       name="product_ids[]" value="{{ $prod->id }}"
                       id="prod_{{ $prod->id }}"
                       {{ in_array($prod->id, old('product_ids', [])) ? 'checked' : '' }}>
                <label class="form-check-label small" for="prod_{{ $prod->id }}">
                  <span class="fw-semibold">{{ $prod->code }}</span>
                  <span class="text-body-secondary d-block" style="font-size:11px">{{ $prod->name }}</span>
                </label>
              </div>
            </div>
            @endforeach
            @if($products->isEmpty())
              <div class="col-12 text-body-secondary small">Không có mặt hàng nào.</div>
            @endif
          </div>
        </div>
      </div>

      {{-- ACTIONS --}}
      <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('stocktakes.index') }}" class="btn btn-outline-secondary">Hủy</a>
        <button type="submit" class="btn btn-primary">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
          Tạo phiếu kiểm kê
        </button>
      </div>

    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
  // Hiện/ẩn scope card theo check_type
  function updateScopeVisibility() {
    const val = document.querySelector('input[name="check_type"]:checked')?.value;
    document.getElementById('scope-location').style.display = val == 2 ? '' : 'none';
    document.getElementById('scope-product').style.display  = val == 3 ? '' : 'none';
  }

  document.querySelectorAll('input[name="check_type"]').forEach(el => {
    el.addEventListener('change', updateScopeVisibility);
  });
  updateScopeVisibility();

  // Chọn/bỏ chọn tất cả vị trí
  function selectAllLocations() {
    document.querySelectorAll('.location-cb').forEach(cb => cb.checked = true);
  }
  function clearLocations() {
    document.querySelectorAll('.location-cb').forEach(cb => cb.checked = false);
  }

  // Chọn/bỏ chọn tất cả mặt hàng
  function selectAllProducts() {
    document.querySelectorAll('.product-cb:not([style*="display:none"])').forEach(cb => cb.checked = true);
  }
  function clearProducts() {
    document.querySelectorAll('.product-cb').forEach(cb => cb.checked = false);
  }

  // Tìm kiếm mặt hàng
  document.getElementById('productSearch')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.product-item').forEach(item => {
      const text = item.textContent.toLowerCase();
      item.style.display = text.includes(q) ? '' : 'none';
    });
  });
</script>
@endpush