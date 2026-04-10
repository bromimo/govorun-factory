        match ($this->message->{{ $params['field'] ?? 'action' }}) {
@foreach($branches as $label => $branchCode)
            '{{ $label }}' => (function() {
{!! $branchCode !!}
            })(),
@endforeach
@if($defaultBranch)
            default => (function() {
{!! $defaultBranch !!}
            })(),
@endif
        };
