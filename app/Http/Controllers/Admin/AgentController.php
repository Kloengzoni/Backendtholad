<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $agents = Agent::when($request->search, fn($q, $v) =>
                $q->where('name', 'like', "%$v%")
                  ->orWhere('email', 'like', "%$v%")
                  ->orWhere('phone', 'like', "%$v%"))
            ->when($request->role, fn($q, $v) => $q->where('role', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()->paginate(15);

        $stats = [
            'total'   => Agent::count(),
            'actif'   => Agent::where('status', 'actif')->count(),
            'inactif' => Agent::whereIn('status', ['inactif','suspendu'])->count(),
        ];

        return view('admin.agents.index', compact('agents', 'stats'));
    }

    public function create()
    {
        return view('admin.agents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:agents,email',
            'phone'    => 'nullable|string|max:30',
            'role'     => 'required|in:agent_commercial,gestionnaire,comptable,technicien,superviseur,directeur',
            'password' => 'required|min:8|confirmed',
            'avatar'   => 'nullable|image|max:3072',
        ]);

        $data = $request->except(['password','password_confirmation','_token','avatar']);
        $data['password'] = Hash::make($request->password);

        // Permissions explicites depuis le formulaire
        $data['can_manage_properties'] = $request->has('can_manage_properties') ? 1 : 0;
        $data['can_manage_bookings']   = $request->has('can_manage_bookings')   ? 1 : 0;
        $data['can_manage_stock']      = $request->has('can_manage_stock')      ? 1 : 0;
        $data['can_manage_payments']   = $request->has('can_manage_payments')   ? 1 : 0;
        $data['can_view_reports']      = $request->has('can_view_reports')      ? 1 : 0;
        $data['can_manage_users']      = $request->has('can_manage_users')      ? 1 : 0;
        $data['can_manage_agents']     = $request->has('can_manage_agents')     ? 1 : 0;

        // Avatar upload
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('agents/avatars', 'public');
            $data['avatar'] = $path;
        }

        $agent = Agent::create($data);

        return redirect()->route('admin.agents.index')
            ->with('success', "Agent {$agent->name} créé avec succès.");
    }

    public function show(string $id)
    {
        $agent = Agent::with('stockMovements.stockItem')->findOrFail($id);
        return view('admin.agents.show', compact('agent'));
    }

    public function edit(string $id)
    {
        $agent = Agent::findOrFail($id);
        return view('admin.agents.edit', compact('agent'));
    }

    public function update(Request $request, string $id)
    {
        $agent = Agent::findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:agents,email,' . $agent->id,
            'role'  => 'required',
        ]);

        $data = $request->except(['password','password_confirmation','_token','_method']);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8|confirmed']);
            $data['password'] = Hash::make($request->password);
        }

        $agent->update($data);

        return redirect()->route('admin.agents.show', $agent->id)
            ->with('success', 'Agent mis à jour.');
    }

    public function toggle(string $id)
    {
        $agent = Agent::findOrFail($id);
        $newStatus = $agent->status === 'actif' ? 'inactif' : 'actif';
        $agent->update(['status' => $newStatus]);

        return back()->with('success', "Agent {$agent->name} : statut changé en « $newStatus ».");
    }

    public function destroy(string $id)
    {
        $agent = Agent::findOrFail($id);
        $agent->delete();
        return redirect()->route('admin.agents.index')
            ->with('success', 'Agent supprimé.');
    }
}
