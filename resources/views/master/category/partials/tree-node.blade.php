{{--
  resources/views/master/category/partials/tree-node.blade.php
  Đệ quy mỗi node trong cây danh mục.
  $node: Category model (with allChildren eager-loaded)
  $depth: int (0 = gốc)
--}}
@php
  $hasChildren = $node->children->isNotEmpty();
  $collapseId  = 'cc-' . $node->id;
  $isRoot      = $depth === 0;
@endphp

<div class="cat-tree-node cat-depth-{{ $depth }}">

  {{-- ROW --}}
  <div class="cat-toggle-row">

    {{-- Toggle collapse button (nếu có con) --}}
    @if ($hasChildren)
      <button
        class="cat-toggle-btn{{ $isRoot ? '' : ' collapsed' }}"
        type="button"
        data-coreui-toggle="collapse"
        data-coreui-target="#{{ $collapseId }}"
        aria-expanded="{{ $isRoot ? 'true' : 'false' }}"
        aria-controls="{{ $collapseId }}"
        title="Mở/Thu gọn"
      >
        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-chevron-bottom') }}"></use></svg>
      </button>
    @else
      <span class="cat-leaf-spacer" aria-hidden="true"></span>
    @endif

    {{-- Icon --}}
    @if ($isRoot)
      <svg class="icon text-primary" style="flex-shrink:0" aria-hidden="true">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use>
      </svg>
    @else
      <svg class="icon text-info" style="flex-shrink:0" aria-hidden="true">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-tag') }}"></use>
      </svg>
    @endif

    {{-- Code --}}
    <code class="cat-tree-code text-primary">{{ $node->code }}</code>

    {{-- Name --}}
    <span class="cat-tree-name text-truncate">{{ $node->name }}</span>

    {{-- Badges --}}
    <span class="cat-tree-badges">

      {{-- Có con --}}
      @if ($hasChildren)
        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:10px">
          {{ $node->children->count() }} con
        </span>
      @endif

      {{-- Status --}}
      @if ($node->status == 0)
        <span class="badge bg-secondary-subtle text-secondary border" style="font-size:10px">Ngừng</span>
      @endif

      {{-- Description tooltip --}}
      @if ($node->description)
        <span class="badge bg-body-secondary text-body border" style="font-size:10px"
              title="{{ $node->description }}">
          <svg class="icon icon-sm" aria-hidden="true"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use></svg>
        </span>
      @endif

      {{-- Edit --}}
      <button class="btn btn-xs btn-outline-primary cat-action-btn"
              style="font-size:11px;padding:1px 6px"
              onclick="openModal(
                {{ $node->id }},
                '{{ addslashes($node->code) }}',
                '{{ addslashes($node->name) }}',
                {{ $node->parent_id ?? 'null' }},
                '{{ addslashes($node->description ?? '') }}',
                {{ $node->status }}
              )"
              title="Chỉnh sửa">
        <svg class="icon icon-sm" aria-hidden="true"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
      </button>

    </span>
  </div>

  {{-- CHILDREN --}}
  @if ($hasChildren)
    <div class="cat-tree-children collapse{{ $isRoot ? ' show' : '' }}" id="{{ $collapseId }}">
      @foreach ($node->children->sortBy('name') as $child)
        @include('master.category.partials.tree-node', [
          'node'  => $child,
          'depth' => $depth + 1,
        ])
      @endforeach
    </div>
  @endif

</div>