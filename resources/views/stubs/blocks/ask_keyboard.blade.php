        $this->ask(
            Message::make({!! \App\Services\CodeGenerator\CodeHelper::renderText($params['text']) !!})
                ->keyboard([
@foreach($params['buttons'] as $button)
                    ['label' => '{!! addslashes($button['label']) !!}', 'action' => '{!! addslashes($button['action']) !!}'],
@endforeach
                ])
        );
