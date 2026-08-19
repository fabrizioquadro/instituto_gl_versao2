@php
    $usuarioAtual = auth()->user();
    // Na V2 a imagem de todos os usuários (incluindo antigos administradores) fica em img/usuarios
    $avatarUrl = $usuarioAtual->imagem ? asset('img/usuarios/' . $usuarioAtual->imagem) : null;
    $iniciais = strtoupper(substr($usuarioAtual->nome, 0, 2));
@endphp
<!doctype html>

<html
  lang="pt-BR"
  class="light-style layout-menu-fixed layout-wide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="{{ config('app.assets_path', '/templates/assets/') }}"
  data-template="horizontal-menu-template"
  data-style="light">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>@yield('title', 'Instituto GL')</title>

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

    @yield('styles')

    <!-- Helpers -->
    <script src="{{ asset('templates/assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <script src="{{ asset('templates/assets/vendor/js/template-customizer.js') }}"></script>
    <script src="{{ asset('templates/assets/js/config.js') }}"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
      <div class="layout-container">
        <!-- Navbar -->

        <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
          <div class="container-xxl">
            <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-6">
              <a href="{{ route('dashboard') }}" class="app-brand-link gap-2">
                <span class="app-brand-logo demo">
                  <img src="{{ asset('img/logo.png') }}" style="height: 40px" alt="Instituto GL" />
                </span>
                <span class="app-brand-text demo menu-text fw-semibold">Instituto GL</span>
              </a>

              <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
                <i class="ri-close-fill align-middle"></i>
              </a>
            </div>

            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                <i class="ri-menu-fill ri-22px"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <!-- Style Switcher -->
                <li class="nav-item dropdown-style-switcher dropdown me-1 me-xl-0">
                  <a
                    class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                    <i class="ri-22px"></i>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                        <span class="align-middle"><i class="ri-sun-line ri-22px me-3"></i>Claro</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                        <span class="align-middle"><i class="ri-moon-clear-line ri-22px me-3"></i>Escuro</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                        <span class="align-middle"><i class="ri-computer-line ri-22px me-3"></i>Sistema</span>
                      </a>
                    </li>
                  </ul>
                </li>
                <!-- / Style Switcher -->

                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt class="rounded-circle" />
                      @else
                        <span class="avatar-initial rounded-circle bg-label-primary">{{ $iniciais }}</span>
                      @endif
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="{{ route('perfil') }}">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-2">
                            <div class="avatar avatar-online">
                              @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt class="rounded-circle" />
                              @else
                                <span class="avatar-initial rounded-circle bg-label-primary">{{ $iniciais }}</span>
                              @endif
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <span class="fw-medium d-block small">{{ $usuarioAtual->nome }}</span>
                            <small class="text-muted">{{ ucfirst($usuarioAtual->role) }}</small>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="{{ route('perfil') }}">
                        <i class="ri-user-3-line ri-22px me-3"></i><span class="align-middle">Perfil</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="{{ route('alterar_senha') }}">
                        <i class="ri-lock-password-line ri-22px me-3"></i><span class="align-middle">Alterar Senha</span>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    <li>
                      <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                          <i class="ri-logout-box-r-line ri-22px me-3"></i><span class="align-middle">Sair</span>
                        </button>
                      </form>
                    </li>
                  </ul>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </div>
        </nav>

        <!-- / Navbar -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Menu -->
            <aside id="layout-menu" class="layout-menu-horizontal menu-horizontal menu bg-menu-theme flex-grow-0">
              <div class="container-xxl d-flex h-100">
                <ul class="menu-inner">
                  <!-- Dashboard -->
                  <li class="menu-item">
                    <a href="{{ route('dashboard') }}" class="menu-link">
                      <i class="menu-icon tf-icons ri-home-smile-line"></i>
                      <div data-i18n="Dashboard">Dashboard</div>
                    </a>
                  </li>

                  <!-- Enfermagem -->
                  <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                      <i class="menu-icon tf-icons ri-nurse-line"></i>
                      <div data-i18n="Enfermagem">Enfermagem</div>
                    </a>
                    <ul class="menu-sub">
                      <li class="menu-item">
                        <a href="{{ route('enfermagem.index', ['aba' => 'fila']) }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-time-line"></i>
                          <div data-i18n="Fila de Espera">Fila de Espera</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('enfermagem.index', ['aba' => 'atendimentos']) }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-calendar-check-line"></i>
                          <div data-i18n="Atendimentos do Dia">Atendimentos do Dia</div>
                        </a>
                      </li>
                    </ul>
                  </li>
                  <!-- / Enfermagem -->

                  @if (auth()->user()->isAdmin())
                    <!-- Configurações -->
                    <li class="menu-item">
                      <a href="javascript:void(0)" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons ri-settings-3-line"></i>
                        <div data-i18n="Configurações">Configurações</div>
                      </a>
                      <ul class="menu-sub">
                        <li class="menu-item">
                          <a href="{{ route('config.clinicas.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ri-building-2-line"></i>
                            <div data-i18n="Clínicas">Clínicas</div>
                          </a>
                        </li>
                        <li class="menu-item">
                          <a href="{{ route('config.usuarios.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ri-user-settings-line"></i>
                            <div data-i18n="Usuários">Usuários</div>
                          </a>
                        </li>
                        <li class="menu-item">
                          <a href="{{ route('config.grupos.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ri-price-tag-3-line"></i>
                            <div data-i18n="Grupos">Grupos</div>
                          </a>
                        </li>
                        <li class="menu-item">
                          <a href="{{ route('config.medicamentos.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ri-flask-line"></i>
                            <div data-i18n="Medicamentos">Medicamentos</div>
                          </a>
                        </li>
                        <li class="menu-item">
                          <a href="{{ route('config.combos.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ri-box-3-line"></i>
                            <div data-i18n="Combos">Combos</div>
                          </a>
                        </li>
                        <li class="menu-item">
                          <a href="{{ route('config.soros.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ri-dropper-line"></i>
                            <div data-i18n="Soros">Soros</div>
                          </a>
                        </li>
                        <li class="menu-item">
                          <a href="{{ route('config.fornecedores.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ri-truck-line"></i>
                            <div data-i18n="Fornecedores">Fornecedores</div>
                          </a>
                        </li>
                      </ul>
                    </li>
                    <!-- / Configurações -->
                  @endif

                  <!-- Estoque -->
                  <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                      <i class="menu-icon tf-icons ri-box-3-line"></i>
                      <div data-i18n="Estoque">Estoque</div>
                    </a>
                    <ul class="menu-sub">
                      <li class="menu-item">
                        <a href="{{ route('estoque.estoques.index') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-stack-line"></i>
                          <div data-i18n="Estoque">Estoque</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('estoque.entradas.index') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-download-2-line"></i>
                          <div data-i18n="Entradas">Entradas</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('estoque.baixas.index') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-delete-back-2-line"></i>
                          <div data-i18n="Baixas">Baixas</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('estoque.transferencias.index') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-swap-box-line"></i>
                          <div data-i18n="Transferências">Transferências</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('estoque.abertos') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-flask-line"></i>
                          <div data-i18n="Estoques Abertos">Estoques Abertos</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('estoque.extrato') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-file-list-3-line"></i>
                          <div data-i18n="Extrato">Extrato</div>
                        </a>
                      </li>
                      @if (auth()->user()->isAdmin())
                        <li class="menu-item">
                          <a href="{{ route('estoque.ajustes.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ri-tools-line"></i>
                            <div data-i18n="Ajustes de Estoque">Ajustes de Estoque</div>
                          </a>
                        </li>
                      @endif
                    </ul>
                  </li>
                  <!-- / Estoque -->

                  <!-- Pacientes -->
                  <li class="menu-item">
                    <a href="{{ route('pacientes.index') }}" class="menu-link">
                      <i class="menu-icon tf-icons ri-user-3-line"></i>
                      <div data-i18n="Pacientes">Pacientes</div>
                    </a>
                  </li>
                  <!-- / Pacientes -->

                  <!-- Procedimentos -->
                  <li class="menu-item">
                    <a href="{{ route('procedimentos.index') }}" class="menu-link">
                      <i class="menu-icon tf-icons ri-stethoscope-line"></i>
                      <div data-i18n="Procedimentos">Procedimentos</div>
                    </a>
                  </li>
                  <!-- / Procedimentos -->

                  <!-- Relatórios -->
                  <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                      <i class="menu-icon tf-icons ri-bar-chart-box-line"></i>
                      <div data-i18n="Relatórios">Relatórios</div>
                    </a>
                    <ul class="menu-sub">
                      <li class="menu-item">
                        <a href="{{ route('relatorios.financeiro') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-money-dollar-circle-line"></i>
                          <div data-i18n="Financeiro">Financeiro</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('relatorios.financeiro_simplificado') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-bank-card-line"></i>
                          <div data-i18n="Financeiro Simplificado">Financeiro Simplificado</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('relatorios.vendas') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-shopping-cart-line"></i>
                          <div data-i18n="Vendas">Vendas</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('relatorios.enfermagem') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-nurse-line"></i>
                          <div data-i18n="Enfermagem">Enfermagem</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('relatorios.transferencias') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-swap-box-line"></i>
                          <div data-i18n="Transferências">Transferências</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('relatorios.baixas') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-delete-back-2-line"></i>
                          <div data-i18n="Baixas">Baixas</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('relatorios.recepcao') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-customer-service-line"></i>
                          <div data-i18n="Recepção">Recepção</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('relatorios.caixa') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-cash-line"></i>
                          <div data-i18n="Caixa Geral">Caixa Geral</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('relatorios.estoque') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-stack-line"></i>
                          <div data-i18n="Estoque">Estoque</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="{{ route('relatorios.pacientes') }}" class="menu-link">
                          <i class="menu-icon tf-icons ri-user-heart-line"></i>
                          <div data-i18n="Pacientes e Protocolos">Pacientes e Protocolos</div>
                        </a>
                      </li>
                    </ul>
                  </li>
                  <!-- / Relatórios -->
                </ul>
              </div>
            </aside>
            <!-- / Menu -->

            <!-- Content -->
            <div class="container-xxl flex-grow-1 container-p-y">
              @yield('content')
            </div>
            <!-- / Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl">
                <div
                  class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                  <div class="text-body mb-2 mb-md-0">
                    ©
                    <script>
                      document.write(new Date().getFullYear());
                    </script>
                    — Instituto GL
                  </div>
                  <div class="d-none d-lg-inline-block">
                    <span class="text-body">Sistema Online</span>
                  </div>
                </div>
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!--/ Content wrapper -->
        </div>

        <!--/ Layout container -->
      </div>
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>

    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>

    <!--/ Layout wrapper -->

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

    <!-- Main JS -->
    <script src="{{ asset('templates/assets/js/main.js') }}"></script>

    @yield('scripts')
  </body>
</html>
