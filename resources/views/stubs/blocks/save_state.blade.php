        $this->state->set('{!! $params['key'] !!}', $this->{!! str_replace('.', '->', $params['source'] ?? 'message->text') !!});
