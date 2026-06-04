@extends('layouts.app')

@section('title', 'Hàng hóa — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Hàng hóa</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Hàng hóa</h4>
      <small class="text-body-secondary">Quản lý danh sách hàng hóa trong kho</small>
    </div>
    <button class="btn btn-primary" onclick="openForm()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm mới
    </button>
  </div>

  {{-- CARDS THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount }}</div>
            <div class="text-body-secondary small">Tổng mặt hàng</div>
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
            <div class="text-body-secondary small">Đang hoạt động</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BẢNG DANH SÁCH --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách hàng hóa</span>
      <form method="GET" action="{{ route('master.product.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:230px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Mã, tên, barcode...">
        </div>
        <select class="form-select" name="category_id" style="width:160px">
          <option value="">Tất cả nhóm</option>
          @foreach ($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
              {{ $cat->name }}
            </option>
          @endforeach
        </select>
        <select class="form-select" name="tracking_type" style="width:150px">
          <option value="">Tất cả tracking</option>
          @foreach (\App\Models\Product::trackingTypes() as $val => $label)
            <option value="{{ $val }}" {{ request('tracking_type') == $val ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
        <select class="form-select" name="status" style="width:130px">
          <option value="">Tất cả</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
          <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng</option>
        </select>
        <button type="submit" class="btn btn-outline-primary">Lọc</button>
        @if(request('search') || request('category_id') || request('tracking_type') || request('status') !== null && request('status') !== '')
          <a href="{{ route('master.product.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:55px">#</th>
              <th style="width:60px">Ảnh</th>
              <th style="width:110px">Mã</th>
              <th>Tên hàng hóa</th>
              <th style="width:140px">Nhóm</th>
              <th style="width:100px">ĐVT kho</th>
              <th style="width:120px">Tracking</th>
              <th class="text-center" style="width:90px">Tồn kho</th>
              <th class="text-center" style="width:120px">Trạng thái</th>
              <th class="text-center" style="width:110px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($products as $index => $product)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($products->currentPage() - 1) * $products->perPage() + $index + 1 }}
                </td>
                <td>
                  @if ($product->image_path)
                    <img src="{{ Storage::url($product->image_path) }}"
                         alt="{{ $product->name }}"
                         class="rounded" style="width:40px;height:40px;object-fit:cover;">
                  @else
                    <div class="rounded bg-body-secondary d-flex align-items-center justify-content-center"
                         style="width:40px;height:40px">
                      <svg class="icon icon-sm text-body-tertiary">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-image') }}"></use>
                      </svg>
                    </div>
                  @endif
                </td>
                <td><code class="text-primary fw-medium">{{ $product->code }}</code></td>
                <td>
                  <div class="fw-medium">{{ $product->name }}</div>
                  @if ($product->barcode)
                    <div class="small text-body-secondary">
                      <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-barcode') }}"></use></svg>
                      {{ $product->barcode }}
                    </div>
                  @endif
                </td>
                <td class="small text-body-secondary">{{ $product->category?->name ?? '—' }}</td>
                <td class="small">{{ $product->uom?->name ?? '—' }}</td>
                <td>
                  @php
                    $trackingColors = [1 => 'secondary', 2 => 'info', 3 => 'warning', 4 => 'primary'];
                    $color = $trackingColors[$product->tracking_type] ?? 'secondary';
                  @endphp
                  <span class="badge bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }}-subtle" style="font-size:11px">
                    {{ \App\Models\Product::trackingTypes()[$product->tracking_type] ?? '—' }}
                  </span>
                </td>
                <td class="text-center fw-semibold">
                  {{ number_format($product->total_stock, 0) }}
                </td>
                <td class="text-center">
                  @if ($product->status == 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1"
                          onclick="openForm({{ $product->id }})"
                          title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $product->id }}, '{{ addslashes($product->name) }}')"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
                  </svg>
                  Chưa có hàng hóa nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($products->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $products->firstItem() }}–{{ $products->lastItem() }}
          trong tổng số {{ $products->total() }} hàng hóa
        </small>
        {{ $products->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>

  {{-- OFFCANVAS FORM --}}
  <div class="offcanvas offcanvas-end" style="width:540px" tabindex="-1" id="productOffcanvas">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="productOffcanvasTitle">Thêm hàng hóa</h5>
      <button type="button" class="btn-close" data-coreui-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <form id="productForm" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        {{-- ===== THÔNG TIN CƠ BẢN ===== --}}
        <div class="mb-3 fw-semibold text-primary border-bottom pb-1">Thông tin cơ bản</div>

        {{-- Ảnh hàng hóa --}}
        <div class="mb-3">
          <label class="form-label">Ảnh hàng hóa</label>
          <div class="d-flex gap-3 align-items-start">
            <div id="imagePreviewWrap" class="rounded border bg-body-secondary d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0"
                 style="width:80px;height:80px">
              <img id="imagePreview" src="" alt="" class="d-none" style="width:80px;height:80px;object-fit:cover;">
              <svg id="imageIcon" class="icon icon-2xl text-body-tertiary">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-image') }}"></use>
              </svg>
            </div>
            <div class="flex-grow-1">
              <input type="file" class="form-control form-control-sm {{ $errors->has('image') ? 'is-invalid' : '' }}"
                    id="pImage" name="image"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    onchange="previewImage(this)">
              @error('image')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="form-text">JPEG, PNG, WebP — tối đa 2 MB</div>
              {{-- Nút xóa ảnh (chỉ hiện khi edit và đang có ảnh) --}}
              <div id="removeImageWrap" class="d-none mt-1">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remove_image"
                         id="removeImage" value="1" onchange="toggleRemoveImage(this)">
                  <label class="form-check-label text-danger small" for="removeImage">
                    Xóa ảnh hiện tại
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-5">
            <label class="form-label">Mã hàng hóa <span class="text-danger">*</span></label>
            <input type="text" class="form-control text-uppercase" id="pCode" name="code"
                   placeholder="VD: SP001" required maxlength="50">
          </div>
          <div class="col-7">
            <label class="form-label">Tên hàng hóa <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="pName" name="name"
                   placeholder="Tên đầy đủ" required maxlength="200">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Nhóm hàng hóa</label>
          <select class="form-select" id="pCategory" name="category_id">
            <option value="">— Chưa phân nhóm —</option>
            @foreach ($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->full_name }}</option>
            @endforeach
          </select>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label">ĐVT lưu kho <span class="text-danger">*</span></label>
            <select class="form-select" id="pUom" name="uom_id" required>
              <option value="">— Chọn ĐVT —</option>
              @foreach ($uoms as $uom)
                <option value="{{ $uom->id }}">{{ $uom->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6">
            <label class="form-label">ĐVT mua hàng</label>
            <select class="form-select" id="pUomPurchase" name="uom_purchase_id">
              <option value="">— Giống ĐVT kho —</option>
              @foreach ($uoms as $uom)
                <option value="{{ $uom->id }}">{{ $uom->name }}</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Barcode + nút sinh tự động --}}
        <div class="mb-3">
          <label class="form-label">Barcode / QR</label>
          <div class="input-group">
            <input type="text" class="form-control font-monospace" id="pBarcode" name="barcode"
                   placeholder="Mã barcode hoặc QR" maxlength="100">
            <button type="button" class="btn btn-outline-secondary" id="genBarcodeBtn"
                    onclick="generateBarcode()" title="Sinh barcode EAN-13 tự động">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-barcode') }}"></use></svg>
              Tự động
            </button>
          </div>
          <div class="form-text">Hoặc nhấn <strong>Tự động</strong> để sinh mã EAN-13 nội bộ</div>
        </div>

        {{-- ===== THÔNG SỐ KỸ THUẬT ===== --}}
        <div class="mb-3 fw-semibold text-primary border-bottom pb-1 mt-4">Thông số kỹ thuật</div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label">Cân nặng (kg)</label>
            <input type="number" class="form-control" id="pWeight" name="weight"
                   step="0.001" min="0" placeholder="0.000">
          </div>
          <div class="col-6">
            <label class="form-label">Thể tích (m³)</label>
            <input type="number" class="form-control" id="pVolume" name="volume"
                   step="0.001" min="0" placeholder="0.000">
          </div>
        </div>

        {{-- ===== QUẢN LÝ TỒN KHO ===== --}}
        <div class="mb-3 fw-semibold text-primary border-bottom pb-1 mt-4">Quản lý tồn kho</div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label">Tồn kho tối thiểu</label>
            <input type="number" class="form-control" id="pMinStock" name="min_stock"
                   step="0.001" min="0" value="0">
          </div>
          <div class="col-6">
            <label class="form-label">Tồn kho tối đa</label>
            <input type="number" class="form-control" id="pMaxStock" name="max_stock"
                   step="0.001" min="0" placeholder="Không giới hạn">
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label">Kiểu theo dõi <span class="text-danger">*</span>
              <span class="ms-1" data-coreui-toggle="tooltip"
                    title="None: không track | Lot: theo lô | Serial: theo số serial | Expiry: theo hạn dùng">
                <svg class="icon icon-sm text-info"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use></svg>
              </span>
            </label>
            <select class="form-select" id="pTracking" name="tracking_type" required
                    onchange="onTrackingChange(this.value)">
              @foreach (\App\Models\Product::trackingTypes() as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6">
            <label class="form-label">Quy tắc xuất kho
              <span class="ms-1" data-coreui-toggle="tooltip"
                    title="FIFO: xuất hàng nhập trước | FEFO: xuất hàng hết hạn trước | LIFO: xuất hàng nhập sau">
                <svg class="icon icon-sm text-info"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use></svg>
              </span>
            </label>
            <select class="form-select" id="pRotation" name="stock_rotation">
              @foreach (\App\Models\Product::rotationTypes() as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Cảnh báo hết hạn — chỉ hiện khi tracking = Expiry (4) --}}
        <div class="mb-3" id="alertExpiryGroup" style="display:none">
          <label class="form-label">Cảnh báo hết hạn trước (ngày)</label>
          <input type="number" class="form-control" id="pAlertExpiry" name="alert_before_expiry"
                 min="0" placeholder="VD: 30">
          <div class="form-text">Cảnh báo khi hàng sắp hết hạn trong vòng N ngày</div>
        </div>

        {{-- ===== THÔNG TIN THÊM ===== --}}
        <div class="mb-3 fw-semibold text-primary border-bottom pb-1 mt-4">Thông tin thêm</div>

        <div class="mb-3">
          <label class="form-label">Mô tả</label>
          <textarea class="form-control" id="pDesc" name="description" rows="2"
                    placeholder="Mô tả ngắn về hàng hóa..."></textarea>
        </div>

        <div class="mb-4">
          <label class="form-label">Trạng thái</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="status" id="pStatusActive" value="1" checked>
              <label class="form-check-label text-success" for="pStatusActive">Hoạt động</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="status" id="pStatusInactive" value="0">
              <label class="form-check-label text-secondary" for="pStatusInactive">Ngừng hoạt động</label>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
            Lưu hàng hóa
          </button>
          <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="offcanvas">Hủy</button>
        </div>

      </form>
    </div>
  </div>

  {{-- MODAL XÁC NHẬN XÓA --}}
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
            Bạn có chắc muốn xóa hàng hóa<br>
            <strong id="deleteProductName" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Không thể xóa nếu đang có tồn kho.</p>
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

@endsection

@push('scripts')
<script>
  const routeStore       = '{{ route('master.product.store') }}';
  const routeBase        = '{{ url('master/product') }}';
  const routeGenBarcode  = '{{ url('master/product/generate-barcode') }}';

  // Map product data for edit mode (paginated → keyBy in JS)
  const productsMap = {};
  @foreach ($products as $p)
    productsMap[{{ $p->id }}] = {
      id:                  {{ $p->id }},
      code:                '{{ addslashes($p->code) }}',
      name:                '{{ addslashes($p->name) }}',
      category_id:         {{ $p->category_id ?? 'null' }},
      uom_id:              {{ $p->uom_id ?? 'null' }},
      uom_purchase_id:     {{ $p->uom_purchase_id ?? 'null' }},
      barcode:             '{{ addslashes($p->barcode ?? '') }}',
      weight:              '{{ $p->weight ?? '' }}',
      volume:              '{{ $p->volume ?? '' }}',
      min_stock:           '{{ $p->min_stock ?? 0 }}',
      max_stock:           '{{ $p->max_stock ?? '' }}',
      tracking_type:       {{ $p->tracking_type ?? 1 }},
      stock_rotation:      {{ $p->stock_rotation ?? 1 }},
      alert_before_expiry: '{{ $p->alert_before_expiry ?? '' }}',
      description:         '{{ addslashes($p->description ?? '') }}',
      status:              {{ $p->status }},
      image_path:          '{{ $p->image_path ?? '' }}',
      image_url:           '{{ $p->image_path ? Storage::url($p->image_path) : '' }}',
    };
  @endforeach

  // ===== MỞ FORM =====
  function openForm(id = null) {
    const offcanvasEl = document.getElementById('productOffcanvas');
    const offcanvas   = new coreui.OffCanvas(offcanvasEl);
    const form        = document.getElementById('productForm');
    const title       = document.getElementById('productOffcanvasTitle');
    const method      = document.getElementById('formMethod');
    const codeInput    = document.getElementById('pCode');
    const barcodeInput = document.getElementById('pBarcode');
    const genBtn       = document.getElementById('genBarcodeBtn');

    form.reset();
    document.getElementById('pStatusActive').checked = true;
    resetImagePreview();
    document.getElementById('removeImageWrap').classList.add('d-none');
    document.getElementById('alertExpiryGroup').style.display = 'none';
    offcanvasEl.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    offcanvasEl.querySelectorAll('.invalid-feedback, .alert').forEach(el => el.remove());

    // Mặc định unlock
    codeInput.removeAttribute('readonly');
    codeInput.classList.remove('bg-body-secondary');
    barcodeInput.removeAttribute('readonly');
    barcodeInput.classList.remove('bg-body-secondary');
    genBtn.disabled = false;

    if (id && productsMap[id]) {
      const p = productsMap[id];
      title.textContent            = 'Chỉnh sửa hàng hóa';
      form.action                  = `${routeBase}/${id}`;
      method.value                 = 'PUT';

      codeInput.value              = p.code;
      document.getElementById('pName').value          = p.name;
      document.getElementById('pCategory').value      = p.category_id ?? '';
      document.getElementById('pUom').value           = p.uom_id ?? '';
      document.getElementById('pUomPurchase').value   = p.uom_purchase_id ?? '';
      barcodeInput.value           = p.barcode;
      document.getElementById('pWeight').value        = p.weight;
      document.getElementById('pVolume').value        = p.volume;
      document.getElementById('pMinStock').value      = p.min_stock;
      document.getElementById('pMaxStock').value      = p.max_stock;
      document.getElementById('pTracking').value      = p.tracking_type;
      document.getElementById('pRotation').value      = p.stock_rotation;
      document.getElementById('pAlertExpiry').value   = p.alert_before_expiry;
      document.getElementById('pDesc').value          = p.description;
      document.getElementById(p.status == 1 ? 'pStatusActive' : 'pStatusInactive').checked = true;

      if (p.image_url) {
        showImagePreview(p.image_url);
        document.getElementById('removeImageWrap').classList.remove('d-none');
      }
      onTrackingChange(p.tracking_type);

      // Lock mã và barcode khi edit
      codeInput.setAttribute('readonly', true);
      codeInput.classList.add('bg-body-secondary');
      barcodeInput.setAttribute('readonly', true);
      barcodeInput.classList.add('bg-body-secondary');
      genBtn.disabled = true;

    } else {
      title.textContent = 'Thêm hàng hóa';
      form.action       = routeStore;
      method.value      = 'POST';
    }

    offcanvas.show();
    setTimeout(() => document.getElementById('pName').focus(), 400);
  }

  // ===== BARCODE =====
  async function generateBarcode() {
    const btn = document.getElementById('genBarcodeBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang sinh...';
    try {
      const res  = await fetch(routeGenBarcode, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (data.barcode) {
        document.getElementById('pBarcode').value = data.barcode;
      }
    } catch (e) {
      alert('Không thể sinh barcode. Vui lòng thử lại.');
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-barcode') }}"></use></svg> Tự động`;
    }
  }

  // ===== TRACKING TYPE =====
  function onTrackingChange(val) {
    const expiryGroup = document.getElementById('alertExpiryGroup');
    // Chỉ hiện alert expiry khi tracking = 4 (Expiry)
    expiryGroup.style.display = (val == 4) ? 'block' : 'none';
  }
  document.getElementById('pTracking').addEventListener('change', function () {
    onTrackingChange(this.value);
  });

  // ===== IMAGE PREVIEW =====
  function previewImage(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = e => showImagePreview(e.target.result);
      reader.readAsDataURL(input.files[0]);
      // Uncheck "xóa ảnh" nếu user chọn ảnh mới
      const removeCheck = document.getElementById('removeImage');
      if (removeCheck) removeCheck.checked = false;
    }
  }

  function showImagePreview(src) {
    const img  = document.getElementById('imagePreview');
    const icon = document.getElementById('imageIcon');
    img.src = src;
    img.classList.remove('d-none');
    icon.classList.add('d-none');
  }

  function resetImagePreview() {
    const img  = document.getElementById('imagePreview');
    const icon = document.getElementById('imageIcon');
    img.src = '';
    img.classList.add('d-none');
    icon.classList.remove('d-none');
    document.getElementById('removeImage').checked = false;
  }

  function toggleRemoveImage(checkbox) {
    if (checkbox.checked) {
      resetImagePreview();
      document.getElementById('pImage').value = '';
    }
  }

  // ===== XÓA SẢN PHẨM =====
  function confirmDelete(id, name) {
    document.getElementById('deleteProductName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  // ===== AUTO UPPERCASE MÃ =====
  document.getElementById('pCode').addEventListener('input', function () {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });

  document.addEventListener('DOMContentLoaded', function () {
    const pfa = (document.body.dataset.pfa || '').trim();
    if (!pfa) return;

    // Có lỗi validation từ server — mở lại offcanvas với old input
    // Inject error alert vào đầu form
    const alertHtml = `
      <div class="alert alert-danger alert-dismissible mb-3" role="alert">
        <strong>Vui lòng kiểm tra lại:</strong>
        <ul class="mb-0 mt-1 ps-3">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
      </div>`;
    document.getElementById('productForm').insertAdjacentHTML('afterbegin', alertHtml);

    // Fill old input trực tiếp (chạy 1 lần duy nhất lúc load)
    document.getElementById('pCode').value        = @json(old('code', ''));
    document.getElementById('pName').value        = @json(old('name', ''));
    document.getElementById('pCategory').value    = @json(old('category_id', ''));
    document.getElementById('pUom').value         = @json(old('uom_id', ''));
    document.getElementById('pUomPurchase').value = @json(old('uom_purchase_id', ''));
    document.getElementById('pBarcode').value     = @json(old('barcode', ''));
    document.getElementById('pWeight').value      = @json(old('weight', ''));
    document.getElementById('pVolume').value      = @json(old('volume', ''));
    document.getElementById('pMinStock').value    = @json(old('min_stock', 0));
    document.getElementById('pMaxStock').value    = @json(old('max_stock', ''));
    document.getElementById('pTracking').value    = @json(old('tracking_type', 1));
    document.getElementById('pRotation').value    = @json(old('stock_rotation', 1));
    document.getElementById('pAlertExpiry').value = @json(old('alert_before_expiry', ''));
    document.getElementById('pDesc').value        = @json(old('description', ''));

    const oldStatus = @json(old('status', '1'));
    document.getElementById(oldStatus == '1' ? 'pStatusActive' : 'pStatusInactive').checked = true;
    onTrackingChange(@json(old('tracking_type', 1)));

    if (pfa.startsWith('update:')) {
      const id = pfa.split(':')[1];
      document.getElementById('productOffcanvasTitle').textContent = 'Chỉnh sửa hàng hóa';
      document.getElementById('productForm').action = `${routeBase}/${id}`;
      document.getElementById('formMethod').value = 'PUT';
    } else {
      document.getElementById('productOffcanvasTitle').textContent = 'Thêm hàng hóa';
      document.getElementById('productForm').action = routeStore;
      document.getElementById('formMethod').value = 'POST';
    }

    // Xóa flag khỏi DOM ngay lập tức
    document.body.dataset.pfa = '';

    new coreui.OffCanvas(document.getElementById('productOffcanvas')).show();
  });
</script>
@endpush