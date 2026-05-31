@extends('layouts.app')

@section('title', 'Nhật ký hệ thống — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Hệ thống</li>
  <li class="breadcrumb-item active">Nhật ký hệ thống</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Nhật ký hệ thống</h4>
      <small class="text-body-secondary">Lịch sử hoạt động và thay đổi dữ liệu trong hệ thống</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-success" onclick="exportLog()">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-spreadsheet') }}"></use></svg>
        Xuất Excel
      </button>
    </div>
  </div>

  {{-- CARDS TÓM TẮT --}}
  <div class="row g-3 mb-4">

    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary flex-shrink-0">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
          </svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($totalToday ?? 0) }}</div>
            <div class="text-body-secondary small">Hoạt động hôm nay</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success flex-shrink-0">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use>
          </svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($totalUpdated ?? 0) }}</div>
            <div class="text-body-secondary small">Cập nhật dữ liệu</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-info">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-info flex-shrink-0">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-people') }}"></use>
          </svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($activeUsersToday ?? 0) }}</div>
            <div class="text-body-secondary small">Người dùng hoạt động</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-warning">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-warning flex-shrink-0">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use>
          </svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($totalDeleted ?? 0) }}</div>
            <div class="text-body-secondary small">Xóa / Hủy hôm nay</div>
          </div>
        </div>
      </div>
    </div>

  </div>
  {{-- /.cards --}}

  {{-- BỘ LỌC --}}
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('activity-log.index') }}" id="logFilterForm">
        <div class="row g-3 align-items-end">

          <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-medium">Từ ngày</label>
            <input type="date" class="form-control" name="date_from"
                   value="{{ request('date_from', now()->toDateString()) }}">
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-medium">Đến ngày</label>
            <input type="date" class="form-control" name="date_to"
                   value="{{ request('date_to', now()->toDateString()) }}">
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-medium">Người thực hiện</label>
            <select class="form-select" name="causer_id">
              <option value="">Tất cả</option>
              @foreach ($users ?? [] as $user)
                <option value="{{ $user->id }}" {{ request('causer_id') == $user->id ? 'selected' : '' }}>
                  {{ $user->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-medium">Loại đối tượng</label>
            <select class="form-select" name="subject_type">
              <option value="">Tất cả</option>
              @foreach ($subjectTypes ?? [] as $type => $label)
                <option value="{{ $type }}" {{ request('subject_type') == $type ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-medium">Hành động</label>
            <select class="form-select" name="event">
              <option value="">Tất cả</option>
              <option value="created"  {{ request('event') == 'created'  ? 'selected' : '' }}>Tạo mới</option>
              <option value="updated"  {{ request('event') == 'updated'  ? 'selected' : '' }}>Cập nhật</option>
              <option value="deleted"  {{ request('event') == 'deleted'  ? 'selected' : '' }}>Xóa</option>
              <option value="approved" {{ request('event') == 'approved' ? 'selected' : '' }}>Duyệt</option>
              <option value="cancelled"{{ request('event') == 'cancelled'? 'selected' : '' }}>Hủy</option>
            </select>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-medium">Tìm kiếm</label>
            <input type="text" class="form-control" name="search"
                   value="{{ request('search') }}" placeholder="Mô tả, mã phiếu...">
          </div>

          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
              Tìm kiếm
            </button>
            <a href="{{ route('activity-log.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
          </div>

        </div>
      </form>
    </div>
  </div>

  {{-- BẢNG NHẬT KÝ --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Danh sách hoạt động</span>
      <small class="text-body-secondary">
        @if (isset($activities) && method_exists($activities, 'total'))
          Tổng: <strong>{{ number_format($activities->total()) }}</strong> bản ghi
        @endif
      </small>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:55px">#</th>
              <th style="width:150px">Thời gian</th>
              <th style="width:160px">Người thực hiện</th>
              <th style="width:100px" class="text-center">Hành động</th>
              <th style="width:140px">Đối tượng</th>
              <th>Mô tả</th>
              <th style="width:80px" class="text-center">Chi tiết</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($activities ?? [] as $index => $log)
              @php
                $eventColors = [
                  'created'   => 'success',
                  'updated'   => 'primary',
                  'deleted'   => 'danger',
                  'approved'  => 'info',
                  'cancelled' => 'warning',
                  'completed' => 'success',
                ];
                $eventLabels = [
                  'created'   => 'Tạo mới',
                  'updated'   => 'Cập nhật',
                  'deleted'   => 'Xóa',
                  'approved'  => 'Duyệt',
                  'cancelled' => 'Hủy',
                  'completed' => 'Hoàn thành',
                ];
                $color = $eventColors[$log->event] ?? 'secondary';
                $label = $eventLabels[$log->event] ?? ucfirst($log->event);
                $subjectParts = $log->subject_type ? explode('\\', $log->subject_type) : [];
                $subjectShort = end($subjectParts) ?: '—';
              @endphp
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($activities->currentPage() - 1) * $activities->perPage() + $index + 1 }}
                </td>
                <td>
                  <div class="small fw-medium">
                    {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y') }}
                  </div>
                  <div class="text-body-secondary" style="font-size:11px">
                    {{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}
                  </div>
                </td>
                <td>
                  @if ($log->causer)
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                           style="width:30px;height:30px;font-size:12px;font-weight:600;">
                        {{ strtoupper(substr($log->causer->name ?? '?', 0, 1)) }}
                      </div>
                      <div>
                        <div class="small fw-medium">{{ $log->causer->name ?? '—' }}</div>
                        <div class="text-body-secondary" style="font-size:11px">{{ $log->causer->email ?? '' }}</div>
                      </div>
                    </div>
                  @else
                    <span class="text-body-secondary small">Hệ thống</span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="badge bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }}-subtle"
                        style="font-size:11px;">
                    {{ $label }}
                  </span>
                </td>
                <td>
                  <div class="small fw-medium">{{ $subjectShort }}</div>
                  @if ($log->subject_id)
                    <div class="text-body-secondary" style="font-size:11px">#{{ $log->subject_id }}</div>
                  @endif
                </td>
                <td>
                  <div class="small">{{ Str::limit($log->description, 80) }}</div>
                  {{-- Hiển thị tên phiếu nếu có trong properties --}}
                  @if (!empty($log->properties['attributes']['code'] ?? $log->properties['code'] ?? null))
                    <div class="text-body-secondary" style="font-size:11px">
                      <code>{{ $log->properties['attributes']['code'] ?? $log->properties['code'] }}</code>
                    </div>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-secondary"
                          onclick="showDetail({{ $log->id }})"
                          data-properties="{{ htmlspecialchars(json_encode($log->properties ?? []), ENT_QUOTES) }}"
                          title="Xem chi tiết thay đổi">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                  </svg>
                  Không có bản ghi nào trong khoảng thời gian đã chọn
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if (isset($activities) && method_exists($activities, 'hasPages') && $activities->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $activities->firstItem() }}–{{ $activities->lastItem() }}
          trong tổng số {{ $activities->total() }} bản ghi
        </small>
        {{ $activities->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>
  {{-- /.card table --}}

  {{-- MODAL CHI TIẾT THAY ĐỔI --}}
  <div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Chi tiết thay đổi</h5>
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <ul class="nav nav-tabs mb-3" id="detailTabs">
            <li class="nav-item">
              <a class="nav-link active" data-coreui-toggle="tab" href="#tabOld">Giá trị cũ</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-coreui-toggle="tab" href="#tabNew">Giá trị mới</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-coreui-toggle="tab" href="#tabDiff">So sánh thay đổi</a>
            </li>
          </ul>

          <div class="tab-content">

            {{-- Tab: giá trị cũ --}}
            <div class="tab-pane fade show active" id="tabOld">
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" id="tblOld">
                  <thead class="table-light">
                    <tr><th style="width:40%">Trường</th><th>Giá trị cũ</th></tr>
                  </thead>
                  <tbody id="tblOldBody">
                    <tr><td colspan="2" class="text-center text-body-secondary">—</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            {{-- Tab: giá trị mới --}}
            <div class="tab-pane fade" id="tabNew">
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead class="table-light">
                    <tr><th style="width:40%">Trường</th><th>Giá trị mới</th></tr>
                  </thead>
                  <tbody id="tblNewBody">
                    <tr><td colspan="2" class="text-center text-body-secondary">—</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            {{-- Tab: so sánh --}}
            <div class="tab-pane fade" id="tabDiff">
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead class="table-light">
                    <tr>
                      <th style="width:35%">Trường</th>
                      <th>Giá trị cũ</th>
                      <th>Giá trị mới</th>
                    </tr>
                  </thead>
                  <tbody id="tblDiffBody">
                    <tr><td colspan="3" class="text-center text-body-secondary">—</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
  function showDetail(logId) {
    const btn = document.querySelector(`button[onclick="showDetail(${logId})"]`);
    if (!btn) return;

    const raw = btn.getAttribute('data-properties');
    let props = {};
    try { props = JSON.parse(raw); } catch (e) {}

    const oldAttrs = props.old || {};
    const newAttrs = props.attributes || {};

    // Populate giá trị cũ
    const oldBody = document.getElementById('tblOldBody');
    oldBody.innerHTML = Object.keys(oldAttrs).length
      ? Object.entries(oldAttrs).map(([k, v]) =>
          `<tr><td class="text-body-secondary small">${k}</td>
               <td class="small">${v !== null && v !== undefined ? v : '<em class="text-body-secondary">null</em>'}</td></tr>`
        ).join('')
      : '<tr><td colspan="2" class="text-center text-body-secondary py-3">Không có dữ liệu cũ (tạo mới)</td></tr>';

    // Populate giá trị mới
    const newBody = document.getElementById('tblNewBody');
    newBody.innerHTML = Object.keys(newAttrs).length
      ? Object.entries(newAttrs).map(([k, v]) =>
          `<tr><td class="text-body-secondary small">${k}</td>
               <td class="small">${v !== null && v !== undefined ? v : '<em class="text-body-secondary">null</em>'}</td></tr>`
        ).join('')
      : '<tr><td colspan="2" class="text-center text-body-secondary py-3">Không có dữ liệu</td></tr>';

    // Populate diff (chỉ những trường thay đổi)
    const diffBody = document.getElementById('tblDiffBody');
    const changedKeys = Object.keys(newAttrs).filter(k =>
      JSON.stringify(oldAttrs[k]) !== JSON.stringify(newAttrs[k])
    );
    diffBody.innerHTML = changedKeys.length
      ? changedKeys.map(k => {
          const oldVal = oldAttrs[k] !== undefined ? oldAttrs[k] : '<em class="text-body-secondary">—</em>';
          const newVal = newAttrs[k] !== null && newAttrs[k] !== undefined ? newAttrs[k] : '<em class="text-body-secondary">null</em>';
          return `<tr>
            <td class="text-body-secondary small fw-medium">${k}</td>
            <td class="small text-danger"><del>${oldVal}</del></td>
            <td class="small text-success fw-medium">${newVal}</td>
          </tr>`;
        }).join('')
      : '<tr><td colspan="3" class="text-center text-body-secondary py-3">Không có trường nào thay đổi</td></tr>';

    // Mở modal
    const modal = new coreui.Modal(document.getElementById('detailModal'));
    modal.show();
  }

  function exportLog() {
    const params = new URLSearchParams(
      Object.fromEntries(
        [...document.getElementById('logFilterForm').querySelectorAll('[name]')]
          .map(el => [el.name, el.value])
          .filter(([, v]) => v)
      )
    );
    window.location.href = '{{ route('activity-log.export') }}?' + params.toString();
  }
</script>
@endpush