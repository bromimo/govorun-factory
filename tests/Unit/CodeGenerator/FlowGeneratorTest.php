<?php

use App\Services\CodeGenerator\FlowGenerator;

test('generates step-based flow from linear graph', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_name', 'type' => 'ask_text', 'data' => ['text' => 'Your name?'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'save_name', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'name', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'reply_thanks', 'type' => 'reply_text', 'data' => ['text' => 'Thanks, {{name}}!'], 'position' => ['x' => 0, 'y' => 300]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 400]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask_name'],
            ['source' => 'ask_name', 'target' => 'save_name'],
            ['source' => 'save_name', 'target' => 'reply_thanks'],
            ['source' => 'reply_thanks', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('OnboardingFlow', $graph, [], false);

    expect($result)->toContain('class OnboardingFlow extends Flow');
    expect($result)->toContain("protected array \$steps = ['askYourName']");
    expect($result)->toContain('use Govorun\State\Step;');
    expect($result)->toContain('use Govorun\Messaging\Media;');
    expect($result)->toContain('use Govorun\Messaging\IncomingMessage;');
    expect($result)->toContain('public function askYourNameStep(Step $step): void');
    expect($result)->toContain("\$step->ask('Your name?')");
    expect($result)->toContain('$step->receive(function (IncomingMessage $message)');
    expect($result)->toContain("\$this->state->set('name', \$message->text)");
    expect($result)->toContain("\$this->reply('Thanks, ' . \$this->state->get('name') . '!')");
    expect($result)->toContain('$this->completeFlow()');
    expect($result)->not->toContain('{{name}}');
});

test('generates multi-step flow', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_name', 'type' => 'ask_text', 'data' => ['text' => 'Name?'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'save_name', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'name', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'ask_age', 'type' => 'ask_text', 'data' => ['text' => 'Age?'], 'position' => ['x' => 0, 'y' => 300]],
            ['id' => 'save_age', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'age', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 400]],
            ['id' => 'reply_done', 'type' => 'reply_text', 'data' => ['text' => 'Done!'], 'position' => ['x' => 0, 'y' => 500]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 600]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask_name'],
            ['source' => 'ask_name', 'target' => 'save_name'],
            ['source' => 'save_name', 'target' => 'ask_age'],
            ['source' => 'ask_age', 'target' => 'save_age'],
            ['source' => 'save_age', 'target' => 'reply_done'],
            ['source' => 'reply_done', 'target' => 'end'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('RegistrationFlow', $graph, [], false);

    expect($result)->toContain('class RegistrationFlow extends Flow');
    expect($result)->toContain("protected array \$steps = [\n        'askName',\n        'askAge',\n    ];");
    expect($result)->toContain('public function askNameStep(Step $step): void');
    expect($result)->toContain('public function askAgeStep(Step $step): void');
    expect($result)->toContain("\$step->ask('Name?')");
    expect($result)->toContain("\$this->state->set('name', \$message->text)");
    expect($result)->toContain("\$step->ask('Age?')");
    expect($result)->toContain("\$this->state->set('age', \$message->text)");
    expect($result)->toContain("\$this->reply('Done!')");
});

test('generates onComplete with body', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => ['text' => 'Ready?'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'complete', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'bye', 'type' => 'reply_text', 'data' => ['text' => 'Goodbye!'], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'complete'],
            ['source' => 'complete', 'target' => 'bye'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('FarewellFlow', $graph, [], false);

    expect($result)->toContain('public function onComplete(): void');
    expect($result)->toContain("\$this->reply('Goodbye!')");
});

test('generates fluent Validator chain in receive callback', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_email', 'type' => 'ask_text', 'data' => [
                'text' => 'Your email?',
                'validation' => [
                    ['name' => 'required', 'message' => 'Обязательное поле'],
                    ['name' => 'email'],
                    ['name' => 'max', 'params' => [255]],
                ],
            ], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'save_email', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'email', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask_email'],
            ['source' => 'ask_email', 'target' => 'save_email'],
            ['source' => 'save_email', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('EmailFlow', $graph, [], false);

    expect($result)->toContain('$this->validator($message->text)');
    expect($result)->toContain("->required('Обязательное поле')");
    expect($result)->toContain('->email()');
    expect($result)->toContain('->max(255)');
    expect($result)->toContain('->fails()');
    expect($result)->toContain('return;');
    expect($result)->toContain("\$this->state->set('email', \$message->text)");
    expect($result)->not->toContain('use Govorun\Support\Validator');
});

test('uses bot-level validation messages as defaults', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => [
                'text' => 'Email?',
                'validation' => [
                    ['name' => 'required'],
                    ['name' => 'email', 'message' => 'Кастомный email'],
                ],
            ], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'done'],
        ],
    ];

    $botMessages = ['required' => 'Заполните поле', 'email' => 'Некорректный email'];

    $generator = new FlowGenerator;
    $result = $generator->generate('BotDefaultsFlow', $graph, [], false, $botMessages);

    expect($result)->toContain("->required('Заполните поле')");
    expect($result)->toContain("->email('Кастомный email')");
});

