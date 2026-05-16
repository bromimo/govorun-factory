{{-- Требует govorun/framework с трейтом MakesHttpCalls (реализован отдельно) --}}
$response = $this->http()->connection('{!! $slug !!}')->{!! $method !!}({!! $pathExpr !!}@if($query !== '[]'), [
    'query' => {!! $query !!},
    'headers' => {!! $headers !!},
    @if($bodyMode === 'json' && $bodyExpr !== 'null')'json' => {!! $bodyExpr !!},
    @elseif($bodyMode === 'form')'form_params' => {!! $bodyExpr !!},
    @endif
]@endif);

@if ($onError === 'stop_flow')
if ($response->failed()) {
    return $this->cancel();
}
@elseif ($onError === 'continue')
if ($response->failed()) {
    $this->state->set('api_error', ['status' => $response->status(), 'message' => $response->body()]);
}
@elseif ($onError === 'branch' && $onErrorTarget)
if ($response->failed()) {
    $this->goTo('{!! $onErrorTarget !!}');
    return;
}
@endif

@if ($mapping)
if ($response->successful()) {
@foreach ($mapping as $m)
    $this->state->set('{!! $m['state_key'] !!}', data_get($response->json(), '{!! $m['json_path'] !!}'));
@endforeach
}
@endif