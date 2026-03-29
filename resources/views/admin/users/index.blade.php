{{-- resources/views/admin/users/index.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Utilisateurs')
@section('content')
<h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;margin-bottom:8px">Gestion des utilisateurs</h2>
<p style="color:var(--txt3);font-size:13px;margin-bottom:20px">{{ $users->total() }} utilisateurs inscrits</p>

<div class="card" style="margin-bottom:20px">
  <div style="padding:16px 20px">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
      <input type="text" name="search" class="form-control" placeholder="🔍 Nom, téléphone, email..." value="{{ request('search') }}" style="width:240px">
      <select name="role" class="form-control" style="width:140px">
        <option value="">Tous les rôles</option>
        <option value="client" {{ request('role')=='client'?'selected':'' }}>Clients</option>
        <option value="owner" {{ request('role')=='owner'?'selected':'' }}>Propriétaires</option>
        <option value="admin" {{ request('role')=='admin'?'selected':'' }}>Admins</option>
      </select>
      <select name="status" class="form-control" style="width:130px">
        <option value="">Tous</option>
        <option value="1" {{ request('status')=='1'?'selected':'' }}>Actifs</option>
        <option value="0" {{ request('status')=='0'?'selected':'' }}>Inactifs</option>
      </select>
      <button type="submit" class="btn btn-gold">Filtrer</button>
      <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Réinitialiser</a>
    </form>
  </div>
</div>

@if(session('success'))
  <div style="background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:12px 16px;border-radius:8px;margin-bottom:16px">
    ✅ {{ session('success') }}
  </div>
@endif

<div class="card">
  <table>
    <thead><tr><th>Utilisateur</th><th>Téléphone</th><th>Rôle</th><th>Vérifié</th><th>Inscrit</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($users as $user)
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <img src="{{ $user->avatar_url }}" style="width:38px;height:38px;border-radius:10px;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="avatar" style="display:none">{{ strtoupper(substr($user->name,0,1)) }}</div>
          <div>
            <div style="font-weight:600">{{ $user->name }}</div>
            <div style="font-size:11px;color:var(--txt3)">{{ $user->email ?? '—' }}</div>
          </div>
        </div>
      </td>
      <td>{{ $user->phone }}</td>
      <td>
        @php $roleColors=['client'=>'var(--blue)','owner'=>'var(--gold)','admin'=>'var(--coral)']; @endphp
        <span style="color:{{ $roleColors[$user->role] ?? '#666' }};font-weight:700;font-size:12px;text-transform:uppercase">{{ $user->role }}</span>
      </td>
      <td>
        @if($user->is_verified)
          <span style="color:var(--green)">✓ Vérifié</span>
        @else
          {{-- Bouton vérification manuelle admin --}}
          <form action="{{ route('admin.users.verify', $user->id) }}" method="POST" style="display:inline">
            @csrf @method('PUT')
            <button type="submit" class="btn btn-success btn-sm" title="Vérifier ce compte manuellement">
              ✓ Vérifier
            </button>
          </form>
        @endif
      </td>
      <td style="font-size:12px;color:var(--txt3)">{{ $user->created_at?->format('d/m/Y') ?? '—' }}</td>
      <td><span class="badge-status {{ $user->is_active ? 'actif' : 'annulé' }}">{{ $user->is_active ? 'actif' : 'suspendu' }}</span></td>
      <td>
        <div style="display:flex;gap:6px">
          <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
          <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST">
            @csrf @method('PUT')
            <button type="submit" class="btn {{ $user->is_active ? 'btn-danger' : 'btn-success' }} btn-sm">
              {{ $user->is_active ? 'Suspendre' : 'Activer' }}
            </button>
          </form>
        </div>
      </td>
    </tr>
    @empty
    <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--txt3)">👥 Aucun utilisateur</td></tr>
    @endforelse
    </tbody>
  </table>
  <div style="padding:16px 20px;border-top:1px solid var(--border)">{{ $users->withQueryString()->links() }}</div>
</div>
@endsection
