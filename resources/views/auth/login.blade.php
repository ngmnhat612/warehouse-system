<!DOCTYPE html>
<html lang="vi">
<head>
  @include('layouts.partials.head')
  @section('title', 'Đăng nhập — Warehouse System')
</head>
<body class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">

  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <div class="card-group d-block d-md-flex row">

          {{-- Form đăng nhập --}}
          <div class="card col-md-7 p-4 mb-0">
            <div class="card-body">

              <h1>Đăng nhập</h1>
              <p class="text-body-secondary">Hệ thống quản lý kho Ment Automation</p>

              <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="input-group mb-3">
                  <span class="input-group-text">
                    <svg class="icon">
                      <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user') }}"></use>
                    </svg>
                  </span>
                  <input class="form-control @error('email') is-invalid @enderror"
                         type="email"
                         name="email"
                         value="{{ old('email') }}"
                         placeholder="Email"
                         autocomplete="email"
                         autofocus>
                  @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                {{-- Mật khẩu --}}
                <div class="input-group mb-4">
                  <span class="input-group-text">
                    <svg class="icon">
                      <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use>
                    </svg>
                  </span>
                  <input class="form-control @error('password') is-invalid @enderror"
                         type="password"
                         name="password"
                         placeholder="Mật khẩu"
                         autocomplete="current-password">
                  @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                {{-- Remember me --}}
                <div class="form-check mb-4">
                  <input class="form-check-input" type="checkbox" name="remember" id="remember">
                  <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>

                <div class="row">
                  <div class="col-6">
                    <button class="btn btn-primary px-4" type="submit">Đăng nhập</button>
                  </div>
                  @if (Route::has('password.request'))
                  <div class="col-6 text-end">
                    <a class="btn btn-link px-0" href="{{ route('password.request') }}">
                      Quên mật khẩu?
                    </a>
                  </div>
                  @endif
                </div>

              </form>
            </div>
          </div>

          {{-- Panel bên phải --}}
          <div class="card col-md-5 text-white bg-primary py-5">
            <div class="card-body text-center d-flex flex-column justify-content-center">
              <h2>Warehouse System</h2>
              <p class="opacity-75 mt-2">
                Hệ thống quản lý kho hàng<br>
                Ment Automation
              </p>
              <div class="mt-3">
                <svg class="icon icon-4xl text-white opacity-50">
                  <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
                </svg>
              </div>
            </div>
          </div>

        </div>
        {{-- /.card-group --}}

      </div>
    </div>
  </div>

  {{-- JS tối thiểu cho trang login --}}
  <script src="{{ asset('vendor/coreui/js/coreui.bundle.min.js') }}"></script>

</body>
</html>
