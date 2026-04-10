        $step->ask(
            Message::make('{!! addslashes($params['text']) !!}')
                ->keyboard([
@foreach($params['buttons'] as $button)
                    ['label' => '{!! addslashes($button['label']) !!}', 'action' => '{!! addslashes($button['action']) !!}'],
@endforeach
                ])
        );
