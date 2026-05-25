<?php

namespace Tests\Unit;

use App\Models\Bot;
use Tests\TestCase;
use App\Models\User;
use App\Policies\BotPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BotPolicyTest extends TestCase
{
    use RefreshDatabase;

    private BotPolicy $policy;

    private User $admin;

    private User $editor;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new BotPolicy;
        $this->admin = User::factory()->admin()->create();
        $this->editor = User::factory()->editor()->create();
        $this->viewer = User::factory()->create();
    }

    public function test_any_authenticated_user_can_view_bots(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
        $this->assertTrue($this->policy->viewAny($this->editor));
        $this->assertTrue($this->policy->viewAny($this->viewer));
    }

    public function test_admin_and_editor_can_create_bots(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
        $this->assertTrue($this->policy->create($this->editor));
    }

    public function test_viewer_cannot_create_bots(): void
    {
        $this->assertFalse($this->policy->create($this->viewer));
    }

    public function test_admin_can_update_any_bot(): void
    {
        $bot = Bot::factory()->for($this->editor, 'creator')->create();
        $this->assertTrue($this->policy->update($this->admin, $bot));
    }

    public function test_editor_can_update_own_bot(): void
    {
        $bot = Bot::factory()->for($this->editor, 'creator')->create();
        $this->assertTrue($this->policy->update($this->editor, $bot));
    }

    public function test_editor_cannot_update_other_users_bot(): void
    {
        $bot = Bot::factory()->for($this->admin, 'creator')->create();
        $this->assertFalse($this->policy->update($this->editor, $bot));
    }

    public function test_viewer_cannot_update_bots(): void
    {
        $bot = Bot::factory()->for($this->admin, 'creator')->create();
        $this->assertFalse($this->policy->update($this->viewer, $bot));
    }

    public function test_only_admin_can_delete_bots(): void
    {
        $bot = Bot::factory()->for($this->editor, 'creator')->create();

        $this->assertTrue($this->policy->delete($this->admin, $bot));
        $this->assertFalse($this->policy->delete($this->editor, $bot));
        $this->assertFalse($this->policy->delete($this->viewer, $bot));
    }

    public function test_admin_and_editor_can_export(): void
    {
        $bot = Bot::factory()->for($this->editor, 'creator')->create();

        $this->assertTrue($this->policy->export($this->admin, $bot));
        $this->assertTrue($this->policy->export($this->editor, $bot));
        $this->assertFalse($this->policy->export($this->viewer, $bot));
    }
}
