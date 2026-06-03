@extends('layouts.app')

@section('title', 'Tạo phiếu kiểm kê — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stocktakes.index') }}">Kiểm kê</a></li>
<li class="breadcrumb-item active">Tạo phiếu mới</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-semibold">Tạo phiếu kiểm kê</h4>
        <small class="text-body-secondary">Chọn phạm vi và ngày tiến hành kiểm kê</small>
    </div>
    <a href="{{ route('stocktakes.index') }}" class="btn btn-outline-secondary">
        <svg class="icon me-1">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
        </svg>
        Quay lại
    </a>
</div>

<form method="POST" action="{{ route('stocktakes.store') }}" id="formCreate">
    @csrf

    <div class="row g-4">

        {{-- CỘT TRÁI: Thông tin chung --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header fw-semibold">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use>
                    </svg>
                    Thông tin phiếu
                </div>
                <div class="card-body">

                    {{-- Loại kiểm kê --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Loại kiểm kê <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3 flex-wrap">
                            @foreach([1 => ['Toàn kho','cil-storage','primary'], 2 => ['Theo khu
                            vực','cil-location-pin','info'], 3 => ['Theo mặt hàng','cil-tags','secondary']] as $val =>
                            [$label, $icon, $color])
                            <label class="card flex-row align-items-center gap-2 px-3 py-2 cursor-pointer border
                            @error('check_type') border-danger @enderror" style="min-width:140px; cursor:pointer"
                                id="card_type_{{ $val }}">
                                <input type="radio" name="check_type" value="{{ $val }}"
                                    class="form-check-input mt-0 check-type-radio"
                                    {{ old('check_type', 1) == $val ? 'checked' : '' }}>
                                <svg class="icon text-{{ $color }}">
                                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#'.$icon) }}"></use>
                                </svg>
                                <span class="small fw-medium">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('check_type')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Ngày kiểm kê --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Ngày kiểm kê <span class="text-danger">*</span></label>
                        <input type="date" name="check_date"
                            class="form-control @error('check_date') is-invalid @enderror"
                            value="{{ old('check_date', now()->toDateString()) }}">
                        @error('check_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Người phụ trách --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Người phụ trách</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">— Không chỉ định —</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Ghi chú --}}
                    <div class="mb-0">
                        <label class="form-label fw-medium">Ghi chú</label>
                        <textarea name="note" class="form-control" rows="3"
                            placeholder="Lý do / nội dung kiểm kê...">{{ old('note') }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        {{-- CỘT PHẢI: Phạm vi kiểm kê --}}
        <div class="col-lg-7">

            {{-- Toàn kho --}}
            <div id="scope_1" class="scope-panel">
                <div class="card border-primary">
                    <div class="card-body text-center py-5 text-primary">
                        <svg class="icon icon-3xl mb-2">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
                        </svg>
                        <div class="fw-semibold">Kiểm kê toàn bộ kho</div>
                        <div class="text-body-secondary small mt-1">Tất cả vị trí và mặt hàng sẽ được đưa vào phiếu kiểm
                            kê.</div>
                    </div>
                </div>
            </div>

            {{-- Theo khu vực --}}
            <div id="scope_2" class="scope-panel d-none">
                <div class="card">
                    <div class="card-header fw-semibold">
                        <svg class="icon me-1 text-info">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-location-pin') }}">
                            </use>
                        </svg>
                        Chọn vị trí kho
                        @error('location_ids')
                        <span class="text-danger small ms-2">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="card-body p-0" style="max-height:380px; overflow-y:auto">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width:36px">
                                        <input type="checkbox" class="form-check-input" id="checkAllLoc">
                                    </th>
                                    <th>Mã vị trí</th>
                                    <th>Tên vị trí</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($locations as $loc)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input loc-check" name="location_ids[]"
                                            value="{{ $loc->id }}"
                                            {{ in_array($loc->id, old('location_ids', [])) ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-body-secondary small font-monospace">{{ $loc->code }}</td>
                                    <td>{{ $loc->name }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Theo mặt hàng --}}
            <div id="scope_3" class="scope-panel d-none">
                <div class="card">
                    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                        <span>
                            <svg class="icon me-1 text-secondary">
                                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-tags') }}"></use>
                            </svg>
                            Chọn mặt hàng
                            @error('product_ids')
                            <span class="text-danger small ms-2">{{ $message }}</span>
                            @enderror
                        </span>
                        <input type="text" class="form-control form-control-sm" id="searchProduct"
                            placeholder="Tìm mã / tên..." style="width:200px">
                    </div>
                    <div class="card-body p-0" style="max-height:380px; overflow-y:auto">
                        <table class="table table-sm table-hover mb-0" id="tblProducts">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width:36px">
                                        <input type="checkbox" class="form-check-input" id="checkAllProd">
                                    </th>
                                    <th>Mã SP</th>
                                    <th>Tên sản phẩm</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $prod)
                                <tr data-search="{{ strtolower($prod->code . ' ' . $prod->name) }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input prod-check" name="product_ids[]"
                                            value="{{ $prod->id }}"
                                            {{ in_array($prod->id, old('product_ids', [])) ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-body-secondary small font-monospace">{{ $prod->code }}</td>
                                    <td>{{ $prod->name }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- NÚT SUBMIT --}}
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('stocktakes.index') }}" class="btn btn-outline-secondary">Hủy</a>
        <button type="submit" class="btn btn-primary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use>
            </svg>
            Tạo phiếu (Nháp)
        </button>
    </div>

</form>

@endsection

@push('scripts')
<script>
(function() {
    // Hiển thị/ẩn scope panel theo check_type
    function switchScope(val) {
        document.querySelectorAll('.scope-panel').forEach(el => el.classList.add('d-none'));
        const panel = document.getElementById('scope_' + val);
        if (panel) panel.classList.remove('d-none');

        // Highlight card radio
        document.querySelectorAll('[id^="card_type_"]').forEach(el => {
            el.classList.remove('border-primary', 'border-info', 'border-secondary', 'bg-primary-subtle',
                'bg-info-subtle', 'bg-secondary-subtle');
        });
        const colorMap = {
            1: 'primary',
            2: 'info',
            3: 'secondary'
        };
        const c = colorMap[val];
        const card = document.getElementById('card_type_' + val);
        if (card && c) card.classList.add('border-' + c, 'bg-' + c + '-subtle');
    }

    document.querySelectorAll('.check-type-radio').forEach(radio => {
        radio.addEventListener('change', e => switchScope(e.target.value));
    });

    // Init on page load
    const checked = document.querySelector('.check-type-radio:checked');
    if (checked) switchScope(checked.value);

    // Check-all location
    document.getElementById('checkAllLoc')?.addEventListener('change', function() {
        document.querySelectorAll('.loc-check').forEach(c => c.checked = this.checked);
    });

    // Check-all product
    document.getElementById('checkAllProd')?.addEventListener('change', function() {
        document.querySelectorAll('.prod-check').forEach(c => c.checked = this.checked);
    });

    // Search product
    document.getElementById('searchProduct')?.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#tblProducts tbody tr').forEach(tr => {
            tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
        });
    });
})();
</script>
@endpush