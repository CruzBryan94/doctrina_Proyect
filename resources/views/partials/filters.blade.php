<form class="filters" method="GET" action="{{ $action }}">
@if($matrix ?? false)<input type="hidden" name="phase" value="{{ $phase->slug }}"><input type="hidden" name="published" value="{{ request()->boolean('published') ? 1 : 0 }}">@endif
<div><label for="filter-q">Buscar manual</label><input class="form-control" id="filter-q" name="q" value="{{ request('q') }}" placeholder="Nombre del manual"></div>
<div><label for="filter-map">Mapa doctrinario</label><select class="form-select" id="filter-map" name="map"><option value="">Todos los mapas</option>@foreach($maps as $map)<option value="{{ $map->id }}" @selected(request('map')==$map->id)>{{ $map->name }}</option>@endforeach</select></div>
<div><label for="filter-type">Tipo</label><select class="form-select" id="filter-type" name="type"><option value="">Todos los tipos</option>@foreach($maps as $map)@foreach($map->manualTypes as $type)<option value="{{ $type->id }}" @selected(request('type')==$type->id)>{{ $type->code }}</option>@endforeach @endforeach</select></div>
@if(!($matrix ?? false))<div><label for="filter-phase">Fase</label><select class="form-select" id="filter-phase" name="phase"><option value="">Todas las fases</option>@foreach($phases as $filterPhase)<option value="{{ $filterPhase->slug }}" @selected(request('phase')===$filterPhase->slug)>{{ $filterPhase->phase_name }}</option>@endforeach</select></div>@endif
<button class="btn btn-primary" type="submit">Filtrar</button><a class="btn btn-light" href="{{ $action }}{{ ($matrix ?? false) ? '?phase='.$phase->slug : '' }}">Limpiar</a>
</form>

