@once
<link rel="stylesheet" href="{{ asset('css/alerts.css') }}?v={{ filemtime(public_path('css/alerts.css')) }}">
<dialog id="artemis-alert" class="artemis-alert" aria-labelledby="artemis-alert-title" aria-describedby="artemis-alert-message">
    <div class="artemis-alert-mark" aria-hidden="true">i</div>
    <h2 id="artemis-alert-title"></h2>
    <p id="artemis-alert-message"></p>
    <div class="artemis-alert-actions">
        <button type="button" data-alert-cancel>Cancelar</button>
        <button type="button" data-alert-accept>Aceptar</button>
    </div>
</dialog>
@foreach(['success', 'status', 'error'] as $key)
    @if(session($key))<div hidden data-alert-message data-alert-kind="{{ $key === 'error' ? 'error' : 'info' }}">{{ session($key) }}</div>@endif
@endforeach
@if($errors->any())<div hidden data-alert-message data-alert-kind="error">{{ implode("\n", $errors->all()) }}</div>@endif
<script src="{{ asset('js/alerts.js') }}?v={{ filemtime(public_path('js/alerts.js')) }}"></script>
@endonce
