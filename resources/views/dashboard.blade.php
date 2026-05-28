<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tableau de bord - EXAD Tracking</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260527-manrope">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260527-dashboard-typography">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <a class="brand-block" href="{{ route('dashboard') }}" aria-label="EXAD Tracking">
                <img src="{{ asset('images/logo-exad-transparent.png') }}" alt="EXAD Tracking">
            </a>

            <nav class="nav flex-column dashboard-nav" aria-label="Navigation principale">
                <a class="nav-link active" href="{{ route('dashboard') }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Vue generale</span>
                </a>
                <a class="nav-link disabled" href="#" aria-disabled="true">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span>Flotte</span>
                </a>
                <a class="nav-link disabled" href="#" aria-disabled="true">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Carte</span>
                </a>
                <a class="nav-link disabled" href="#" aria-disabled="true">
                    <i class="fa-solid fa-bell"></i>
                    <span>Alertes</span>
                </a>
            </nav>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <p class="eyebrow mb-1">Centre de controle</p>
                    <h1>Tableau de bord</h1>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Deconnexion</span>
                    </button>
                </form>
            </header>

            <section class="summary-grid" aria-label="Indicateurs flotte">
                <article class="metric-card">
                    <span class="metric-icon metric-blue"><i class="fa-solid fa-microchip"></i></span>
                    <div>
                        <p>Boitiers</p>
                        <strong>{{ number_format($summary['devices_total']) }}</strong>
                    </div>
                </article>
                <article class="metric-card">
                    <span class="metric-icon metric-green"><i class="fa-solid fa-signal"></i></span>
                    <div>
                        <p>En ligne</p>
                        <strong>{{ number_format($summary['devices_online']) }}</strong>
                    </div>
                </article>
                <article class="metric-card">
                    <span class="metric-icon metric-amber"><i class="fa-solid fa-route"></i></span>
                    <div>
                        <p>En mouvement</p>
                        <strong>{{ number_format($summary['devices_moving']) }}</strong>
                    </div>
                </article>
                <article class="metric-card">
                    <span class="metric-icon metric-red"><i class="fa-solid fa-location-crosshairs"></i></span>
                    <div>
                        <p>Positions du jour</p>
                        <strong>{{ number_format($summary['positions_today']) }}</strong>
                    </div>
                </article>
            </section>

            <section class="content-grid">
                <div class="panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow mb-1">Suivi flotte</p>
                            <h2>Derniers boitiers actifs</h2>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle dashboard-table">
                            <thead>
                                <tr>
                                    <th>Boitier</th>
                                    <th>Statut</th>
                                    <th>Vitesse</th>
                                    <th>Dernier signal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($devices as $device)
                                    <tr>
                                        <td>
                                            <strong>{{ $device->name ?: 'Boitier '.$device->imei }}</strong>
                                            <span class="technical-code">{{ $device->imei }}</span>
                                        </td>
                                        <td>
                                            <span class="status-pill status-{{ $device->status }}">
                                                {{ ucfirst($device->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $device->last_speed }} km/h</td>
                                        <td>{{ $device->last_seen_at?->diffForHumans() ?? 'Aucun signal' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="empty-state">
                                            Aucun boitier enregistre pour le moment.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow mb-1">Telemetrie</p>
                            <h2>Positions recentes</h2>
                        </div>
                    </div>

                    <div class="position-list">
                        @forelse ($recentPositions as $position)
                            <article class="position-item">
                                <span class="position-marker">
                                    <i class="fa-solid fa-location-dot"></i>
                                </span>
                                <div>
                                    <strong>{{ $position->device?->name ?: $position->imei }}</strong>
                                    <p>
                                        <span class="technical-code">{{ $position->latitude ?? 'n/a' }}, {{ $position->longitude ?? 'n/a' }}</span>
                                    </p>
                                    <small>{{ $position->server_time?->format('d/m/Y H:i') }}</small>
                                </div>
                            </article>
                        @empty
                            <div class="empty-state">
                                Aucune position recue pour le moment.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
