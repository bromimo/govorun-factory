<?php

use App\Services\CodeGenerator\FlowGenerator;

test('generates step-based flow from linear graph', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_name', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Your name?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'save_name', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'name', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'reply_thanks', 'type' => 'reply', 'data' => ['text' => 'Thanks, {{name}}!', 'media' => null, 'keyboard' => null], 'position' => ['x' => 0, 'y' => 300]],
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
    expect($result)->toContain('use Govorun\Messaging\IncomingMessage;');
    expect($result)->not->toContain('use Govorun\Messaging\Media;');
    expect($result)->toContain('use Govorun\Messaging\Message;');
    expect($result)->not->toContain('use Govorun\Messaging\Keyboard;');
    expect($result)->toContain('public function askYourNameStep(Step $step): void');
    expect($result)->toContain("\$step->ask(\n            Message::make('Your name?')\n                ->parseMode('HTML')\n        );");
    expect($result)->toContain('$step->receive(function (IncomingMessage $message)');
    expect($result)->toContain("\$this->state->set('name', \$message->text)");
    expect($result)->toContain("Message::make('Thanks, ' . \$this->state->get('name') . '!')");
    expect($result)->toContain('$this->completeFlow()');
    expect($result)->not->toContain('{{name}}');
});

test('generates multi-step flow', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_name', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Name?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'save_name', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'name', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'ask_age', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Age?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 300]],
            ['id' => 'save_age', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'age', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 400]],
            ['id' => 'reply_done', 'type' => 'reply', 'data' => ['text' => 'Done!', 'media' => null, 'keyboard' => null], 'position' => ['x' => 0, 'y' => 500]],
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
    expect($result)->toContain("\$step->ask(\n            Message::make('Name?')\n                ->parseMode('HTML')\n        );");
    expect($result)->toContain("\$this->state->set('name', \$message->text)");
    expect($result)->toContain("\$step->ask(\n            Message::make('Age?')\n                ->parseMode('HTML')\n        );");
    expect($result)->toContain("\$this->state->set('age', \$message->text)");
    expect($result)->toContain("Message::make('Done!')");
});

test('generates onComplete with body', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Ready?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'complete', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'bye', 'type' => 'reply', 'data' => ['text' => 'Goodbye!', 'media' => null, 'keyboard' => null], 'position' => ['x' => 0, 'y' => 300]],
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
    expect($result)->toContain("Message::make('Goodbye!')");
});

test('generates fluent Validator chain in receive callback', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_email', 'type' => 'ask', 'data' => [
                'mode' => 'text',
                'stepName' => '',
                'text' => 'Your email?',
                'media' => null,
                'keyboard' => null,
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
            ['id' => 'ask', 'type' => 'ask', 'data' => [
                'mode' => 'text',
                'stepName' => '',
                'text' => 'Email?',
                'media' => null,
                'keyboard' => null,
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
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Hi {{name}}, your age?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
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
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Hi?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
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
            ['id' => 'ask_g', 'type' => 'ask', 'data' => [
                'mode' => 'callback',
                'stepName' => 'askGender',
                'text' => 'Пол?',
                'media' => null,
                'validation' => [],
                'keyboard' => [
                    'type' => 'inline',
                    'buttons' => [
                        [
                            ['type' => 'action', 'label' => 'М', 'action' => 'man'],
                            ['type' => 'action', 'label' => 'Ж', 'action' => 'woman'],
                        ],
                    ],
                ],
            ], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'cond', 'type' => 'condition', 'data' => ['field' => 'message.action'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_m', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => 'askMan', 'text' => 'Мужской', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_w', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => 'askWoman', 'text' => 'Женский', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 0]],
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
    expect($result)->toContain("'man' => (function () { \$this->nextStep('askMan'); })()");
    expect($result)->toContain("'woman' => (function () { \$this->nextStep('askWoman'); })()");
    expect($result)->toContain('default => null');
    expect($result)->not->toContain("\$this->nextStep();\n");
});

test('condition branch without ask runs inline actions then completeFlow', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => [
                'mode' => 'callback',
                'stepName' => 'askConfirm',
                'text' => 'OK?',
                'media' => null,
                'validation' => [],
                'keyboard' => ['type' => 'inline', 'buttons' => [[['type' => 'action', 'label' => 'Y', 'action' => 'y']]]],
            ], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'cond', 'type' => 'condition', 'data' => ['field' => 'message.action'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'save', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'confirmed', 'source' => 'message.action']]], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'reply', 'type' => 'reply', 'data' => ['text' => 'Спасибо!', 'media' => null, 'keyboard' => null], 'position' => ['x' => 0, 'y' => 0]],
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
    expect($result)->toContain("Message::make('Спасибо!')");
    expect($result)->toContain('$this->completeFlow();');
});

