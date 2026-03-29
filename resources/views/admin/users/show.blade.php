{{-- resources/views/admin/users/show.blade.php --}}
@extends('admin.layouts.app')
@section('title', $user->name)
@section('content')
<div style="display:flex;align-items:center;gap:16px;margin-bottom:24px">
  <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">← Retour</a>
  <div style="flex:1">
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700">{{ $user->name }}</h2>
    <p style="color:var(--txt3);font-size:13px">Inscrit {{ $user->created_at?->diffForHumans() ?? '—' }}</p>
  </div>
  <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST">
    @csrf @method('PUT')
    <button type="submit" class="btn {{ $user->is_active ? 'btn-danger' : 'btn-success' }}">
      {{ $user->is_active ? '⛔ Suspendre' : '✓ Activer' }}
    </button>
  </form>
</div>

<div class="grid-2" style="margin-bottom:20px">
  <div class="card">
    <div class="card-header"><h3>👤 Profil</h3></div>
    <div style="padding:20px">
      <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
        <div class="avatar" style="width:60px;height:60px;font-size:24px">{{ strtoupper(substr($user->name,0,1)) }}</div>
        <div>
          <div style="font-size:20px;font-weight:700">{{ $user->name }}</div>
          @php $roleColors=['client'=>'var(--blue)','owner'=>'var(--gold)','admin'=>'var(--coral)']; @endphp
          <span style="color:{{ $roleColors[$user->role] ?? '#666' }};font-weight:700;font-size:13px;text-transform:uppercase">{{ $user->role }}</span>
          @if($user->is_verified)<span style="color:var(--green);margin-left:8px;font-size:12px">✓ Vérifié</span>@endif
        </div>
      </div>
      <div style="display:grid;gap:12px">
        <div style="display:flex;gap:10px"><span style="color:var(--txt3);width:100px;font-size:13px">Téléphone</span><strong>{{ $user->phone }}</strong></div>
        <div style="display:flex;gap:10px"><span style="color:var(--txt3);width:100px;font-size:13px">Email</span><strong>{{ $user->email ?? '—' }}</strong></div>
        <div style="display:flex;gap:10px"><span style="color:var(--txt3);width:100px;font-size:13px">Pays</span><strong>{{ $user->country }}</strong></div>
        <div style="display:flex;gap:10px"><span style="color:var(--txt3);width:100px;font-size:13px">Statut</span>
          <span class="badge-status {{ $user->is_active ? 'actif' : 'annulé' }}">{{ $user->is_active ? 'Actif' : 'Suspendu' }}</span>
        </div>
        <div style="display:flex;gap:10px"><span style="color:var(--txt3);width:100px;font-size:13px">Dernière connexion</span><strong>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</strong></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>📊 Activité</h3></div>
    <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
      @php
        $bookings = $user->bookings;
        $reviews  = $user->reviews;
        $favs     = $user->favorites;
      @endphp
      <div style="padding:16px;background:var(--bg);border-radius:12px;text-align:center">
        <div style="font-size:28px;font-weight:700;color:var(--navy)">{{ $bookings->count() }}</div>
        <div style="font-size:12px;color:var(--txt3)">Réservations</div>
      </div>
      <div style="padding:16px;background:var(--bg);border-radius:12px;text-align:center">
        <div style="font-size:28px;font-weight:700;color:var(--gold)">{{ number_format($bookings->sum('total_amount'),0,',',' ') }}</div>
        <div style="font-size:12px;color:var(--txt3)">XAF dépensés</div>
      </div>
      <div style="padding:16px;background:var(--bg);border-radius:12px;text-align:center">
        <div style="font-size:28px;font-weight:700;color:var(--green)">{{ $reviews->count() }}</div>
        <div style="font-size:12px;color:var(--txt3)">Avis laissés</div>
      </div>
      <div style="padding:16px;background:var(--bg);border-radius:12px;text-align:center">
        <div style="font-size:28px;font-weight:700;color:var(--coral)">{{ $favs->count() }}</div>
        <div style="font-size:12px;color:var(--txt3)">Favoris</div>
      </div>
    </div>
  </div>
</div>

<!-- Recent bookings -->
@if($bookings->count())
<div class="card">
  <div class="card-header"><h3>📅 Réservations récentes</h3></div>
  <table>
    <thead><tr><th>Référence</th><th>Propriété</th><th>Dates</th><th>Montant</th><th>Statut</th></tr></thead>
    <tbody>
    @foreach($bookings->take(5) as $b)
    <tr>
      <td><a href="{{ route('admin.bookings.show', $b->reference) }}" style="color:var(--gold);font-weight:600;text-decoration:none">{{ $b->reference }}</a></td>
      <td>{{ $b->property->title ?? '—' }}</td>
      <td style="font-size:12px">{{ $b->check_in->format('d/m/Y') }} → {{ $b->check_out->format('d/m/Y') }}</td>
      <td><strong>{{ number_format($b->total_amount,0,',',' ') }} {{ $b->currency }}</strong></td>
      <td><span class="badge-status {{ $b->status }}">{{ $b->status }}</span></td>
    </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endif
@endsection
