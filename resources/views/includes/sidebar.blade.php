<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('dashboard') }}" class="brand-link">
        <img src="{{ asset('img/logo.png') }}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Perpustakaan</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ asset('img/user.png') }}" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block">{{ Auth::user()->name }}</a>
            </div>
        </div>

        <!-- SidebarSearch Form -->
        <!-- href befor 1 -->
        <form class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="text" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </form>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add arrows to the nav with the class -->
                <li class="nav-item menu-open">
                    <a href="#" class="nav-link active">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                            Dashboard
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Home</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Books Management -->
                @auth
                    @role('Super Admin|Librarian')
                        <li class="nav-item">
                            <a href="{{ route('books.index') }}" class="nav-link @if(request()->is('books/*')) active @endif">
                                <i class="nav-icon fas fa-book"></i>
                                <p>Books</p>
                            </a>
                        </li>
                    @endrole
                @endauth

                <!-- Categories Management -->
                @auth
                    @role('Super Admin|Librarian')
                        <li class="nav-item">
                            <a href="{{ route('categories.index') }}" class="nav-link @if(request()->is('categories/*')) active @endif">
                                <i class="nav-icon fas fa-tags"></i>
                                <p>Categories</p>
                            </a>
                        </li>
                    @endrole
                @endauth

                <!-- Borrowings Management -->
                @auth
                    @role('Super Admin|Librarian')
                        <li class="nav-item">
                            <a href="{{ route('borrowings.index') }}" class="nav-link @if(request()->is('borrowings/*')) active @endif">
                                <i class="nav-icon fas fa-exchange-alt"></i>
                                <p>Borrowings</p>
                            </a>
                        </li>
                    @endrole
                @endauth

                <!-- Fines Management -->
                @auth
                    @role('Super Admin|Librarian')
                        <li class="nav-item">
                            <a href="{{ route('fines.index') }}" class="nav-link @if(request()->is('fines/*')) active @endif">
                                <i class="nav-icon fas fa-money-bill"></i>
                                <p>Fines</p>
                            </a>
                        </li>
                    @endrole
                @endauth

                <!-- Fine Configuration (Super Admin only) -->
                @auth
                    @role('Super Admin')
                        <li class="nav-item">
                            <a href="{{ route('fine-config.index') }}" class="nav-link @if(request()->is('fine-config/*')) active @endif">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>Fine Config</p>
                            </a>
                        </li>
                    @endrole
                @endauth

                <!-- Penalty Configuration (Super Admin only) -->
                @auth
                    @role('Super Admin')
                        <li class="nav-item">
                            <a href="{{ route('penalty-config.index') }}" class="nav-link @if(request()->is('penalty-config/*')) active @endif">
                                <i class="nav-icon fas fa-exclamation-triangle"></i>
                                <p>Penalty Config</p>
                            </a>
                        </li>
                    @endrole
                @endauth

                <!-- Users Management (Super Admin only) -->
                @auth
                    @role('Super Admin')
                        <li class="nav-item">
                            <a href="{{ route('users.index') }}" class="nav-link @if(request()->is('users/*')) active @endif">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Users</p>
                            </a>
                        </li>
                    @endrole
                @endauth

                <!-- Member Dashboard -->
                @auth
                    @role('Member')
                        <li class="nav-item">
                            <a href="{{ route('member.dashboard') }}" class="nav-link @if(request()->is('member/*')) active @endif">
                                <i class="nav-icon fas fa-user"></i>
                                <p>My Dashboard</p>
                            </a>
                        </li>
                    @endrole
                @endauth

                <!-- Reports -->
                @auth
                    @role('Super Admin|Librarian')
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>
                                    Reports
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('reports.borrowings') }}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Borrowing Reports</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('reports.fines') }}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Fine Reports</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endrole
                @endauth
            </ul>
        </nav>
    </div>
</aside>