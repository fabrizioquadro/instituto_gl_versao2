<!doctype html>

<html
  lang="pt-BR"
  class="light-style layout-wide customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="/templates/assets/"
  data-template="horizontal-menu-template"
  data-style="light">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Redefinir senha - Instituto GL</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('img/logo.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
      rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('templates/assets/vendor/fonts/remixicon/remixicon.css') }}" />
    <link rel="stylesheet" href="{{ asset('templates/assets/vendor/fonts/flag-icons.css') }}" />

    <!-- Menu waves for no-customizer fix -->
    <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/node-waves/node-waves.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('templates/assets/vendor/css/rtl/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('templates/assets/vendor/css/rtl/theme-default.css') }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('templates/assets/css/demo.css') }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/typeahead-js/typeahead.css') }}" />
    <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/@form-validation/form-validation.css') }}" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="{{ asset('templates/assets/vendor/css/pages/page-auth.css') }}" />

    <!-- Helpers -->
    <script src="{{ asset('templates/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/js/template-customizer.js') }}"></script>
    <script src="{{ asset('templates/assets/js/config.js') }}"></script>
  </head>

  <body>
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">
        <div class="card">
          <div class="card-body">
            <!-- Logo -->
            <div class="app-brand justify-content-center mb-4">
              <a href="{{ url('/') }}" class="app-brand-link gap-2">
                <span class="app-brand-logo demo">
                  <img src="{{ asset('img/logo.png') }}" style="height: 40px" alt="Instituto GL" />
                </span>
                <span class="app-brand-text demo text-heading fw-semibold">Instituto GL</span>
              </a>
            </div>
            <!-- /Logo -->

            <h4 class="mb-2">Redefinir senha 🔒</h4>
            <p class="mb-4">Defina uma nova senha para sua conta.</p>

            @if ($errors->any())
              <div class="alert alert-danger mb-3">
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
              @csrf
              <input type="hidden" name="token" value="{{ $token }}" />

              <div class="form-floating form-floating-outline mb-4">
                <input
                  type="email"
                  class="form-control @error('email') is-invalid @enderror"
                  id="email"
                  name="email"
                  value="{{ old('email', $email ?? '') }}"
                  placeholder="Digite seu e-mail"
                  required
                  autofocus
                  autocomplete="email" />
                <label for="email">E-mail</label>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-floating form-floating-outline mb-4">
                <input
                  type="password"
                  class="form-control @error('password') is-invalid @enderror"
                  id="password"
                  name="password"
                  placeholder="Nova senha"
                  required
                  autocomplete="new-password" />
                <label for="password">Nova senha</label>
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-floating form-floating-outline mb-4">
                <input
                  type="password"
                  class="form-control"
                  id="password-confirm"
                  name="password_confirmation"
                  placeholder="Confirmar nova senha"
                  required
                  autocomplete="new-password" />
                <label for="password-confirm">Confirmar nova senha</label>
              </div>

              <button type="submit" class="btn btn-primary d-grid w-100">Redefinir senha</button>
            </form>

            <p class="text-center mt-3 mb-0">
              <a href="{{ route('login') }}">
                <i class="ri-arrow-left-line me-1"></i>Voltar para o login
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Core JS -->
    <script src="{{ asset('templates/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ asset('templates/assets/js/main.js') }}"></script>
    <script src="{{ asset('templates/assets/js/pages-auth.js') }}"></script>
  </body>
</html>
