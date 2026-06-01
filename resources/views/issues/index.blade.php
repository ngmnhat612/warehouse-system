@extends('layouts.app')

@section('title', 'Xuất kho — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item active">Xuất kho</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Xuất kho</h4>
      <small class="text-body-secondary">Quản lý phiếu xuất hàng hóa khỏi kho</small>
    </div>
    <a href="{{ route('issues.create') }}" class="btn btn-primary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Tạo phiếu xuất
    </a>
  </div>

  {{-- CARDS THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-top') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount ?? 0 }}</div>
            <div class="text-body-secondary small">Tổng phiếu xuất</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-warning">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-warning"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clock') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $pendingCount ?? 0 }}</div>
            <div class="text-body-secondary small">Chờ duyệt</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $completedCount ?? 0 }}</div>
            <div class="text-body-secondary small">Hoàn thành</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-danger">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-danger"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $cancelledCount ?? 0 }}</div>
            <div class="text-body-secondary small">Đã hủy</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BẢNG DANH SÁCH --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách phiếu xuất</span>
      <form method="GET" action="{{ route('issues.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:230px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Mã phiếu, số tham chiếu...">
        </div>
        <select class="form-select" name="issue_type" style="width:150px">
          <option value="">Tất cả loại</option>
          <option value="1" {{ request('issue_type') == '1' ? 'selected' : '' }}>Sản xuất</option>
          <option value="2" {{ request('issue_type') == '2' ? 'selected' : '' }}>Bảo trì</option>
          <option value="3" {{ request('issue_type') == '3' ? 'selected' : '' }}>Mượn</option>
          <option value="4" {{ request('issue_type') == '4' ? 'selected' : '' }}>Khác</option>
        </select>
        <select class="form-select" name="status" style="width:140px">
          <option value="">Tất cả trạng thái</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Draft</option>
          <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Chờ duyệt</option>
          <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Đã duyệt</option>
          <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>Hoàn thành</option>
          <option value="5" {{ request('status') == '5' ? 'selected' : '' }}>Đã hủy</option>
        </select>
        <input type="date" class="form-control" name="date_from"
               value="{{ request('date_from') }}" style="width:145px" title="Từ ngày">
        <input type="date" class="form-control" name="date_to"
               value="{{ request('date_to') }}" style="width:145px" title="Đến ngày">
        <button type="submit" class="btn btn-outline-primary">Lọc</button>
        @if(request('search') || request('issue_type') || request('status') || request('date_from') || request('date_to'))
          <a href="{{ route('issues.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:50px">#</th>
              <th style="width:130px">Mã phiếu</th>
              <th style="width:120px">Loại xuất</th>
              <th>Người yêu cầu</th>
              <th style="width:130px">Số tham chiếu</th>
              <th style="width:105px">Ngày xuất</th>
              <th style="width:115px">Hạn trả</th>
              <th class="text-center" style="width:80px">Số dòng</th>
              <th class="text-center" style="width:120px">Trạng thái</th>
              <th style="width:120px">Người tạo</th>
              <th class="text-center" style="width:100px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($issues as $index => $issue)
              @php
                $statusMap = [
                  1 => ['label' => 'Draft',      'color' => 'secondary'],
                  2 => ['label' => 'Chờ duyệt',  'color' => 'warning'],
                  3 => ['label' => 'Đã duyệt',   'color' => 'info'],
                  4 => ['label' => 'Hoàn thành', 'color' => 'success'],
                  5 => ['label' => 'Đã hủy',     'color' => 'danger'],
                ];
                $typeMap = [
                  1 => ['label' => 'Sản xuất', 'color' => 'primary'],
                  2 => ['label' => 'Bảo trì',  'color' => 'warning'],
                  3 => ['label' => 'Mượn',     'color' => 'info'],
                  4 => ['label' => 'Khác',     'color' => 'secondary'],
                ];
                $st = $statusMap[$issue->status] ?? ['label' => '—', 'color' => 'secondary'];
                $tp = $typeMap[$issue->issue_type] ?? ['label' => '—', 'color' => 'secondary'];
              @endphp
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($issues->currentPage() - 1) * $issues->perPage() + $index + 1 }}
                </td>
                <td>
                  <a href="{{ route('issues.show', $issue->id) }}"
                     class="fw-medium text-primary text-decoration-none">
                    {{ $issue->code }}
                  </a>
                </td>
                <td>
                  <span class="badge bg-{{ $tp['color'] }}-subtle text-{{ $tp['color'] }} border border-{{ $tp['color'] }}-subtle" style="font-size:11px">
                    {{ $tp['label'] }}
                  </span>
                </td>
                <td class="small">{{ $issue->requester?->name ?? '—' }}</td>
                <td class="small text-body-secondary">{{ $issue->reference_no ?? '—' }}</td>
                <td class="small">{{ $issue->issue_date ? \Carbon\Carbon::parse($issue->issue_date)->format('d/m/Y') : '—' }}</td>
                <td class="small">
                  @if($issue->expected_return_date)
                    @php $isOverdue = \Carbon\Carbon::parse($issue->expected_return_date)->isPast() && $issue->status != 4 @endphp
                    <span class="{{ $isOverdue ? 'text-danger fw-semibold' : 'text-body-secondary' }}">
                      {{ \Carbon\Carbon::parse($issue->expected_return_date)->format('d/m/Y') }}
                      @if($isOverdue) <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg> @endif
                    </span>
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="badge bg-light text-dark border">{{ $issue->details_count ?? 0 }}</span>
                </td>
                <td class="text-center">
                  <span class="badge bg-{{ $st['color'] }}-subtle text-{{ $st['color'] }} border border-{{ $st['color'] }}-subtle" style="font-size:11px">
                    {{ $st['label'] }}
                  </span>
                </td>
                <td class="small text-body-secondary">{{ $issue->creator?->name ?? '—' }}</td>
                <td class="text-center">
                  <a href="{{ route('issues.show', $issue->id) }}"
                     class="btn btn-sm btn-outline-secondary me-1" title="Xem chi tiết">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
                  </a>
                  @if($issue->status == 1)
                    <a href="{{ route('issues.edit', $issue->id) }}"
                       class="btn btn-sm btn-outline-primary me-1" title="Chỉnh sửa">
                      <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                    </a>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete({{ $issue->id }}, '{{ $issue->code }}')"
                            title="Xóa">
                      <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                    </button>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-top') }}"></use>
                  </svg>
                  Chưa có phiếu xuất nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($issues->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $issues->firstItem() }}–{{ $issues->lastItem() }}
          trong tổng số {{ $issues->total() }} phiếu
        </small>
        {{ $issues->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>

  {{-- MODAL XÁC NHẬN XÓA --}}
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title text-danger">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
            Xác nhận xóa
          </h5>
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Bạn có chắc muốn xóa phiếu xuất <strong id="deleteCode"></strong>?
          <div class="small text-body-secondary mt-1">Thao tác này không thể hoàn tác.</div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
          <form id="deleteForm" method="POST">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Xóa phiếu</button>
          </form>
        </div>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
function confirmDelete(id, code) {
  document.getElementById('deleteCode').textContent = code;
  document.getElementById('deleteForm').action = `/issues/${id}`;
  new coreui.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endpush