test('interpolates state variables in text', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => ['text' => 'Hi {{name}}, your age?'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('AgeFlow', $graph, [], false);

    expect($result)->toContain("\$this->state->get('name')");
    expect($result)->not->toContain('{{name}}');
});

test('save_state generates multiple variables with correct sources', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => ['text' => 'Hi?'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'save', 'type' => 'save_state', 'data' => ['variables' => [
                ['key' => 'answer', 'source' => 'message.text'],
                ['key' => 'user_id', 'source' => 'user.id'],
            ]], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'save'],
            ['source' => 'save', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('MultiVarFlow', $graph, [], false);

    expect($result)->toContain("\$this->state->set('answer', \$message->text)");
    expect($result)->toContain("\$this->state->set('user_id', \$message->user->id)");
});

test('condition routes to named ask-steps via nextStep', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_g', 'type' => 'ask_keyboard', 'data' => [
                'text' => 'Пол?',
                'stepName' => 'askGender',
                'buttons' => [['label' => 'М', 'action' => 'man'], ['label' => 'Ж', 'action' => 'woman']],
            ], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'cond', 'type' => 'condition', 'data' => ['field' => 'message.action'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_m', 'type' => 'ask_text', 'data' => ['text' => 'Мужской', 'stepName' => 'askMan'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_w', 'type' => 'ask_text', 'data' => ['text' => 'Женский', 'stepName' => 'askWoman'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'end_m', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'end_w', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask_g'],
            ['source' => 'ask_g', 'target' => 'cond'],
            ['source' => 'cond', 'target' => 'ask_m', 'label' => 'man'],
            ['source' => 'cond', 'target' => 'ask_w', 'label' => 'woman'],
            ['source' => 'ask_m', 'target' => 'end_m'],
            ['source' => 'ask_w', 'target' => 'end_w'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('GenderFlow', $graph, [], false);

    expect($result)->toContain("protected array \$steps = [\n        'askGender',\n        'askMan',\n        'askWoman',\n    ];");
    expect($result)->toContain('public function askGenderStep(Step $step): void');
    expect($result)->toContain('public function askManStep(Step $step): void');
    expect($result)->toContain('public function askWomanStep(Step $step): void');
    expect($result)->toContain('match ($message->action)');
    expect($result)->toContain("'man' => (function () { \$this->nextStep('askMan'); return; })()");
    expect($result)->toContain("'woman' => (function () { \$this->nextStep('askWoman'); return; })()");
    expect($result)->toContain('default => null');
    expect($result)->not->toContain("\$this->nextStep();\n");
});

test('condition branch without ask runs inline actions then completeFlow', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_keyboard', 'data' => ['text' => 'OK?', 'stepName' => 'askConfirm', 'buttons' => [['label' => 'Y', 'action' => 'y']]], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'cond', 'type' => 'condition', 'data' => ['field' => 'message.action'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'save', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'confirmed', 'source' => 'message.action']]], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'reply', 'type' => 'reply_text', 'data' => ['text' => 'Спасибо!'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'cond'],
            ['source' => 'cond', 'target' => 'save', 'label' => 'y'],
            ['source' => 'save', 'target' => 'reply'],
            ['source' => 'reply', 'target' => 'end'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('ConfirmFlow', $graph, [], false);

    expect($result)->toContain("'y' => (function () {");
    expect($result)->toContain("\$this->state->set('confirmed', \$message->action)");
    expect($result)->toContain("\$this->reply('Спасибо!')");
    expect($result)->toContain('$this->completeFlow(); return;');
});

test('condition branch directly to on_complete calls completeFlow', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_keyboard', 'data' => ['text' => 'q', 'stepName' => 'askQ', 'buttons' => [['label' => 'S', 'action' => 'stop']]], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'cond', 'type' => 'condition', 'data' => ['field' => 'message.action'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'cond'],
            ['source' => 'cond', 'target' => 'end', 'label' => 'stop'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('StopFlow', $graph, [], false);

    expect($result)->toContain("'stop' => (function () { \$this->completeFlow(); return; })()");
});

test('converging branches generate shared tail method', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_g', 'type' => 'ask_keyboard', 'data' => ['text' => 'Пол?', 'stepName' => 'askGender', 'buttons' => [['label' => 'М', 'action' => 'm'], ['label' => 'Ж', 'action' => 'w']]], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'cond', 'type' => 'condition', 'data' => ['field' => 'message.action'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_m', 'type' => 'ask_text', 'data' => ['text' => 'М?', 'stepName' => 'askMan'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_w', 'type' => 'ask_text', 'data' => ['text' => 'Ж?', 'stepName' => 'askWoman'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'common', 'type' => 'reply_text', 'data' => ['text' => 'Спасибо!'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask_g'],
            ['source' => 'ask_g', 'target' => 'cond'],
            ['source' => 'cond', 'target' => 'ask_m', 'label' => 'm'],
            ['source' => 'cond', 'target' => 'ask_w', 'label' => 'w'],
            ['source' => 'ask_m', 'target' => 'common'],
            ['source' => 'ask_w', 'target' => 'common'],
            ['source' => 'common', 'target' => 'end'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('ConvergeFlow', $graph, [], false);

    expect($result)->toContain('private function tail1(): void');
    expect($result)->toContain('$this->tail1(); return;');
    expect($result)->toContain("\$this->reply('Спасибо!')");
    expect($result)->toContain('$this->completeFlow(); return;');
    $defCount = substr_count($result, 'private function tail1(): void');
    expect($defCount)->toBe(1);
});

test('explicit stepName overrides autogenerated', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => ['text' => 'Введите email', 'stepName' => 'askEmailCustom'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'end'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('EmailFlow', $graph, [], false);

    expect($result)->toContain("protected array \$steps = ['askEmailCustom']");
    expect($result)->toContain('public function askEmailCustomStep(Step $step): void');
    expect($result)->not->toContain('askVvedite');
});

test('autogenerated stepName is used when stepName is absent', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => ['text' => 'Имя?'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'end'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('NameFlow', $graph, [], false);

    expect($result)->toContain("protected array \$steps = ['askImya']");
    expect($result)->toContain('public function askImyaStep(Step $step): void');
});

test('linear flow without condition still generates valid step method', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => ['text' => 'Q'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'save', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'q', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'save'],
            ['source' => 'save', 'target' => 'end'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('LinearFlow', $graph, [], false);

    expect($result)->toContain('public function askQStep(Step $step): void');
    expect($result)->toContain("\$this->state->set('q', \$message->text)");
    expect($result)->toContain('$this->completeFlow();');
});

test('ask_text with image generates Media::photo with caption', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => ['text' => 'Нравится?', 'image' => 'https://example.com/a.jpg'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('PhotoFlow', $graph, [], false);

    expect($result)->toContain("        \$step->ask(\n            Media::photo('https://example.com/a.jpg')\n                ->caption('Нравится?')\n        );");
    expect($result)->not->toContain("\$step->ask('Нравится?')");
    expect($result)->not->toContain("Media::photo('https://example.com/a.jpg')->caption(");
});

test('ask_text without image keeps string ask', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => ['text' => 'Возраст?'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('AgeFlow', $graph, [], false);

    expect($result)->toContain("\$step->ask('Возраст?')");
    expect($result)->not->toContain('Media::photo');
});

test('ask_keyboard with image generates Media::photo with caption and keyboard', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_keyboard', 'data' => [
                'text' => 'Выбор?',
                'image' => 'https://example.com/b.jpg',
                'buttons' => [
                    ['label' => 'Да', 'action' => 'yes'],
                    ['label' => 'Нет', 'action' => 'no'],
                ],
            ], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('ChoicePhotoFlow', $graph, [], false);

    expect($result)->toContain("        \$step->ask(\n            Media::photo('https://example.com/b.jpg')\n                ->caption('Выбор?'),\n            fn () => Keyboard::make()\n");
    expect($result)->toContain("                ->button('Да', 'yes')");
    expect($result)->toContain("                ->button('Нет', 'no')");
});