test('condition branch directly to on_complete calls completeFlow', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => [
                'mode' => 'callback',
                'stepName' => 'askQ',
                'text' => 'q',
                'media' => null,
                'validation' => [],
                'keyboard' => ['type' => 'inline', 'buttons' => [[['type' => 'action', 'label' => 'S', 'action' => 'stop']]]],
            ], 'position' => ['x' => 0, 'y' => 0]],
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

    expect($result)->toContain("'stop' => (function () { \$this->completeFlow(); })()");

});

test('converging branches generate shared tail method', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_g', 'type' => 'ask', 'data' => [
                'mode' => 'callback',
                'stepName' => 'askGender',
                'text' => 'Пол?',
                'media' => null,
                'validation' => [],
                'keyboard' => ['type' => 'inline', 'buttons' => [[['type' => 'action', 'label' => 'М', 'action' => 'm'], ['type' => 'action', 'label' => 'Ж', 'action' => 'w']]]],
            ], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'cond', 'type' => 'condition', 'data' => ['field' => 'message.action'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_m', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => 'askMan', 'text' => 'М?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_w', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => 'askWoman', 'text' => 'Ж?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'common', 'type' => 'reply', 'data' => ['text' => 'Спасибо!', 'media' => null, 'keyboard' => null], 'position' => ['x' => 0, 'y' => 0]],
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
    expect($result)->toContain('$this->tail1();');
    expect($result)->toContain("Message::make('Спасибо!')");
    expect($result)->toContain('$this->completeFlow();');
    $defCount = substr_count($result, 'private function tail1(): void');
    expect($defCount)->toBe(1);
});

test('explicit stepName overrides autogenerated', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => 'askEmailCustom', 'text' => 'Введите email', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 0]],
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
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Имя?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 0]],
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
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Q', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 0]],
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
            ['id' => 'ask', 'type' => 'ask', 'data' => [
                'mode' => 'text',
                'stepName' => '',
                'text' => 'Нравится?',
                'media' => ['type' => 'photo', 'url' => 'https://example.com/a.jpg'],
                'validation' => [],
                'keyboard' => null,
            ], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('PhotoFlow', $graph, [], false);

    expect($result)->toContain("        \$step->ask(\n            Media::photo('https://example.com/a.jpg')\n                ->caption('Нравится?')\n                ->parseMode('HTML')\n        );");
    expect($result)->not->toContain("\$step->ask('Нравится?')");
    expect($result)->not->toContain("Media::photo('https://example.com/a.jpg')->caption(");
});

test('ask_text without image keeps string ask', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Возраст?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('AgeFlow', $graph, [], false);

    expect($result)->toContain("\$step->ask(\n            Message::make('Возраст?')\n                ->parseMode('HTML')\n        );");
    expect($result)->not->toContain('Media::photo');
});

