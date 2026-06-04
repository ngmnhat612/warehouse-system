@extends('layouts.app')
@section('title', (isset($scrap) ? 'Sửa phiếu hủy' : 'Tạo phiếu hủy hàng') . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('scraps.index') }}">Hủy hàng</a></li>
<li class="breadcrumb-item active">{{ isset($scrap) ? 'Chỉnh sửa' : 'Tạo mới' }}</li>
@endsection

@section('content')

@php
$isEdit = isset($scrap);
$action = $isEdit ? route('scraps.update', $scrap) : route('scraps.store');
@endphp

<form method="POST" action="{{ $action }}" id="scrapForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">
            <svg class="icon me-1 text-danger">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use>
            </svg>
            {{ $isEdit ? 'Chỉnh sửa phiếu '.$scrap->code : 'Tạo phiếu hủy hàng mới' }}
        </h4>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use>
                </svg>
                Lưu phiếu
            </button>
            <a href="{{ route('scraps.index') }}" class="btn btn-outline-secondary">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
                </svg>
                Quay lại
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible mb-4">
        <strong>Vui lòng kiểm tra lại:</strong>
        <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-header fw-semibold">
            <svg class="icon me-1 text-primary">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use>
            </svg>
            Thông tin phiếu
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Mã phiếu <small class="text-body-secondary">(tự sinh nếu bỏ
                            trống)</small></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                        value="{{ old('code', $isEdit ? $scrap->code : '') }}" placeholder="VD: HH-202506-0001">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ngày hủy <span class="text-danger">*</span></label>
                    <input type="date" name="scrap_date" class="form-control @error('scrap_date') is-invalid @enderror"
                        value="{{ old('scrap_date', $isEdit ? $scrap->scrap_date?->format('Y-m-d') : now()->format('Y-m-d')) }}"
                        required>
                    @error('scrap_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ghi chú</label>
                    <input type="text" name="note" class="form-control"
                        value="{{ old('note', $isEdit ? $scrap->note : '') }}" placeholder="Lý do hủy hàng...">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
            <span>
                <svg class="icon me-1 text-primary">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                </svg>
                Danh sách hàng hóa cần hủy
            </span>
            <button type="button" class="btn btn-outline-danger btn-sm" id="btnAddRow">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
                </svg>
                Thêm dòng
            </button>
        </div>
        <div class="card-body p-0">
            @error('details')<div class="alert alert-danger m-3 mb-0">{{ $message }}</div>@enderror
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="detailsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:200px">Hàng hóa <span class="text-danger">*</span></th>
                            <th style="min-width:160px">Vị trí kho <span class="text-danger">*</span></th>
                            <th style="min-width:100px">Số lượng <span class="text-danger">*</span></th>
                            <th style="min-width:90px">ĐVT</th>
                            <th style="min-width:120px">Lô hàng</th>
                            <th style="min-width:180px">Lý do hủy</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="detailsBody">
                        @if($isEdit && $scrap->details->count())
                        @foreach($scrap->details as $i => $d)
                        <tr class="detail-row">
                            <td>
                                <select name="details[{{ $i }}][product_id]" class="form-select form-select-sm"
                                    required>
                                    <option value="">— Chọn SP —</option>
                                    @foreach($products as $p)
                                    <option value="{{ $p->id }}" @selected($d->product_id == $p->id)>{{ $p->code }} —
                                        {{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="details[{{ $i }}][location_id]" class="form-select form-select-sm"
                                    required>
                                    <option value="">— Vị trí —</option>
                                    @foreach($locations as $l)
                                    <option value="{{ $l->id }}" @selected($d->location_id == $l->id)>{{ $l->code }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" name="details[{{ $i }}][quantity]"
                                    class="form-control form-control-sm" value="{{ $d->quantity }}" min="0.001"
                                    step="0.001" required></td>
                            <td>
                                <select name="details[{{ $i }}][uom_id]" class="form-select form-select-sm">
                                    @if($d->uom)
                                    <option value="{{ $d->uom->id }}" selected>{{ $d->uom->name }}</option>
                                    @endif
                                </select>
                            </td>
                            <td>
                                <select name="details[{{ $i }}][lot_id]" class="form-select form-select-sm">
                                    <option value="">— Không —</option>
                                    @foreach($lots->get($d->product_id, collect()) as $lot)
                                    <option value="{{ $lot->id }}" @selected($d->lot_id ==
                                        $lot->id)>{{ $lot->lot_number }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="text" name="details[{{ $i }}][reason]" class="form-control form-control-sm"
                                    value="{{ $d->reason }}" placeholder="Hư hỏng, hết hạn..."></td>
                            <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-row"><svg
                                        class="icon">
                                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}">
                                        </use>
                                    </svg></button></td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</form>

<template id="rowTemplate">
    <tr class="detail-row">
        <td>
            <select name="details[__IDX__][product_id]" class="form-select form-select-sm product-select" required>
                <option value="">— Chọn sản phẩm —</option>
                @foreach($products as $p)
                <option value="{{ $p->id }}" data-uom-id="{{ $p->uom_id }}" data-uom-name="{{ $p->uom?->name }}">
                    {{ $p->code }} — {{ $p->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="details[__IDX__][location_id]" class="form-select form-select-sm" required>
                <option value="">— Vị trí kho —</option>
                @foreach($locations as $l)
                <option value="{{ $l->id }}">{{ $l->code }} @if($l->name) — {{ $l->name }} @endif</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="details[__IDX__][quantity]" class="form-control form-control-sm" min="0.001"
                step="0.001" placeholder="0.000" required></td>
        <td>
            <select name="details[__IDX__][uom_id]" class="form-select form-select-sm uom-select">
                <option value="">—</option>
            </select>
        </td>
        <td>
            <select name="details[__IDX__][lot_id]" class="form-select form-select-sm lot-select">
                <option value="">— Không —</option>
            </select>
        </td>
        <td><input type="text" name="details[__IDX__][reason]" class="form-control form-control-sm"
                placeholder="Hư hỏng, hết hạn..."></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-row"><svg class="icon">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use>
                </svg></button></td>
    </tr>
</template>

@push('scripts')
<script>
const products = @json($productsJson - > keyBy('id'));
const lotsMap = @json($lots - > map(fn($g) => $g - > values()));
let rowIdx = <?php echo $isEdit ? $scrap->details->count() : 0; ?>;

document.getElementById('btnAddRow').addEventListener('click', () => {
    const tpl = document.getElementById('rowTemplate').innerHTML.replaceAll('__IDX__', rowIdx++);
    const tbody = document.getElementById('detailsBody');
    tbody.insertAdjacentHTML('beforeend', tpl);
    const row = tbody.lastElementChild;
    row.querySelector('.btn-remove-row').addEventListener('click', () => row.remove());
    row.querySelector('.product-select').addEventListener('change', function() {
        onProductChange(this);
    });
});

document.querySelectorAll('.btn-remove-row').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('tr').remove());
});

function onProductChange(sel) {
    const row = sel.closest('tr');
    const p = products[sel.value];
    const uomSel = row.querySelector('.uom-select');
    const lotSel = row.querySelector('.lot-select');
    if (!uomSel || !lotSel) return;
    uomSel.innerHTML = p ? `<option value="${p.uom_id}">${p.uom ?? p.uom_id}</option>` : '<option value="">—</option>';
    const lots = lotsMap[sel.value] ?? [];
    lotSel.innerHTML = '<option value="">— Không —</option>' +
        lots.map(l =>
            `<option value="${l.id}">${l.lot_number}${l.expiry_date ? ' (HSD: '+l.expiry_date+')' : ''}</option>`).join(
            '');
}
</script>
@endpush

@endsection