<?php

namespace Tests\Feature;

use App\Models\CommitteeMember;
use App\Models\Grade;
use App\Models\User;
use App\Services\MemberCsvImport;
use App\Services\MemberDirectory;
use Database\Seeders\CommitteeTypeSeeder;
use Database\Seeders\ManualPhasesSeeder;
use Database\Seeders\ManualTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MemberDirectoryImportTest extends TestCase
{
    use RefreshDatabase;

    private Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->grade = Grade::create(['code' => 77, 'grade_name' => 'Grado de prueba']);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('catalogs.manage', 'web'));
        $this->actingAs($user);
    }

    private function data(string $cedula = '0012345678'): array
    {
        return ['grades_id' => $this->grade->id, 'full_name' => 'Pérez Ana María', 'identification' => $cedula, 'cellphone' => '0991234567'];
    }

    private function csv(string $rows, string $header = 'cedula,nombre,grado_codigo,celular'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('integrantes.csv', $header."\n".$rows);
    }

    public function test_listing_paginates_filters_words_and_uses_grade_id(): void
    {
        for ($i = 0; $i < 31; $i++) {
            CommitteeMember::create($this->data(str_pad((string) $i, 10, '0', STR_PAD_LEFT)));
        }
        $this->get(route('members.index'))->assertOk()->assertViewHas('committeeMembers', function ($members) {
            return $members->count() === 25 && $members->total() === 31 && $members->first()->grade === 'Grado de prueba';
        })->assertSee('page=2', false);
        $this->get(route('members.index', ['q' => 'María Pérez']))->assertOk()->assertViewHas('committeeMembers', fn ($members) => $members->total() === 31);
        $this->get(route('members.index', ['q' => '000000003']))->assertOk()->assertViewHas('committeeMembers', fn ($members) => $members->total() === 1);
        $this->get(route('members.index', ['q' => 'No existe']))->assertOk()->assertSee('No se encontraron integrantes');
    }

    public function test_manual_count_is_distinct_across_committees(): void
    {
        $this->seed([ManualPhasesSeeder::class, ManualTypesSeeder::class, CommitteeTypeSeeder::class]);
        $member = CommitteeMember::create($this->data());
        $manualId = DB::table('manuals')->insertGetId(['manual_name' => 'Manual de prueba', 'manual_types_id' => 1, 'manual_phases_id' => 1]);
        foreach ([1, 2] as $type) {
            DB::table('manual_committee_members')->insert(['manuals_id' => $manualId, 'committee_members_id' => $member->id, 'committee_type_id' => $type]);
        }
        $this->assertSame(1, (int) app(MemberDirectory::class)->listing()->first()->manuals);
    }

    public function test_individual_validation_preserves_cedula_and_grade_id(): void
    {
        $this->post(route('members.store'), $this->data())->assertSessionHasNoErrors()->assertRedirect(route('members.index'));
        $this->assertDatabaseHas('committee_members', ['identification' => '0012345678', 'grades_id' => $this->grade->id]);
        $this->post(route('members.store'), $this->data())->assertSessionHasErrors('identification');
        $this->post(route('members.store'), array_replace($this->data(), ['grades_id' => 77, 'identification' => '0012345679']))->assertSessionHasErrors('grades_id');
        $this->post(route('members.store'), array_replace($this->data(), ['full_name' => '', 'identification' => 'ABC']))->assertSessionHasErrors(['full_name', 'identification']);
        $this->assertDatabaseCount('committee_members', 1);
    }

    public function test_preview_classifies_existing_duplicate_and_invalid_without_writes(): void
    {
        CommitteeMember::create($this->data());
        $preview = app(MemberCsvImport::class)->preview($this->csv("0012345678,Otro nombre,77,\n0012345679,Nuevo,77,+593 99 123\n0012345679,Repetido,77,\n123,Inválido,77,\n0012345680,Grado inválido,99,\n"));
        $this->assertSame(['new' => 1, 'existing' => 1, 'duplicate' => 1, 'error' => 2], $preview['summary']);
        $this->assertSame('0012345679', $preview['rows'][1]['data']['identification']);
        $this->assertDatabaseCount('committee_members', 1);
    }

    public function test_semicolon_bom_and_quoted_names_are_supported(): void
    {
        $preview = app(MemberCsvImport::class)->preview($this->csv('0012345678;"Pérez, Ana";77;', "\xEF\xBB\xBFcedula;nombre;grado_codigo;celular"));
        $this->assertSame(1, $preview['summary']['new']);
        $this->assertSame('Pérez, Ana', $preview['rows'][0]['data']['full_name']);
    }

    public function test_bad_headers_and_empty_files_are_rejected(): void
    {
        foreach ([$this->csv('', 'bad,header'), UploadedFile::fake()->createWithContent('empty.csv', '')] as $file) {
            try {
                app(MemberCsvImport::class)->preview($file);
                $this->fail('Expected CSV validation failure');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('csv', $e->errors());
            }
        }
    }

    public function test_commit_adds_only_new_and_rechecks_database_after_preview(): void
    {
        $service = app(MemberCsvImport::class);
        $preview = $service->preview($this->csv("0012345678,Nuevo,77,\n0012345679,Segundo,77,\n0012345679,Repetido,77,\n123,Inválido,77,\n"));
        CommitteeMember::create($this->data());
        $userCount = User::count();
        $this->assertSame(['inserted' => 1, 'omitted' => 2, 'errors' => 1], $service->commit($preview));
        $this->assertSame(['inserted' => 0, 'omitted' => 3, 'errors' => 1], $service->commit($preview));
        $this->assertSame('Pérez Ana María', CommitteeMember::where('identification', '0012345678')->first()->full_name);
        $this->assertSame($userCount, User::count());
        $this->assertDatabaseCount('committee_members', 2);
    }

    public function test_batch_is_rolled_back_when_grade_catalog_changes(): void
    {
        $other = Grade::create(['code' => 88, 'grade_name' => 'Otro grado']);
        $service = app(MemberCsvImport::class);
        $preview = $service->preview($this->csv("0012345678,Nuevo,77,\n0012345679,Segundo,88,\n"));
        $other->delete();
        try {
            $service->commit($preview);
            $this->fail('Expected catalog change validation failure');
        } catch (ValidationException $e) {
            $this->assertDatabaseCount('committee_members', 0);
        }
    }

    public function test_http_import_preview_commit_and_token_replay(): void
    {
        $this->get(route('members.import'))->assertOk()->assertSee('Revisar archivo');
        $this->post(route('members.import.preview'), ['csv' => $this->csv("0012345678,Nuevo,77,\n")])->assertRedirect(route('members.import'));
        $this->assertDatabaseCount('committee_members', 0);
        $this->get(route('members.import'))->assertOk()->assertSee('Incorporar 1 integrantes nuevos');
        $token = session('member_csv_preview.token');
        $this->post(route('members.import.commit'), ['token' => 'forged'])->assertStatus(419);
        $this->post(route('members.import.commit'), ['token' => $token])->assertRedirect(route('members.import'))->assertSessionHas('import_result', ['inserted' => 1, 'omitted' => 0, 'errors' => 0]);
        $this->post(route('members.import.commit'), ['token' => $token])->assertStatus(419);
        $this->assertDatabaseCount('committee_members', 1);
    }

    public function test_users_without_catalog_permission_cannot_import(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('members.import'))->assertForbidden();
        $this->post(route('members.import.preview'), ['csv' => $this->csv("0012345678,Nuevo,77,\n")])->assertForbidden();
        $this->post(route('members.import.commit'), ['token' => 'fake'])->assertForbidden();
        $this->assertDatabaseCount('committee_members', 0);
    }

    public function test_preview_expiry_new_upload_and_file_limits_are_enforced(): void
    {
        $this->post(route('members.import.preview'), ['csv' => $this->csv("0012345678,Nuevo,77,\n")])->assertSessionHasNoErrors();
        $preview = session('member_csv_preview');
        $preview['created_at'] = time() - 3601;
        $this->withSession(['member_csv_preview' => $preview])->post(route('members.import.commit'), ['token' => $preview['token']])
            ->assertRedirect(route('members.import'))->assertSessionHasErrors('csv');
        $this->assertNull(session('member_csv_preview'));
        $this->post(route('members.import.preview'), ['csv' => $this->csv("0012345678,Nuevo,77,\n")])->assertSessionHasNoErrors();
        $this->post(route('members.import.preview'), ['csv' => UploadedFile::fake()->create('big.csv', 8193)])
            ->assertSessionHasErrors('csv');
        $this->assertNull(session('member_csv_preview'));
        $this->assertDatabaseCount('committee_members', 0);
    }

    public function test_invalid_encoding_and_excessive_rows_are_rejected(): void
    {
        foreach ([$this->csv("0012345678,Pe\xF1a,77,\n"), $this->csv(str_repeat("0012345678,Nuevo,77,\n", 30001))] as $file) {
            try {
                app(MemberCsvImport::class)->preview($file);
                $this->fail('Expected encoding or row-limit failure');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('csv', $e->errors());
            }
        }
        $this->assertDatabaseCount('committee_members', 0);
    }

    public function test_http_import_accepts_and_commits_30000_rows(): void
    {
        $lines = [];
        for ($i = 0; $i < 30000; $i++) {
            $lines[] = sprintf('%010d,Persona %05d,77,', 2000000000 + $i, $i);
        }

        $this->post(route('members.import.preview'), ['csv' => $this->csv(implode("\n", $lines))])
            ->assertRedirect(route('members.import'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('committee_members', 0);
        $preview = session('member_csv_preview');
        $this->assertSame(30000, $preview['summary']['new']);

        $this->get(route('members.import'))->assertOk()->assertSee('Máximo 8 MB y 30.000 filas por archivo.');
        $this->post(route('members.import.commit'), ['token' => $preview['token']])
            ->assertRedirect(route('members.import'))
            ->assertSessionHas('import_result', ['inserted' => 30000, 'omitted' => 0, 'errors' => 0]);
        $this->assertDatabaseCount('committee_members', 30000);
    }

    public function test_directory_with_30000_people_uses_bounded_queries(): void
    {
        if (! getenv('RUN_MEMBER_VOLUME_BENCHMARK')) {
            $this->markTestSkipped('Medición de 30.000 personas solo con RUN_MEMBER_VOLUME_BENCHMARK=1.');
        }
        $data = [];
        for ($i = 0; $i < 30000; $i++) {
            $data[] = ['grades_id' => $this->grade->id, 'full_name' => sprintf('Persona Prueba %05d', $i), 'identification' => sprintf('%010d', $i)];
            if (count($data) === 500) {
                DB::table('committee_members')->insert($data);
                $data = [];
            }
        }
        DB::enableQueryLog();
        $before = hrtime(true);
        $legacy = CommitteeMember::join('grades as G', 'committee_members.grades_id', '=', 'G.id')
            ->leftJoin('manual_committee_members as MCM', 'committee_members.id', '=', 'MCM.committee_members_id')
            ->select('committee_members.id', 'committee_members.full_name', 'committee_members.identification', 'G.grade_name')
            ->selectRaw('COUNT(MCM.id) as manuals')->groupBy('committee_members.id', 'committee_members.full_name', 'committee_members.identification', 'G.grade_name')
            ->orderByDesc('G.code')->orderBy('committee_members.full_name')->get();
        $legacyMs = (hrtime(true) - $before) / 1e6;
        $legacyQueries = count(DB::getQueryLog());
        $this->assertCount(30000, $legacy);
        unset($legacy);
        DB::flushQueryLog();
        $start = hrtime(true);
        $page = app(MemberDirectory::class)->listing()->paginate(25);
        $newMs = (hrtime(true) - $start) / 1e6;
        $this->assertSame(30000, $page->total());
        $this->assertCount(25, $page);
        $this->assertCount(2, DB::getQueryLog());
        DB::flushQueryLog();
        $searchStart = hrtime(true);
        $matches = app(MemberDirectory::class)->search(CommitteeMember::with('grade'), '000002999')->orderBy('full_name')->simplePaginate(25);
        $searchMs = (hrtime(true) - $searchStart) / 1e6;
        $this->assertCount(10, $matches);
        $this->assertCount(2, DB::getQueryLog());
        DB::disableQueryLog();
        fwrite(STDOUT, sprintf("\nMember directory SQLite memory 30000: legacy %.2f ms / %d queries / 30000 hydrated; paged %.2f ms / 2 queries / 25 hydrated; prefix %.2f ms / 2 queries / 10 hydrated.\n", $legacyMs, $legacyQueries, $newMs, $searchMs));
    }
}
