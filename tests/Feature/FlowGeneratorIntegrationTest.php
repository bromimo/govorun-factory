<?php

use App\Services\CodeGenerator\FlowGenerator;

test('spec example: flow 2 (Gender branching) generates expected structure', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_name', 'type' => 'ask_text', 'data' => ['text' => 'Как вас зовут?', 'stepName' => 'askName'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'save_name', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'name', 'source' => 'message.text'], ['key' => 'user_id', 'source' => 'user.id']]], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'reply_hi', 'type' => 'reply_text', 'data' => ['text' => 'Привет, {{name}} {{message.user.firstName}}!'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_g', 'type' => 'ask_keyboard', 'data' => ['text' => 'Выберите свой пол', 'stepName' => 'askGender', 'buttons' => [['label' => 'мужской', 'action' => 'man'], ['label' => 'женский', 'action' => 'woman']]], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'cond', 'type' => 'condition', 'data' => ['field' => 'message.action'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_m', 'type' => 'ask_text', 'data' => ['text' => 'Мужской вопрос', 'stepName' => 'askManAnswer'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'save_ans', 'type' => 'save_state', 'data' => ['variables' => [['key' => 'answer', 'source' => 'message.text']]], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'reply_ans', 'type' => 'reply_text', 'data' => ['text' => '{{name}}, вы ответили {{answer}}'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'ask_w', 'type' => 'ask_text', 'data' => ['text' => 'Женский вопрос', 'stepName' => 'askWomanAnswer'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'end_m', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'end_w', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask_name'],
            ['source' => 'ask_name', 'target' => 'save_name'],
            ['source' => 'save_name', 'target' => 'reply_hi'],
            ['source' => 'reply_hi', 'target' => 'ask_g'],
            ['source' => 'ask_g', 'target' => 'cond'],
            ['source' => 'cond', 'target' => 'ask_m', 'label' => 'man'],
            ['source' => 'cond', 'target' => 'ask_w', 'label' => 'woman'],
            ['source' => 'ask_m', 'target' => 'save_ans'],
            ['source' => 'save_ans', 'target' => 'reply_ans'],
            ['source' => 'reply_ans', 'target' => 'end_m'],
            ['source' => 'ask_w', 'target' => 'end_w'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('GenderFlow', $graph, [], false);

    expect($result)->toContain("protected array \$steps = ['askName', 'askGender', 'askManAnswer', 'askWomanAnswer']");

    expect($result)->toContain('public function askNameStep(Step $step): void');
    expect($result)->toContain("\$this->state->set('name', \$message->text)");
    expect($result)->toContain("\$this->state->set('user_id', \$message->user->id)");
    expect($result)->toContain("\$this->nextStep('askGender'); return;");

    expect($result)->toContain('public function askGenderStep(Step $step): void');
    expect($result)->toContain('match ($message->action)');
    expect($result)->toContain("'man' => (function () { \$this->nextStep('askManAnswer'); return; })()");
    expect($result)->toContain("'woman' => (function () { \$this->nextStep('askWomanAnswer'); return; })()");
    expect($result)->toContain('default => null');

    expect($result)->toContain('public function askManAnswerStep(Step $step): void');
    expect($result)->toContain("\$this->state->set('answer', \$message->text)");
    expect($result)->toContain('$this->completeFlow(); return;');

    expect($result)->toContain('public function askWomanAnswerStep(Step $step): void');
});
