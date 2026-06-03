{{--
  resources/views/master/category/partials/tree.blade.php
  Tree view cho cấu trúc phân cấp danh mục hàng hóa.
  Sử dụng CoreUI v5 native collapse (data-coreui-*).
  Biến truyền vào: $categories (Collection<Category>, chỉ root nodes, children đã eager-loaded)
--}}

<div class="card" id="categoryTreeCard">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">
      <svg class="icon me-1 text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-sitemap') }}"></use></svg>
      Sơ đồ cây danh mục
    </span>
    <div class="d-flex gap-2 align-items-center">
      <button class="btn btn-sm btn-outline-secondary" onclick="catTreeExpandAll()">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-bottom') }}"></use></svg>
        Mở rộng tất cả
      </button>
      <button class="btn btn-sm btn-outline-secondary" onclick="catTreeCollapseAll()">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-top') }}"></use></svg>
        Thu gọn tất cả
      </button>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="category-tree p-3" id="categoryTreeRoot">
      @forelse ($categories as $root)
        @include('master.category.partials.tree-node', ['node' => $root, 'depth' => 0])
      @empty
        <div class="text-center text-body-secondary py-4">
          <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use>
          </svg>
          Chưa có danh mục nào
        </div>
      @endforelse
    </div>
  </div>
</div>

<style>
  .category-tree { font-size: 0.875rem; }

  .cat-tree-node { position: relative; }

  .cat-toggle-row {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 8px;
    border-radius: var(--cui-border-radius);
    cursor: default;
    transition: background 0.1s;
    line-height: 1.4;
  }
  .cat-toggle-row:hover { background: var(--cui-secondary-bg); }

  .cat-toggle-btn {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    padding: 0;
    color: var(--cui-body-color);
    opacity: 0.45;
    border-radius: 3px;
    transition: opacity 0.15s, transform 0.2s;
  }
  .cat-toggle-btn:hover { opacity: 0.8; }
  .cat-toggle-btn.collapsed .icon { transform: rotate(-90deg); }
  .cat-toggle-btn .icon { transition: transform 0.2s; }

  .cat-leaf-spacer { width: 18px; flex-shrink: 0; }

  .cat-tree-children {
    margin-left: 22px;
    padding-left: 12px;
    border-left: 1px dashed var(--cui-border-color);
  }

  .cat-tree-code {
    font-family: var(--cui-font-monospace);
    font-size: 0.8rem;
    font-weight: 600;
    min-width: 70px;
  }
  .cat-tree-name { flex: 1; min-width: 0; }
  .cat-tree-badges { display: flex; gap: 4px; flex-wrap: nowrap; align-items: center; margin-left: auto; }

  .cat-action-btn {
    opacity: 0;
    transition: opacity 0.15s;
    flex-shrink: 0;
  }
  .cat-toggle-row:hover .cat-action-btn { opacity: 1; }

  /* depth indent colors */
  .cat-depth-0 > .cat-tree-children { border-left-color: rgba(var(--cui-primary-rgb), 0.3); }
  .cat-depth-1 > .cat-tree-children { border-left-color: rgba(var(--cui-info-rgb), 0.3); }
  .cat-depth-2 > .cat-tree-children { border-left-color: rgba(var(--cui-success-rgb), 0.25); }
</style>

<script>
  function catTreeExpandAll() {
    document.querySelectorAll('#categoryTreeRoot .collapse').forEach(el => {
      coreui.Collapse.getOrCreateInstance(el, { toggle: false }).show();
    });
    document.querySelectorAll('#categoryTreeRoot .cat-toggle-btn').forEach(btn => {
      btn.classList.remove('collapsed');
      btn.setAttribute('aria-expanded', 'true');
    });
  }

  function catTreeCollapseAll() {
    document.querySelectorAll('#categoryTreeRoot .collapse').forEach(el => {
      coreui.Collapse.getOrCreateInstance(el, { toggle: false }).hide();
    });
    document.querySelectorAll('#categoryTreeRoot .cat-toggle-btn').forEach(btn => {
      btn.classList.add('collapsed');
      btn.setAttribute('aria-expanded', 'false');
    });
  }
</script>