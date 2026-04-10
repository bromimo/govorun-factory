<?php

use App\Services\CodeGenerator\FlowGenerator;

test('generates flow class from linear graph', function () {
    $graph = [
        'nodes' => [
            ['id' => 'ask_name', 'type' => 'ask_text', 'data' => ['text' => 'Your name?'], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'save_name', 'type' => 'save_state', 'data' => ['key' => 'name', 'source' => 'text'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'reply_thanks', 'type' => 'reply_text', 'data' => ['text' => 'Thanks!'], 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
            ['source' => 'ask_name', 'target' => 'save_name'],
            ['source' => 'save_name', 'target' => 'reply_thanks'],
            ['source' => 'reply_thanks', 'target' => 'done'],
        ],
    ];

    $generator = new FlowGenerator;
    $result = $generator->generate('OnboardingFlow', $graph, [], false);

    expect($result)->toContain('class OnboardingFlow extends Flow');
    expect($result)->toContain("ask('Your name?')");
    expect($result)->toContain("state->set('name'");
    expect($result)->toContain("reply('Thanks!')");
});

test('generates flow with condition branching', function () {
    $graph = [
        'nodes' => [
            ['id' => 'ask', 'type' => 'ask_keyboard', 'data' => ['text' => 'Confirm?', 'buttons' => [['label' => 'Yes', 'action' => 'yes'], ['label' => 'No', 'action' => 'no']]], 'position' => ['x' => 0, 'y' => 0]],
            ['id' => 'check', 'type' => 'condition', 'data' => ['field' => 'action'], 'position' => ['x' => 0, 'y' => 100]],
            ['id' => 'ok', 'type' => 'reply_text', 'data' => ['text' => 'Done!'], 'position' => ['x' => -100, 'y' => 200]],
            ['id' => 'cancel', 'type' => 'reply_text', 'data' => ['text' => 'Cancelled'], 'position' => ['x' => 100, 'y' => 200]],
            ['id' => 'end', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
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
    expect($result)->toContain('match');
    expect($result)->toContain("'yes'");
    expect($result)->toContain("'no'");
    expect($result)->toContain('interruptCommands');
    expect($result)->toContain('interruptOnEvent = true');
});
