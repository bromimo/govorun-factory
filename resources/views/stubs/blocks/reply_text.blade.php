        $this->reply({!! \App\Services\CodeGenerator\CodeHelper::renderText($params['text']) !!});
