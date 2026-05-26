return [

    'sender_name' => {!! var_export($senderName, true) !!},

    'sender_avatar' => {!! $avatarExpr !!},

    'public_account_uri' => {!! $publicAccountUri === null ? 'null' : var_export($publicAccountUri, true) !!},

    'event_types' => [
@foreach($eventTypes as $eventType)
        '{{ $eventType }}',
@endforeach
    ],

];