test('reply_media type=photo with caption generates Media::photo with caption', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => ['text' => 'Имя?'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'save', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'name', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'media', 'type' => 'reply_media', 'data' => ['media_type' => 'photo', 'url' => 'https://example.com/pic.jpg', 'caption' => 'Привет, {{ name }}!'], 'position' => ['x' => 0, 'y' => 300]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 400]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'save'],
            ['source' => 'save', 'target' => 'media'],
            ['source' => 'media', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('GreetingFlow', $graph, [], false);

    expect($result)->toContain("            \$this->send(\n                Media::photo('https://example.com/pic.jpg')\n                    ->caption('Привет, ' . \$this->state->get('name') . '!')\n            );");
});

test('reply_media type=photo without caption generates Media::photo without caption chain', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => ['text' => 'Готов?'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'media', 'type' => 'reply_media', 'data' => ['media_type' => 'photo', 'url' => 'https://example.com/x.jpg', 'caption' => ''], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'media'],
            ['source' => 'media', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('PhotoOnlyFlow', $graph, [], false);

    expect($result)->toContain("\$this->send(Media::photo('https://example.com/x.jpg'));");
    expect($result)->not->toContain('->caption');
});

test('reply_media non-photo type generates unsupported comment', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_text', 'data' => ['text' => 'OK?'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'media', 'type' => 'reply_media', 'data' => ['media_type' => 'video', 'url' => 'https://example.com/v.mp4'], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'media'],
            ['source' => 'media', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('VideoFlow', $graph, [], false);

    expect($result)->toContain('// Unsupported media type: video');
    expect($result)->not->toContain('Media::photo');
});
