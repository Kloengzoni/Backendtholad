{{-- resources/views/admin/properties/index.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Propriétés')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700">Gestion des propriétés</h2>
    <p style="color:var(--txt3);font-size:13px">{{ $properties->total() }} propriétés au total</p>
  </div>
  <a href="{{ route('admin.properties.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Nouvelle propriété
  </a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
  <div style="padding:16px 20px">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
      <input type="text" name="search" class="form-control" placeholder="🔍 Titre, ville..." value="{{ request('search') }}" style="width:220px">
      <select name="type" class="form-control" style="width:150px">
        <option value="">Tous les types</option>
        @foreach(['appartement','villa','studio','maison','chambre','bureau','terrain'] as $t)
          <option value="{{ $t }}" {{ request('type')==$t?'selected':'' }}>{{ ucfirst($t) }}</option>
        @endforeach
      </select>
      <select name="status" class="form-control" style="width:150px">
        <option value="">Tous statuts</option>
        @foreach(['disponible','occupé','maintenance','suspendu'] as $s)
          <option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ $s }}</option>
        @endforeach
      </select>
      <select name="approved" class="form-control" style="width:150px">
        <option value="">Approbation</option>
        <option value="1" {{ request('approved')=='1'?'selected':'' }}>Approuvés</option>
        <option value="0" {{ request('approved')=='0'?'selected':'' }}>En attente</option>
      </select>
      <button type="submit" class="btn btn-gold">Filtrer</button>
      <a href="{{ route('admin.properties.index') }}" class="btn btn-outline">Réinitialiser</a>
    </form>
  </div>
</div>

<div class="card">
  <table>
    <thead><tr><th>Propriété</th><th>Propriétaire</th><th>Prix</th><th>Note</th><th>Statut</th><th>Approuvé</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($properties as $p)
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:12px">
          @if($p->primaryImage)
            <img src="{{ $p->primaryImage->url }}" style="width:50px;height:40px;object-fit:cover;border-radius:8px">
          @else
            <div style="width:50px;height:40px;background:var(--bg);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px">🏠</div>
          @endif
          <div>
            <div style="font-weight:600;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p->title }}</div>
            <div style="font-size:11px;color:var(--txt3)">{{ $p->city }}, {{ $p->district }}</div>
          </div>
        </div>
      </td>
      <td>{{ $p->owner->name ?? '—' }}</td>
      <td><strong style="color:var(--gold)">{{ number_format($p->price,0,',',' ') }}</strong><br><span style="font-size:11px;color:var(--txt3)">{{ $p->currency }}/{{ $p->price_period }}</span></td>
      <td>⭐ {{ $p->rating }} <span style="font-size:11px;color:var(--txt3)">({{ $p->reviews_count }})</span></td>
      <td><span class="badge-status {{ $p->status }}">{{ $p->status }}</span></td>
      <td>
        @if($p->is_approved)
          <span class="badge-status actif">✓ Approuvé</span>
        @else
          <form action="{{ route('admin.properties.approve', $p->id) }}" method="POST" style="display:inline">
            @csrf @method('PUT')
            <button type="submit" class="btn btn-success btn-sm">Approuver</button>
          </form>
        @endif
      </td>
      <td>
        <div style="display:flex;gap:6px">
          <a href="{{ route('admin.properties.show', $p->id) }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
          <form action="{{ route('admin.properties.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Supprimer ?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </td>
    </tr>
    @empty
    <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--txt3)">😕 Aucune propriété trouvée</td></tr>
    @endforelse
    </tbody>
  </table>
  <div style="padding:16px 20px;border-top:1px solid var(--border)">{{ $properties->withQueryString()->links() }}</div>
</div>
@endsection
