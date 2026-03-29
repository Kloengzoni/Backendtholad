{{-- resources/views/admin/settings.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Paramètres')
@section('content')
<h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;margin-bottom:20px">Paramètres système</h2>

<div style="max-width:700px">
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3>⚙️ Configuration générale</h3></div>
    <div style="padding:24px;display:grid;gap:18px">
      <div class="form-group">
        <label>Nom de la plateforme</label>
        <input type="text" class="form-control" value="TholadImmo" readonly>
      </div>
      <div class="form-group">
        <label>Email de contact</label>
        <input type="email" class="form-control" value="contact@tholadgroup.com" readonly>
      </div>
      <div class="form-group">
        <label>Frais de service (%)</label>
        <input type="number" class="form-control" value="5" readonly>
      </div>
      <div class="form-group">
        <label>Devise par défaut</label>
        <input type="text" class="form-control" value="XAF" readonly>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>ℹ️ Informations système</h3></div>
    <div style="padding:20px;display:grid;gap:12px">
      <div style="display:flex;justify-content:space-between;font-size:14px"><span style="color:var(--txt3)">Version</span><strong>TholadImmo v2.0.0</strong></div>
      <div style="display:flex;justify-content:space-between;font-size:14px"><span style="color:var(--txt3)">Laravel</span><strong>{{ app()->version() }}</strong></div>
      <div style="display:flex;justify-content:space-between;font-size:14px"><span style="color:var(--txt3)">PHP</span><strong>{{ PHP_VERSION }}</strong></div>
      <div style="display:flex;justify-content:space-between;font-size:14px"><span style="color:var(--txt3)">Environnement</span><strong>{{ config('app.env') }}</strong></div>
      <div style="display:flex;justify-content:space-between;font-size:14px"><span style="color:var(--txt3)">Tholad Group</span><strong>© {{ date('Y') }}</strong></div>
    </div>
  </div>
</div>
@endsection