test('ask_keyboard with image generates Media::photo with caption and keyboard', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => [
                'mode' => 'callback',
                'stepName' => '',
                'text' => 'Выбор?',
                'media' => ['type' => 'photo', 'url' => 'https://example.com/b.jpg'],
                'validation' => [],
                'keyboard' => [
                    'type' => 'inline',
                    'buttons' => [
                        [
                            ['type' => 'action', 'label' => 'Да', 'action' => 'yes'],
                            ['type' => 'action', 'label' => 'Нет', 'action' => 'no'],
                        ],
                    ],
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

    expect($result)->toContain("Media::photo('https://example.com/b.jpg')\n                ->caption('Выбор?')\n                ->parseMode('HTML')\n                ->keyboard(\n                    Keyboard::make()->buttons([\n");
    expect($result)->toContain("Button::make('Да')->action('yes')");
    expect($result)->toContain("Button::make('Нет')->action('no')");
    expect($result)->toContain('use Govorun\Messaging\Button;');
});

test('reply_media type=photo with caption generates Media::photo with caption', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Имя?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'save', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'name', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'media', 'type' => 'reply', 'data' => ['text' => 'Привет, {{ name }}!', 'media' => ['type' => 'photo', 'url' => 'https://example.com/pic.jpg'], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 300]],
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

    expect($result)->toContain("            \$this->send(\n                Media::photo('https://example.com/pic.jpg')\n                    ->caption('Привет, ' . \$this->state->get('name') . '!')\n                    ->parseMode('HTML')\n            );");
});

test('reply_media type=photo without caption generates Media::photo without caption chain', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Готов?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'media', 'type' => 'reply', 'data' => ['text' => null, 'media' => ['type' => 'photo', 'url' => 'https://example.com/x.jpg'], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 200]],
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

    expect($result)->toContain("\$this->send(\n                Media::photo('https://example.com/x.jpg')\n            );");
    expect($result)->not->toContain('->caption');
});

test('длинный текст в reply_text разбивается по конкатенации/пробелу при генерации', function () {
    $longText = str_repeat('очень длинное приветственное слово ', 10);
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Имя?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'save', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'name', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'reply', 'type' => 'reply', 'data' => ['text' => $longText.'{{name}}!', 'media' => null, 'keyboard' => null], 'position' => ['x' => 0, 'y' => 300]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 400]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'save'],
            ['source' => 'save', 'target' => 'reply'],
            ['source' => 'reply', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('LongTextFlow', $graph, [], false);

    foreach (explode("\n", $result) as $line) {
        expect(mb_strlen($line))->toBeLessThanOrEqual(120, "строка длиннее 120: {$line}");
    }

    $tmp = tempnam(sys_get_temp_dir(), 'flow_long_').'.php';
    file_put_contents($tmp, $result);
    exec('php -l '.escapeshellarg($tmp).' 2>&1', $out, $exit);
    unlink($tmp);
    expect($exit)->toBe(0, 'php -l failed: '.implode("\n", $out));

    expect($result)->toContain("\$this->state->get('name')");
});

test('reply_media non-photo type generates unsupported comment', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'OK?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'media', 'type' => 'reply', 'data' => ['text' => null, 'media' => ['type' => 'video', 'url' => 'https://example.com/v.mp4'], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 200]],
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

    expect($result)->toContain("Media::video('https://example.com/v.mp4')");
    expect($result)->not->toContain('Media::photo');
});

test('ask_keyboard с двумя рядами разных размеров', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            [
                'id' => 'ask',
                'type' => 'ask',
                'data' => [
                    'mode' => 'callback',
                    'stepName' => '',
                    'text' => 'Выберите услугу',
                    'media' => null,
                    'validation' => [],
                    'keyboard' => [
                        'type' => 'inline',
                        'buttons' => [
                            [
                                ['type' => 'action', 'label' => '💅 маникюр', 'action' => 'manicure'],
                                ['type' => 'action', 'label' => '🦶 педикюр', 'action' => 'pedicure'],
                            ],
                            [
                                ['type' => 'action', 'label' => '🤚 наращивание', 'action' => 'nails'],
                            ],
                        ],
                    ],
                ],
                'position' => ['x' => 0, 'y' => 100],
            ],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('ServiceFlow', $graph, [], false);

    expect($result)->toContain('use Govorun\Messaging\Button;');
    expect($result)->toContain('use Govorun\Messaging\Keyboard;');
    expect($result)->toContain("Button::make('💅 маникюр')->action('manicure')");
    expect($result)->toContain("Button::make('🤚 наращивание')->action('nails')");
    expect($result)->toContain('$step->ask(');
    expect($result)->toContain('Keyboard::make()->buttons([');
});

