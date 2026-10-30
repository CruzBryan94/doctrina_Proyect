<?php

namespace Tests\Feature;

use App\Models\CommitteeMember;
use App\Models\User;
use Database\Seeders\Grade_Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CommitteeMemberCellphoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(Grade_Seeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('catalogs.manage', 'web'));
        $this->actingAs($user);
    }

    public function test_member_can_be_created_and_listed_with_cellphone(): void
    {
        $this->get(route('members.newMember'))->assertOk()->assertSee('name="cellphone"', false);
        $this->post(route('members.store'), $this->memberData() + [
            'cellphone' => '0991234567',
        ])->assertRedirect(route('members.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('committee_members', ['cellphone' => '0991234567']);
        $this->get(route('members.index'))->assertOk()->assertSee('0991234567');
    }

    public function test_member_can_be_created_without_cellphone(): void
    {
        $this->post(route('members.store'), $this->memberData())
            ->assertRedirect(route('members.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('committee_members', [
            'identification' => '1234567890', 'cellphone' => null,
        ]);
    }

    public function test_cellphone_can_be_updated_and_cleared(): void
    {
        $member = CommitteeMember::create($this->memberData());
        $this->post(route('members.edit', $member))->assertOk()->assertSee('name="cellphone"', false);

        $this->post(route('members.update'), $this->memberData() + [
            'id' => $member->id, 'cellphone' => '+593 99 123-4567',
        ])->assertRedirect(route('members.index'))->assertSessionHasNoErrors();
        $this->assertSame('+593 99 123-4567', $member->fresh()->cellphone);

        $this->post(route('members.update'), $this->memberData() + [
            'id' => $member->id, 'cellphone' => '',
        ])->assertRedirect(route('members.index'))->assertSessionHasNoErrors();
        $this->assertNull($member->fresh()->cellphone);
    }

    public function test_invalid_cellphones_are_rejected_without_saving(): void
    {
        foreach (['abc', str_repeat('1', 21), ['0991234567']] as $cellphone) {
            $this->post(route('members.store'), $this->memberData() + [
                'cellphone' => $cellphone,
            ])->assertSessionHasErrors('cellphone');
        }
        $this->assertDatabaseCount('committee_members', 0);

        $member = CommitteeMember::create($this->memberData() + ['cellphone' => '0991234567']);
        $this->post(route('members.update'), $this->memberData() + [
            'id' => $member->id, 'cellphone' => 'invalid',
        ])->assertSessionHasErrors('cellphone');
        $this->assertSame('0991234567', $member->fresh()->cellphone);
    }

    private function memberData(): array
    {
        return ['grades_id' => 1, 'full_name' => 'Miembro de prueba', 'identification' => '1234567890'];
    }
}
