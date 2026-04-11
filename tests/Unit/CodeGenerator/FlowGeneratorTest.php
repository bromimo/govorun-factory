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
    expect($result)->toContain("protected array \$steps = ['step1']");
    expect($result)->toContain('use Govorun\State\Step;');
    expect($result)->toContain('use Govorun\Messaging\IncomingMessage;');
    expect($result)->toContain('public function step1Step(Step $step): void');
    expect($result)->toContain("\$step->ask('Your name?')");
    expect($result)->toContain('$step->receive(function (IncomingMessage $message)');
    expect($result)->toContain("\$this->state->set('name', \$message->text)");
    expect($result)->toContain("\$this->reply('Thanks, ' . \$this->state->get('name') . '!')");
    expect($result)->toContain('$this->nextStep()');
    expect($result)->not->toContain('{{name}}');
});

test('generates flow with condition branching', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask', 'type' => 'ask_keyboard', 'data' => ['text' => 'Confirm?', 'buttons' => [['label' => 'Yes', 'action' => 'yes'], ['label' => 'No', 'action' => 'no']]], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'check', 'type' => 'condition', 'data' => ['field' => 'message.action'], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'ok', 'type' => 'reply_text', 'data' => ['text' => 'Done!'], 'position' => ['x' => -100, 'y' => 300]],
            ['id' => 'cancel', 'type' => 'reply_text', 'data' => ['text' => 'Cancelled'], 'position' => ['x' => 100, 'y' => 300]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 400]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask'],
            ['source' => 'ask', 'target' => 'check'],
            ['source' => 'check', 'target' => 'ok', 'label' => 'yes'],
            ['source' => 'check', 'target' => 'cancel', 'label' => 'no'],
            ['source' => 'ok', 'target' => 'end'],
            ['source' => 'cancel', 'target' => 'end'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('ConfirmFlow', $graph, ['/cancel'], true);

    expect($result)->toContain('class ConfirmFlow extends Flow');
    expect($result)->toContain("protected array \$steps = ['step1']");
    expect($result)->toContain('step1Step(Step $step)');
    expect($result)->toContain("ask('Confirm?', fn () => Keyboard::make()");
    expect($result)->toContain("->button('Yes', 'yes')");
    expect($result)->toContain("->button('No', 'no')");
    expect($result)->toContain('use Govorun\Messaging\Keyboard;');
    expect($result)->toContain('match ($message->action)');
    expect($result)->toContain("'yes' => (function ()");
    expect($result)->toContain("'no' => (function ()");
    expect($result)->toContain("\$this->reply('Done!')");
    expect($result)->toContain("\$this->reply('Cancelled')");
    expect($result)->toContain('interruptCommands');
    expect($result)->toContain('interruptOnEvent = true');
    expect($result)->toContain('$this->nextStep()');
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
    expect($result)->toContain("protected array \$steps = ['step1', 'step2']");
    expect($result)->toContain('public function step1Step(Step $step): void');
    expect($result)->toContain('public function step2Step(Step $step): void');
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

    // required: нет кастомного message → берёт из bot defaults
    expect($result)->toContain("->required('Заполните поле')");
    // email: есть кастомный message → приоритет у него
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
