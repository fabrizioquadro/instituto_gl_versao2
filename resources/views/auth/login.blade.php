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

    <title>Login - Instituto GL</title>

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
    <!-- Vendor -->
    <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/@form-validation/form-validation.css') }}" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="{{ asset('templates/assets/vendor/css/pages/page-auth.css') }}" />

    <!-- Helpers -->
    <script src="{{ asset('templates/assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="{{ asset('templates/assets/vendor/js/template-customizer.js') }}"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('templates/assets/js/config.js') }}"></script>
  </head>

  <body>
    <!-- Content -->

    <div class="authentication-wrapper authentication-cover">
      <!-- Logo -->
      <a href="{{ url('/') }}" class="auth-cover-brand d-flex align-items-center gap-2">
        <span class="app-brand-logo demo">
          <img src="{{ asset('img/logo.png') }}" style="height: 40px" alt="Instituto GL" />
        </span>
        <span class="app-brand-text demo text-heading fw-semibold">Instituto GL</span>
      </a>
      <!-- /Logo -->
      <div class="authentication-inner row m-0">
        <!-- /Left Section -->
        <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center justify-content-center p-12 pb-2">
          <img
            src="{{ asset('templates/assets/img/illustrations/auth-login-illustration-light.png') }}"
            class="auth-cover-illustration w-100"
            alt="auth-illustration"
            data-app-light-img="illustrations/auth-login-illustration-light.png"
            data-app-dark-img="illustrations/auth-login-illustration-dark.png" />
          <img
            src="{{ asset('templates/assets/img/illustrations/auth-cover-login-mask-light.png') }}"
            class="authentication-image"
            alt="mask"
            data-app-light-img="illustrations/auth-cover-login-mask-light.png"
            data-app-dark-img="illustrations/auth-cover-login-mask-dark.png" />
        </div>
        <!-- /Left Section -->

        <!-- Login -->
        <div
          class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg position-relative py-sm-12 px-12 py-6">
          <div class="w-px-400 mx-auto pt-5 pt-lg-0">
            <h4 class="mb-1">Bem-vindo ao Instituto GL! 👋</h4>
            <p class="mb-5">Entre com sua conta para começar</p>

            <form id="formAuthentication" class="mb-5" method="POST" action="{{ url('/login') }}">
              @csrf
              <div class="form-floating form-floating-outline mb-5">
                <input
                  type="text"
                  class="form-control"
                  id="email"
                  name="email"
                  placeholder="Digite seu e-mail ou usuário"
                  autofocus />
                <label for="email">E-mail ou usuário</label>
              </div>
              <div class="mb-5">
                <div class="form-password-toggle">
                  <div class="input-group input-group-merge">
                    <div class="form-floating form-floating-outline">
                      <input
                        type="password"
                        id="password"
                        class="form-control"
                        name="password"
                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                        aria-describedby="password" />
                      <label for="password">Senha</label>
                    </div>
                    <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                  </div>
                </div>
              </div>
              <div class="mb-5 d-flex justify-content-between mt-5">
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" id="remember-me" name="remember" />
                  <label class="form-check-label" for="remember-me"> Lembrar-me </label>
                </div>
                <a href="{{ route('password.request') }}" class="float-end mb-1 mt-2">
                  <span>Esqueceu a senha?</span>
                </a>
              </div>
              <button class="btn btn-primary d-grid w-100" type="submit">Entrar</button>
            </form>
          </div>
        </div>
        <!-- /Login -->
      </div>
    </div>

    <!-- / Content -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="{{ asset('templates/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/js/menu.js') }}"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('templates/assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset('templates/assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>

    <!-- Main JS -->
    <script src="{{ asset('templates/assets/js/main.js') }}"></script>

    <!-- Page JS -->
    <script src="{{ asset('templates/assets/js/pages-auth.js') }}"></script>
  </body>
</html>
