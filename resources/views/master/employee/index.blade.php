@extends('layouts.app')

@section('title', 'Nhân viên — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Nhân viên & Tài khoản</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Nhân viên & Tài khoản</h4>
      <small class="text-body-secondary">Quản lý hồ sơ nhân viên và tài khoản đăng nhập</small>
    </div>
    <button class="btn btn-primary" onclick="openEmployeeModal()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm nhân viên
    </button>
  </div>

  {{-- THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-people') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount }}</div>
            <div class="text-body-secondary small">Tổng nhân viên</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $activeCount }}</div>
            <div class="text-body-secondary small">Đang làm việc</div>
          </div>
        </div>
      </div>
    </div>
    @if ($pendingUsers->count() > 0)
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-warning">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-warning"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-bell') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $pendingUsers->count() }}</div>
            <div class="text-body-secondary small">Chờ kích hoạt</div>
          </div>
        </div>
      </div>
    </div>
    @endif
  </div>

  {{-- PENDING USERS: chờ kích hoạt --}}
  @if ($pendingUsers->count() > 0)
  <div class="card border-warning mb-4">
    <div class="card-header bg-warning-subtle d-flex align-items-center gap-2">
      <svg class="icon text-warning"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-bell') }}"></use></svg>
      <span class="fw-semibold text-warning-emphasis">Tài khoản chờ kích hoạt ({{ $pendingUsers->count() }})</span>
      <span class="ms-auto text-body-secondary small">Người dùng tự đăng ký — cần admin duyệt trước khi đăng nhập được</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Tên</th>
              <th>Email</th>
              <th>Thời gian đăng ký</th>
              <th style="width:280px" class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($pendingUsers as $pu)
            <tr>
              <td class="fw-medium">{{ $pu->name }}</td>
              <td class="text-body-secondary">{{ $pu->email }}</td>
              <td class="text-body-secondary small">{{ $pu->created_at->format('d/m/Y H:i') }}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-success"
                        onclick="openActivateModal({{ $pu->id }}, '{{ addslashes($pu->name) }}', '{{ $pu->email }}')"
                        title="Kích hoạt tài khoản này">
                  <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
                  Kích hoạt
                </button>
                <form method="POST" action="{{ route('master.employee.user.reject', $pu) }}"
                      class="d-inline"
                      onsubmit="return confirm('Từ chối và xóa tài khoản \'{{ addslashes($pu->email) }}\'?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Từ chối & xóa">
                    <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-ban') }}"></use></svg>
                    Từ chối
                  </button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  {{-- BẢNG NHÂN VIÊN --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách nhân viên</span>
      <form method="GET" action="{{ route('master.employee.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:260px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Mã, tên, số điện thoại...">
        </div>
        <select class="form-select" name="status" style="width:140px">
          <option value="">Tất cả</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Đang làm</option>
          <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Nghỉ việc</option>
        </select>
        <button type="submit" class="btn btn-outline-primary">Lọc</button>
        @if(request('search') || (request('status') !== null && request('status') !== ''))
          <a href="{{ route('master.employee.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:60px" class="text-center">#</th>
              <th style="width:100px">Mã NV</th>
              <th>Họ tên</th>
              <th>Số điện thoại</th>
              <th class="text-center" style="width:180px">Tài khoản</th>
              <th class="text-center" style="width:130px">Vai trò</th>
              <th class="text-center" style="width:120px">Trạng thái</th>
              <th class="text-center" style="width:160px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($employees as $index => $emp)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($employees->currentPage() - 1) * $employees->perPage() + $index + 1 }}
                </td>
                <td class="font-monospace fw-medium">{{ $emp->code }}</td>
                <td class="fw-medium">{{ $emp->full_name }}</td>
                <td class="text-body-secondary">{{ $emp->phone ?? '—' }}</td>

                {{-- Tài khoản --}}
                <td class="text-center">
                  @if ($emp->hasAccount())
                    @if ($emp->user->is_active)
                      <span class="badge bg-info-subtle text-info border border-info-subtle" title="{{ $emp->user->email }}">
                        <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user') }}"></use></svg>
                        {{ Str::limit($emp->user->email, 22) }}
                      </span>
                    @else
                      <span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="{{ $emp->user->email }}">
                        <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clock') }}"></use></svg>
                        Chờ kích hoạt
                      </span>
                    @endif
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use></svg>
                      Chưa có TK
                    </span>
                  @endif
                </td>

                {{-- Vai trò --}}
                <td class="text-center">
                  @if ($emp->hasAccount())
                    @php $role = $emp->user->roles->first() @endphp
                    @if ($role?->name === 'warehouse_manager')
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Thủ kho</span>
                    @elseif ($role?->name === 'warehouse_staff')
                      <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">NV kho</span>
                    @else
                      <span class="text-body-secondary small">—</span>
                    @endif
                  @else
                    <span class="text-body-secondary small">—</span>
                  @endif
                </td>

                {{-- Trạng thái nhân viên --}}
                <td class="text-center">
                  @if ($emp->status == 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
                      Đang làm
                    </span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-ban') }}"></use></svg>
                      Nghỉ việc
                    </span>
                  @endif
                </td>

                {{-- Thao tác --}}
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary"
                          onclick="openEmployeeModal({{ $emp->id }}, '{{ addslashes($emp->code) }}', '{{ addslashes($emp->full_name) }}', '{{ $emp->phone }}', '{{ $emp->email }}', {{ $emp->status }})"
                          title="Sửa hồ sơ">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>

                  @if ($emp->hasAccount())
                    <button class="btn btn-sm btn-outline-info"
                            onclick="openUpdateAccountModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}', '{{ $emp->user->email }}', '{{ $emp->user->roles->first()?->name }}')"
                            title="Quản lý tài khoản">
                      <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-settings') }}"></use></svg>
                    </button>
                  @else
                    <button class="btn btn-sm btn-outline-success"
                            onclick="openCreateAccountModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}', '{{ $emp->email }}')"
                            title="Tạo tài khoản">
                      <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user-plus') }}"></use></svg>
                    </button>
                  @endif

                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $emp->id }}, '{{ addslashes($emp->full_name) }}', {{ $emp->hasAccount() ? 'true' : 'false' }})"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-people') }}"></use>
                  </svg>
                  Chưa có nhân viên nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($employees->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $employees->firstItem() }}–{{ $employees->lastItem() }}
          trong tổng số {{ $employees->total() }} nhân viên
        </small>
        {{ $employees->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>


  {{-- ===== MODAL: HỒ SƠ NHÂN VIÊN (có toggle tạo tài khoản) ===== --}}
  <div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form id="employeeForm" method="POST">
          @csrf
          <input type="hidden" name="_method" id="empMethod" value="POST">

          <div class="modal-header">
            <h5 class="modal-title" id="employeeModalLabel">Thêm nhân viên</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>
          <div class="modal-body">

            {{-- Hồ sơ nhân viên --}}
            <div class="row g-3 mb-3">
              <div class="col-12">
                <h6 class="fw-semibold text-body-secondary mb-2 text-uppercase small">
                  <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-contact') }}"></use></svg>
                  Hồ sơ nhân viên
                </h6>
              </div>
              <div class="col-sm-4">
                <label class="form-label fw-medium">Mã NV <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace text-uppercase" name="code" id="empCode"
                       placeholder="NV001" maxlength="20" required>
              </div>
              <div class="col-sm-8">
                <label class="form-label fw-medium">Họ và tên <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="full_name" id="empName"
                       placeholder="Nguyễn Văn A" maxlength="100" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-medium">Số điện thoại</label>
                <input type="text" class="form-control" name="phone" id="empPhone"
                       placeholder="0901234567" maxlength="20">
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-medium">Email liên hệ</label>
                <input type="email" class="form-control" name="email" id="empEmail"
                       placeholder="nv@example.com" maxlength="100">
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Trạng thái</label>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="status" id="empStatusActive" value="1" checked>
                    <label class="form-check-label text-success" for="empStatusActive">Đang làm việc</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="status" id="empStatusInactive" value="0">
                    <label class="form-check-label text-secondary" for="empStatusInactive">Nghỉ việc</label>
                  </div>
                </div>
              </div>
            </div>

            {{-- Toggle tạo tài khoản — chỉ hiện khi thêm mới --}}
            <div id="createAccountSection">
              <hr class="my-3">
              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="toggleCreateAccount" name="create_account" value="1"
                       onchange="toggleAccountFields(this.checked)">
                <label class="form-check-label fw-medium" for="toggleCreateAccount">
                  <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user-plus') }}"></use></svg>
                  Tạo kèm tài khoản đăng nhập
                </label>
              </div>

              <div id="accountFields" class="d-none">
                <div class="p-3 bg-body-secondary rounded-2">
                  <h6 class="fw-semibold text-body-secondary mb-3 text-uppercase small">
                    <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-unlocked') }}"></use></svg>
                    Thông tin tài khoản
                  </h6>
                  <div class="row g-3">
                    <div class="col-sm-6">
                      <label class="form-label fw-medium">Tên hiển thị <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="login_name" id="accLoginName"
                             placeholder="Tên khi đăng nhập" maxlength="100">
                    </div>
                    <div class="col-sm-6">
                      <label class="form-label fw-medium">Email đăng nhập <span class="text-danger">*</span></label>
                      <input type="email" class="form-control" name="login_email" id="accLoginEmail"
                             placeholder="email@example.com">
                    </div>
                    <div class="col-sm-6">
                      <label class="form-label fw-medium">Mật khẩu <span class="text-danger">*</span></label>
                      <input type="password" class="form-control" name="password"
                             placeholder="Tối thiểu 8 ký tự">
                    </div>
                    <div class="col-sm-6">
                      <label class="form-label fw-medium">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                      <input type="password" class="form-control" name="password_confirmation"
                             placeholder="Nhập lại mật khẩu">
                    </div>
                    <div class="col-12">
                      <label class="form-label fw-medium">Vai trò <span class="text-danger">*</span></label>
                      <select class="form-select" name="role">
                        <option value="">— Chọn vai trò —</option>
                        @foreach ($roles as $role)
                          <option value="{{ $role->name }}">
                            {{ $role->name === 'warehouse_manager' ? '🔑 Thủ kho (Quản lý)' : '👤 Nhân viên kho' }}
                          </option>
                        @endforeach
                      </select>
                      <div class="form-text">Tài khoản do admin tạo sẽ được kích hoạt ngay.</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-primary">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              Lưu
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>


  {{-- ===== MODAL: KÍCH HOẠT USER TỰ ĐĂNG KÝ ===== --}}
  <div class="modal fade" id="activateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="activateForm" method="POST">
          @csrf

          <div class="modal-header">
            <h5 class="modal-title">Kích hoạt tài khoản</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning py-2 small mb-3">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user') }}"></use></svg>
              Kích hoạt tài khoản: <strong id="activateUserName"></strong>
              <span class="text-body-secondary">(<span id="activateUserEmail"></span>)</span>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Gán vai trò <span class="text-danger">*</span></label>
              <select class="form-select" name="role" required>
                <option value="">— Chọn vai trò —</option>
                @foreach ($roles as $role)
                  <option value="{{ $role->name }}">
                    {{ $role->name === 'warehouse_manager' ? '🔑 Thủ kho (Quản lý)' : '👤 Nhân viên kho' }}
                  </option>
                @endforeach
              </select>
            </div>
            <p class="text-body-secondary small mb-0">
              Sau khi kích hoạt, người dùng có thể đăng nhập ngay. Bạn có thể gắn họ với hồ sơ nhân viên sau.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-success">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
              Kích hoạt
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>


  {{-- ===== MODAL: TẠO TÀI KHOẢN (cho nhân viên chưa có TK) ===== --}}
  <div class="modal fade" id="createAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="createAccountForm" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Tạo tài khoản đăng nhập</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info py-2 small mb-3">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use></svg>
              Tạo tài khoản cho: <strong id="createAccEmpName"></strong>
            </div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-medium">Tên hiển thị <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="login_name" id="createAccName"
                       placeholder="Tên hiển thị khi đăng nhập" maxlength="100" required>
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Email đăng nhập <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="login_email" id="createAccEmail"
                       placeholder="email@example.com" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-medium">Mật khẩu <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="password"
                       placeholder="Tối thiểu 8 ký tự" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-medium">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="password_confirmation"
                       placeholder="Nhập lại mật khẩu" required>
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Vai trò <span class="text-danger">*</span></label>
                <select class="form-select" name="role" required>
                  <option value="">— Chọn vai trò —</option>
                  @foreach ($roles as $role)
                    <option value="{{ $role->name }}">
                      {{ $role->name === 'warehouse_manager' ? '🔑 Thủ kho (Quản lý)' : '👤 Nhân viên kho' }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-success">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user-plus') }}"></use></svg>
              Tạo tài khoản
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>


  {{-- ===== MODAL: CẬP NHẬT TÀI KHOẢN ===== --}}
  <div class="modal fade" id="updateAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="updateAccountForm" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title">Quản lý tài khoản</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-secondary py-2 small mb-3">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user') }}"></use></svg>
              Tài khoản: <strong id="updateAccEmail"></strong>
            </div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-medium">Vai trò <span class="text-danger">*</span></label>
                <select class="form-select" name="role" id="updateAccRole" required>
                  @foreach ($roles as $role)
                    <option value="{{ $role->name }}">
                      {{ $role->name === 'warehouse_manager' ? '🔑 Thủ kho (Quản lý)' : '👤 Nhân viên kho' }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Mật khẩu mới
                  <span class="text-body-secondary fw-normal">(để trống nếu không đổi)</span>
                </label>
                <input type="password" class="form-control" name="new_password"
                       placeholder="Tối thiểu 8 ký tự">
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Xác nhận mật khẩu mới</label>
                <input type="password" class="form-control" name="new_password_confirmation"
                       placeholder="Nhập lại mật khẩu mới">
              </div>
            </div>
            <hr class="my-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-medium text-danger small">Xóa tài khoản đăng nhập</div>
                <div class="text-body-secondary" style="font-size:0.8rem">Hồ sơ nhân viên vẫn được giữ lại</div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteAccount()" id="deleteAccBtn">
                <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                Xóa tài khoản
              </button>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-primary">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              Lưu thay đổi
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>


  {{-- ===== MODAL: XÓA NHÂN VIÊN ===== --}}
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center px-4 pb-2">
          <svg class="icon icon-3xl text-danger mb-3">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
          </svg>
          <h6 class="fw-semibold mb-1">Xác nhận xóa</h6>
          <p class="text-body-secondary small mb-0">
            Bạn có chắc muốn xóa nhân viên<br>
            <strong id="deleteEmpName" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Hành động này không thể hoàn tác.</p>
        </div>
        <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Hủy</button>
          <form id="deleteForm" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- Form ẩn DELETE tài khoản --}}
  <form id="deleteAccountForm" method="POST" class="d-none">
    @csrf
    @method('DELETE')
  </form>

@endsection

@push('scripts')
<script>
  const routeStore = '{{ route('master.employee.store') }}';
  const routeBase  = '{{ url('master/employee') }}';
  const routeUserBase = '{{ url('master/employee/user') }}';

  // ===== TOGGLE ACCOUNT FIELDS =====
  function toggleAccountFields(show) {
    const fields = document.getElementById('accountFields');
    fields.classList.toggle('d-none', !show);
    // Sync tên hiển thị từ họ tên nhân viên
    if (show) {
      const name = document.getElementById('empName').value;
      if (name) document.getElementById('accLoginName').value = name;
      const email = document.getElementById('empEmail').value;
      if (email) document.getElementById('accLoginEmail').value = email;
      document.getElementById('accLoginName').focus();
    }
  }

  // Tự động đồng bộ tên khi nhập họ tên
  document.getElementById('empName').addEventListener('input', function () {
    const toggle = document.getElementById('toggleCreateAccount');
    if (toggle && toggle.checked) {
      document.getElementById('accLoginName').value = this.value;
    }
  });

  // ===== HỒ SƠ NHÂN VIÊN =====
  function openEmployeeModal(id = null, code = '', name = '', phone = '', email = '', status = 1) {
    const modal  = new coreui.Modal(document.getElementById('employeeModal'));
    const form   = document.getElementById('employeeForm');
    const title  = document.getElementById('employeeModalLabel');
    const method = document.getElementById('empMethod');
    const createSection = document.getElementById('createAccountSection');

    if (id) {
      title.textContent = 'Chỉnh sửa hồ sơ nhân viên';
      form.action  = `${routeBase}/${id}`;
      method.value = 'PUT';
      document.getElementById('empCode').value  = code;
      document.getElementById('empName').value  = name;
      document.getElementById('empPhone').value = phone;
      document.getElementById('empEmail').value = email;
      document.getElementById(status == 1 ? 'empStatusActive' : 'empStatusInactive').checked = true;
      createSection.classList.add('d-none'); // Ẩn phần tạo tài khoản khi edit
    } else {
      title.textContent = 'Thêm nhân viên';
      form.action  = routeStore;
      method.value = 'POST';
      form.reset();
      document.getElementById('empStatusActive').checked = true;
      document.getElementById('toggleCreateAccount').checked = false;
      document.getElementById('accountFields').classList.add('d-none');
      createSection.classList.remove('d-none'); // Hiện phần tạo tài khoản khi thêm mới
    }

    modal.show();
    setTimeout(() => document.getElementById('empCode').focus(), 300);
  }

  // ===== KÍCH HOẠT USER TỰ ĐĂNG KÝ =====
  function openActivateModal(userId, userName, userEmail) {
    const modal = new coreui.Modal(document.getElementById('activateModal'));
    const form  = document.getElementById('activateForm');
    form.action = `${routeUserBase}/${userId}/activate`;
    document.getElementById('activateUserName').textContent  = userName;
    document.getElementById('activateUserEmail').textContent = userEmail;
    modal.show();
  }

  // ===== TẠO TÀI KHOẢN (nhân viên chưa có TK) =====
  function openCreateAccountModal(empId, empName, empEmail = '') {
    const modal = new coreui.Modal(document.getElementById('createAccountModal'));
    const form  = document.getElementById('createAccountForm');
    form.action = `${routeBase}/${empId}/account`;
    form.reset();
    document.getElementById('createAccEmpName').textContent = empName;
    document.getElementById('createAccName').value  = empName;
    document.getElementById('createAccEmail').value = empEmail || '';
    modal.show();
  }

  // ===== CẬP NHẬT TÀI KHOẢN =====
  let currentEmpIdForAccount = null;
  function openUpdateAccountModal(empId, empName, email, currentRole) {
    currentEmpIdForAccount = empId;
    const modal = new coreui.Modal(document.getElementById('updateAccountModal'));
    const form  = document.getElementById('updateAccountForm');
    form.action = `${routeBase}/${empId}/account`;
    document.getElementById('updateAccEmail').textContent = email;
    document.getElementById('updateAccRole').value = currentRole || '';
    form.querySelector('[name=new_password]').value = '';
    form.querySelector('[name=new_password_confirmation]').value = '';
    modal.show();
  }

  function confirmDeleteAccount() {
    if (!currentEmpIdForAccount) return;
    if (!confirm('Bạn có chắc muốn xóa tài khoản đăng nhập này? Hồ sơ nhân viên sẽ được giữ lại.')) return;
    const form = document.getElementById('deleteAccountForm');
    form.action = `${routeBase}/${currentEmpIdForAccount}/account`;
    form.submit();
  }

  // ===== XÓA NHÂN VIÊN =====
  function confirmDelete(id, name, hasAccount) {
    if (hasAccount) {
      alert(`Không thể xóa "${name}" vì đang có tài khoản đăng nhập.\nHãy xóa tài khoản trước.`);
      return;
    }
    document.getElementById('deleteEmpName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }
</script>
@endpush