<?php

use App\Models\Bot;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\BotConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('admin может всё', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $bot = Bot::factory()->create();
    $conn = BotConnection::factory()->for($bot)->create();

    expect($admin->can('view', $conn))->toBeTrue();
    expect($admin->can('update', $conn))->toBeTrue();
    expect($admin->can('delete', $conn))->toBeTrue();
});

it('editor управляет своими ботами', function () {
    $editor = User::factory()->create(['role' => UserRole::Editor]);
    $own = Bot::factory()->create(['created_by' => $editor->id]);
    $other = Bot::factory()->create();
    $ownConn = BotConnection::factory()->for($own)->create();
    $otherConn = BotConnection::factory()->for($other)->create();

    expect($editor->can('update', $ownConn))->toBeTrue();
    expect($editor->can('update', $otherConn))->toBeFalse();
});

it('viewer только читает', function () {
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);
    $bot = Bot::factory()->create();
    $conn = BotConnection::factory()->for($bot)->create();

    expect($viewer->can('view', $conn))->toBeTrue();
    expect($viewer->can('update', $conn))->toBeFalse();
});
