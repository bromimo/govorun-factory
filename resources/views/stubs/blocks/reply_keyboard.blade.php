        $this->send(
            Message::make({!! \App\Services\CodeGenerator\CodeHelper::renderText($params['text']) !!})
                ->keyboard(
                    Keyboard::make()->buttons([
@foreach($params['buttons'] as $row)
                        [
@foreach($row as $btn)
                            {!! \App\Services\CodeGenerator\CodeHelper::renderButton($btn) !!},
@endforeach
                        ],
@endforeach
                    ])
                )
        );
