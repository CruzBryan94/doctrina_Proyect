<?php

namespace App\Http\Controllers;

use App\Models\CommitteeMember;
use App\Models\Grade;
use App\Services\MemberCsvImport;
use App\Services\MemberDirectory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MembersController extends Controller
{
    public function index(Request $request, MemberDirectory $directory)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $committeeMembers = $directory->listing($data['q'] ?? '')->paginate(25)->withQueryString();

        return view('committees.members.index', compact('committeeMembers'));
    }

    public function edit($id)
    {
        $committeeMember = CommitteeMember::findOrFail($id);
        $grades = Grade::orderBy('code')->get();

        return view('committees.members.edit', compact('committeeMember', 'grades'));
    }

    public function update(Request $request)
    {
        $request->validate(['id' => ['required', 'integer', 'exists:committee_members,id']]);
        $committeeMember = CommitteeMember::findOrFail($request->integer('id'));
        $committeeMember->update($this->validatedMember($request, $committeeMember));

        return redirect()->route('members.index')->with('success', 'Miembro actualizado correctamente');
    }

    public function newMember()
    {
        $grades = Grade::orderBy('code')->get();

        return view('committees.members.newMember', compact('grades'));
    }

    public function store(Request $request)
    {
        CommitteeMember::create($this->validatedMember($request));

        return redirect()->route('members.index')->with('success', 'Miembro creado correctamente');
    }

    private function validatedMember(Request $request, ?CommitteeMember $member = null): array
    {
        return $request->validate([
            'grades_id' => ['required', 'integer', 'exists:grades,id'],
            'full_name' => ['required', 'string', 'max:250'],
            'identification' => ['required', 'string', 'regex:/^[0-9]{10}$/', Rule::unique('committee_members')->ignore($member?->id)],
            'cellphone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 ()-]*$/'],
        ], [
            'identification.regex' => 'La cédula debe contener 10 dígitos numéricos.',
            'identification.unique' => 'Ya existe un integrante con esta cédula.',
            'cellphone.regex' => 'El celular debe contener números y puede incluir + al inicio, espacios, guiones o paréntesis.',
            'cellphone.max' => 'El celular no debe superar los 20 caracteres.',
        ]);
    }

    public function importForm(Request $request)
    {
        $preview = $request->session()->get('member_csv_preview');
        $grades = Grade::orderBy('code')->get();
        $page = max(1, $request->integer('page', 1));
        $rows = $preview ? new LengthAwarePaginator(array_slice($preview['rows'], ($page - 1) * 50, 50), count($preview['rows']), 50, $page, ['path' => $request->url()]) : null;

        return view('committees.members.import', compact('preview', 'grades', 'rows'));
    }

    public function previewImport(Request $request, MemberCsvImport $import)
    {
        $request->session()->forget('member_csv_preview');
        $request->validate(['csv' => ['required', 'file', 'max:2048', 'extensions:csv,txt']]);
        $preview = $import->preview($request->file('csv'));
        $preview['token'] = Str::random(40);
        $preview['created_at'] = time();
        $request->session()->put('member_csv_preview', $preview);

        return redirect()->route('members.import');
    }

    public function commitImport(Request $request, MemberCsvImport $import)
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:40']]);
        $preview = $request->session()->get('member_csv_preview');
        abort_unless($preview && hash_equals($preview['token'], $data['token']), 419, 'La revisión ya no está disponible.');
        if ($preview['created_at'] < time() - 3600) {
            $request->session()->forget('member_csv_preview');

            return redirect()->route('members.import')->withErrors(['csv' => 'La revisión expiró. Cargue el archivo otra vez.']);
        }
        $result = $import->commit($preview);
        $request->session()->forget('member_csv_preview');

        return redirect()->route('members.import')->with('import_result', $result);
    }
}
