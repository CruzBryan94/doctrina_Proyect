<?php

namespace App\Http\Controllers;

use App\Models\Manual;
use App\Models\ManualPhase;
use App\Services\ManualWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualDeletionController extends Controller
{
    public function index()
    {
        $user = request()->user();
        abort_unless($user->canConsultManuals(), 403);

        $query = Manual::onlyTrashed()->with(['manualPhase', 'manualType.doctrinalMap', 'deletedBy']);
        if (! $user->hasRole('admin')) {
            $phaseIds = ManualPhase::whereIn('slug', $user->accessiblePhaseSlugs())->pluck('id');
            $query->whereIn('manual_phases_id', $phaseIds)->where('deleted_at', '>=', now()->subDays(7));
        }

        $manuals = $query->orderByDesc('deleted_at')->paginate(20);

        return view('manuals.deleted', compact('manuals'));
    }

    public function destroy(Request $request, int $id, ManualWorkflow $workflow)
    {
        $data = $request->validate(['version' => ['required', 'integer', 'min:0']]);
        $manual = DB::transaction(function () use ($data, $id, $workflow) {
            $manual = Manual::with('manualPhase')->lockForUpdate()->findOrFail($id);
            $this->authorize('delete', $manual);
            abort_if((int) $data['version'] !== $manual->lock_version, 409, 'El manual cambió. Recarga la matriz antes de eliminarlo.');

            $before = $manual->toArray();
            $manual->deleted_by = auth()->id();
            $manual->lock_version++;
            $manual->save();
            $manual->delete();
            $manual->refresh()->load('manualPhase');
            $workflow->event($manual, 'deleted', $before, $manual->toArray(), 'Borrado lógico; recuperación habilitada durante siete días para usuarios de la fase y sin límite para administración.');

            return $manual;
        });

        return redirect()->route('manuals.index', ['phase' => $manual->manualPhase->slug, 'published' => $manual->is_published ? 1 : 0])->with('success', 'Manual enviado a la papelera. Puede recuperarse durante siete días; administración puede recuperarlo después.');
    }

    public function restore(Request $request, int $id, ManualWorkflow $workflow)
    {
        $data = $request->validate(['version' => ['required', 'integer', 'min:0']]);
        $manual = DB::transaction(function () use ($data, $id, $workflow) {
            $manual = Manual::onlyTrashed()->with('manualPhase')->lockForUpdate()->findOrFail($id);
            $this->authorize('restore', $manual);
            abort_if((int) $data['version'] !== $manual->lock_version, 409, 'El manual cambió. Recarga la papelera antes de recuperarlo.');

            $before = $manual->toArray();
            $manual->lock_version++;
            $manual->restore();
            $manual->refresh()->load('manualPhase');
            $workflow->event($manual, 'restored', $before, $manual->toArray(), 'Recuperación del estado conservado por el borrado lógico.');

            return $manual;
        });

        return redirect()->route('manuals.index', ['phase' => $manual->manualPhase->slug, 'published' => $manual->is_published ? 1 : 0])->with('success', 'Manual recuperado con su estado e historial conservados.');
    }
}