test('reply_keyboard со всеми типами кнопок', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            [
                'id' => 'ask',
                'type' => 'ask',
                'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Имя?', 'media' => null, 'validation' => [], 'keyboard' => null],
                'position' => ['x' => 0, 'y' => 100],
            ],
            [
                'id' => 'reply',
                'type' => 'reply',
                'data' => [
                    'text' => 'Меню',
                    'media' => null,
                    'keyboard' => [
                        'type' => 'inline',
                        'buttons' => [
                            [
                                ['type' => 'action', 'label' => 'Записаться', 'action' => 'book'],
                                ['type' => 'url', 'label' => 'Сайт', 'url' => 'https://example.com'],
                            ],
                            [
                                ['type' => 'contact', 'label' => 'Мой номер'],
                                ['type' => 'location', 'label' => 'Где я'],
                            ],
                        ],
                    ],
                ],
                'position' => ['x' => 0, 'y' => 200],
            ],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'reply'],
            ['source' => 'reply', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('MenuFlow', $graph, [], false);

    expect($result)->toContain("Button::make('Записаться')->action('book')");
    expect($result)->toContain("Button::make('Сайт')->url('https://example.com')");
    expect($result)->toContain("Button::make('Мой номер')->requestContact()");
    expect($result)->toContain("Button::make('Где я')->requestLocation()");
    expect($result)->toContain('use Govorun\Messaging\Button;');
});

test('action с param рендерится со вторым аргументом', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            [
                'id' => 'ask',
                'type' => 'ask',
                'data' => [
                    'mode' => 'callback',
                    'stepName' => '',
                    'text' => 'Выбор',
                    'media' => null,
                    'validation' => [],
                    'keyboard' => [
                        'type' => 'inline',
                        'buttons' => [[
                            ['type' => 'action', 'label' => 'A', 'action' => 'pick', 'param' => ['id' => 1]],
                        ]],
                    ],
                ],
                'position' => ['x' => 0, 'y' => 100],
            ],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('ParamFlow', $graph, [], false);

    expect($result)->toContain("Button::make('A')->action('pick', ['id' => 1])");
});

test('reply text only generates Message with parseMode HTML', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Q?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'reply', 'type' => 'reply', 'data' => ['text' => '<b>Привет</b>!', 'media' => null, 'keyboard' => null], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'reply'],
            ['source' => 'reply', 'target' => 'end'],
        ],
    ];

    $result = (new FlowGenerator)->generate('HtmlFlow', $graph, [], false);

    expect($result)->toContain("->parseMode('HTML')");
    expect($result)->toContain("Message::make(");
});

test('ask with text generates ask with parseMode HTML', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => '', 'text' => '<i>Введите</i> имя?', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 200]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'end'],
        ],
    ];

    $result = (new FlowGenerator)->generate('ItalicAskFlow', $graph, [], false);

    expect($result)->toContain("\$step->ask(");
    expect($result)->toContain("->parseMode('HTML')");
});

test('reply media without text does not generate parseMode', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask', 'data' => ['mode' => 'text', 'stepName' => 'askPhoto', 'text' => '', 'media' => null, 'validation' => [], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'reply', 'type' => 'reply', 'data' => ['text' => null, 'media' => ['type' => 'photo', 'url' => 'https://example.com/img.jpg'], 'keyboard' => null], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'reply'],
            ['source' => 'reply', 'target' => 'end'],
        ],
    ];

    $result = (new FlowGenerator)->generate('PhotoOnlyFlow2', $graph, [], false);

    expect($result)->not->toContain("->parseMode(");
    expect($result)->toContain("Media::photo(");
});