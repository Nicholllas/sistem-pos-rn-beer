@if (session('error'))
    <div class="alert app-alert app-alert-danger alert-dismissible fade show" role="alert">
        <div class="app-alert-indicator"></div>

        <div class="app-alert-content">
            <strong>Tindakan tidak dapat diproses</strong>
            <span>{{ session('error') }}</span>
        </div>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif
