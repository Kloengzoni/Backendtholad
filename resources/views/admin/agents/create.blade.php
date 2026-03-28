@extends('admin.layouts.app')
@section('title', 'Nouvel agent')

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.agents.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);">Créer un compte agent TholadImmo</h2>
    <p style="font-size:13px;color:var(--txt3);">Définissez les accès et permissions selon le rôle de l'agent</p>
  </div>
</div>

<form method="POST" action="{{ route('admin.agents.store') }}" enctype="multipart/form-data">
@csrf
<div class="grid-2" style="align-items:start;">

<!-- ═══ COLONNE GAUCHE ═══ -->
<div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-user" style="color:var(--tholad-blue);"></i><h3>Identité</h3></div>
    <div style="padding:22px;">
      @if($errors->any())
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i>
        <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
      @endif

      <div class="form-group">
        <label>Nom complet *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Prénom NOM">
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="email@tholad.com">
      </div>

      <div class="form-group">
        <label>Téléphone</label>
        <div style="display:flex;gap:8px;">
          <select id="agent-ind" class="form-control" style="width:120px;flex-shrink:0;"></select>
          <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="06 XXX XX XX" style="flex:1;">
        </div>
        <small style="color:var(--txt3);font-size:11px;">Indicatif mis à jour automatiquement selon le pays</small>
      </div>

      <div class="form-group">
        <label>Matricule (optionnel)</label>
        <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id') }}" placeholder="Ex: THL-2026-001">
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Mot de passe *</label>
          <div style="position:relative;">
            <input type="password" name="password" class="form-control" id="ag-pwd1" required minlength="8">
            <button type="button" onclick="togglePwd('ag-pwd1','ag-eye1')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--txt3);"><i class="fas fa-eye" id="ag-eye1"></i></button>
          </div>
        </div>
        <div class="form-group">
          <label>Confirmer *</label>
          <div style="position:relative;">
            <input type="password" name="password_confirmation" class="form-control" id="ag-pwd2" required>
            <button type="button" onclick="togglePwd('ag-pwd2','ag-eye2')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--txt3);"><i class="fas fa-eye" id="ag-eye2"></i></button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-map-marker-alt" style="color:var(--tholad-blue);"></i><h3>Localisation</h3></div>
    <div style="padding:22px;">
      <div class="form-group">
        <label>Adresse</label>
        <input type="text" name="address" class="form-control" value="{{ old('address') }}" placeholder="N° rue, quartier...">
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Pays</label>
          <select name="country" class="form-control" id="agent-country"></select>
        </div>
        <div class="form-group">
          <label>Ville</label>
          <select name="city" class="form-control" id="agent-city">
            <option value="">— Choisir d'abord un pays —</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-phone-alt" style="color:var(--tholad-blue);"></i><h3>Contact d'urgence</h3></div>
    <div style="padding:22px;">
      <div class="form-group">
        <label>Nom</label>
        <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name') }}" placeholder="Nom du contact d'urgence">
      </div>
      <div class="form-group">
        <label>Téléphone</label>
        <div style="display:flex;gap:8px;">
          <select id="emerg-ind" class="form-control" style="width:120px;flex-shrink:0;"></select>
          <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone') }}" placeholder="06 XXX XX XX">
        </div>
      </div>
      <div class="form-group">
        <label>Relation</label>
        <select name="emergency_contact_relation" class="form-control">
          <option value="">—</option>
          <option value="conjoint"  {{ old('emergency_contact_relation')=='conjoint'?'selected':'' }}>Conjoint(e)</option>
          <option value="parent"    {{ old('emergency_contact_relation')=='parent'?'selected':'' }}>Parent (père/mère)</option>
          <option value="frere_soeur"{{ old('emergency_contact_relation')=='frere_soeur'?'selected':'' }}>Frère / Sœur</option>
          <option value="ami"       {{ old('emergency_contact_relation')=='ami'?'selected':'' }}>Ami(e)</option>
          <option value="autre"     {{ old('emergency_contact_relation')=='autre'?'selected':'' }}>Autre</option>
        </select>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-id-card" style="color:var(--tholad-blue);"></i><h3>Documents</h3></div>
    <div style="padding:22px;">
      <div class="grid-2">
        <div class="form-group">
          <label>Type de pièce d'identité</label>
          <select name="id_document_type" class="form-control">
            <option value="">—</option>
            <option value="cni"       {{ old('id_document_type')=='cni'?'selected':'' }}>CNI</option>
            <option value="passeport" {{ old('id_document_type')=='passeport'?'selected':'' }}>Passeport</option>
            <option value="permis"    {{ old('id_document_type')=='permis'?'selected':'' }}>Permis de conduire</option>
          </select>
        </div>
        <div class="form-group">
          <label>Numéro</label>
          <input type="text" name="id_document_number" class="form-control" value="{{ old('id_document_number') }}" placeholder="Ex: 123456789">
        </div>
      </div>
      <div class="form-group">
        <label>Photo de profil</label>
        <input type="file" name="avatar" class="form-control" accept="image/*">
      </div>
    </div>
  </div>

</div>

<!-- ═══ COLONNE DROITE ═══ -->
<div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-briefcase" style="color:var(--tholad-blue);"></i><h3>Poste & Rôle</h3></div>
    <div style="padding:22px;">

      <div class="form-group">
        <label>Rôle *</label>
        <select name="role" class="form-control" required id="role-select">
          <option value="">— Choisir un rôle —</option>
          @php
          $roles = [
            'agent_commercial' => ['Agent Commercial','briefcase','Vente et location de biens'],
            'gestionnaire'     => ['Gestionnaire','tasks','Gestion des propriétés et réservations'],
            'comptable'        => ['Comptable','calculator','Comptabilité et rapports financiers'],
            'technicien'       => ['Technicien','tools','Maintenance et interventions techniques'],
            'superviseur'      => ['Superviseur','user-check','Supervision d\'équipe et contrôle qualité'],
            'directeur'        => ['Directeur','crown','Direction générale et décisions stratégiques'],
          ];
          @endphp
          @foreach($roles as $val => [$label, $icon, $desc])
          <option value="{{ $val }}" data-desc="{{ $desc }}" {{ old('role')==$val?'selected':'' }}>{{ $label }}</option>
          @endforeach
        </select>
        <div id="role-desc" style="font-size:12px;color:var(--txt3);margin-top:6px;padding:8px;background:var(--bg-soft);border-radius:6px;display:none;"></div>
      </div>

      <div class="form-group">
        <label>Département / Service</label>
        <select name="department" class="form-control">
          <option value="">—</option>
          <option value="Commercial"   {{ old('department')=='Commercial'?'selected':'' }}>Commercial</option>
          <option value="Finance"      {{ old('department')=='Finance'?'selected':'' }}>Finance</option>
          <option value="Technique"    {{ old('department')=='Technique'?'selected':'' }}>Technique</option>
          <option value="RH"           {{ old('department')=='RH'?'selected':'' }}>Ressources Humaines</option>
          <option value="Direction"    {{ old('department')=='Direction'?'selected':'' }}>Direction</option>
          <option value="Informatique" {{ old('department')=='Informatique'?'selected':'' }}>Informatique</option>
          <option value="Marketing"    {{ old('department')=='Marketing'?'selected':'' }}>Marketing</option>
          <option value="Juridique"    {{ old('department')=='Juridique'?'selected':'' }}>Juridique</option>
          <option value="Autre"        {{ old('department')=='Autre'?'selected':'' }}>Autre</option>
        </select>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label>Date d'embauche</label>
          <input type="date" name="hire_date" class="form-control" value="{{ old('hire_date', date('Y-m-d')) }}">
        </div>
        <div class="form-group">
          <label>Type de contrat</label>
          <select name="contract_type" class="form-control">
            <option value="CDI"   {{ old('contract_type','CDI')=='CDI'?'selected':'' }}>CDI</option>
            <option value="CDD"   {{ old('contract_type')=='CDD'?'selected':'' }}>CDD</option>
            <option value="Stage" {{ old('contract_type')=='Stage'?'selected':'' }}>Stage</option>
            <option value="Freelance"{{ old('contract_type')=='Freelance'?'selected':'' }}>Freelance</option>
          </select>
        </div>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Salaire mensuel (XAF)</label>
          <input type="number" name="salary" class="form-control" value="{{ old('salary') }}" placeholder="0" min="0">
        </div>
        <div class="form-group">
          <label>Statut</label>
          <select name="status" class="form-control">
            <option value="actif"   {{ old('status','actif')=='actif'?'selected':'' }}>✅ Actif</option>
            <option value="inactif" {{ old('status')=='inactif'?'selected':'' }}>❌ Inactif</option>
            <option value="suspendu"{{ old('status')=='suspendu'?'selected':'' }}>🚫 Suspendu</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Notes internes</label>
        <textarea name="notes" class="form-control" rows="3" placeholder="Informations complémentaires...">{{ old('notes') }}</textarea>
      </div>

    </div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-shield-alt" style="color:var(--tholad-blue);"></i><h3>Permissions d'accès</h3></div>
    <div style="padding:22px;">
      <p style="font-size:12px;color:var(--txt3);margin-bottom:14px;">
        <i class="fas fa-info-circle"></i> Les permissions sont pré-configurées selon le rôle. Vous pouvez les ajuster manuellement :
      </p>

      @php
      $perms = [
        'can_manage_properties' => ['Gérer les propriétés',     'building',       'Créer, modifier, approuver des biens'],
        'can_manage_bookings'   => ['Gérer les réservations',   'calendar-check', 'Voir et traiter les réservations'],
        'can_manage_stock'      => ['Gérer les stocks',         'boxes',          'Inventaire et mouvements de stock'],
        'can_manage_payments'   => ['Gérer les paiements',      'credit-card',    'Valider et traiter les paiements'],
        'can_view_reports'      => ['Voir les rapports',        'chart-bar',      'Accès aux statistiques et tableaux de bord'],
        'can_manage_users'      => ['Gérer les utilisateurs',   'users',          'Gérer les comptes clients et propriétaires'],
        'can_manage_agents'     => ['Gérer les agents',         'user-tie',       'Créer et modifier des comptes agents'],
      ];
      @endphp

      <div id="permissions-list">
        @foreach($perms as $key => $perm)
        <label class="perm-row" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;cursor:pointer;margin-bottom:8px;transition:all .15s;">
          <input type="checkbox" name="{{ $key }}" value="1" {{ old($key,1)?'checked':'' }}
                 style="width:16px;height:16px;accent-color:var(--tholad-blue);flex-shrink:0;">
          <i class="fas fa-{{ $perm[1] }}" style="color:var(--tholad-blue);width:16px;font-size:13px;flex-shrink:0;"></i>
          <div>
            <div style="font-size:13.5px;font-weight:500;">{{ $perm[0] }}</div>
            <div style="font-size:11px;color:var(--txt3);">{{ $perm[2] }}</div>
          </div>
        </label>
        @endforeach
      </div>

      <div style="margin-top:12px;padding:10px;background:#FFF8E1;border-radius:8px;font-size:12px;color:#856404;">
        <i class="fas fa-lightbulb"></i> <strong>Conseil :</strong> Les permissions sont automatiquement configurées selon le rôle choisi à gauche. Sélectionnez d'abord le rôle.
      </div>

    </div>
  </div>

</div>
</div>

<div style="display:flex;gap:12px;margin-top:4px;margin-bottom:40px;">
  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer l'agent</button>
  <a href="{{ route('admin.agents.index') }}" class="btn btn-outline">Annuler</a>
</div>
</form>

<style>
.perm-row:hover{background:var(--bg-soft);border-color:var(--tholad-blue);}
.perm-row:has(input:checked){background:#EEF2FF;border-color:var(--tholad-blue);}
</style>

<script>
const PAYS_DATA = {
  "Congo Brazzaville":{code:"+242",villes:["Brazzaville","Pointe-Noire","Dolisie","Nkayi","Impfondo","Ouesso","Owando","Mossendjo","Madingou","Kinkala","Sibiti","Gamboma","Djambala","Boundji","Ewo"]},
  "Congo RDC":{code:"+243",villes:["Kinshasa","Lubumbashi","Mbuji-Mayi","Kisangani","Goma","Bukavu","Kananga","Matadi","Kolwezi","Likasi"]},
  "Gabon":{code:"+241",villes:["Libreville","Port-Gentil","Franceville","Oyem","Moanda","Mouila","Lambaréné","Makokou"]},
  "Cameroun":{code:"+237",villes:["Yaoundé","Douala","Garoua","Bamenda","Bafoussam","Ngaoundéré","Bertoua","Kumba"]},
  "Côte d'Ivoire":{code:"+225",villes:["Abidjan","Yamoussoukro","Bouaké","Daloa","Korhogo","Man","San-Pédro"]},
  "Sénégal":{code:"+221",villes:["Dakar","Thiès","Kaolack","Ziguinchor","Saint-Louis","Touba","Mbour"]},
  "Mali":{code:"+223",villes:["Bamako","Sikasso","Ségou","Mopti","Koutiala","Gao","Kayes"]},
  "Guinée":{code:"+224",villes:["Conakry","Kankan","Labé","Kindia","Nzérékoré","Mamou","Boke"]},
  "Tchad":{code:"+235",villes:["N'Djamena","Moundou","Sarh","Abéché","Kélo","Bongor"]},
  "Centrafrique":{code:"+236",villes:["Bangui","Berbérati","Carnot","Bambari","Bouar"]},
  "Angola":{code:"+244",villes:["Luanda","Huambo","Lobito","Benguela","Lubango","Cabinda"]},
  "France":{code:"+33",villes:["Paris","Marseille","Lyon","Toulouse","Nice","Bordeaux","Lille"]},
  "Belgique":{code:"+32",villes:["Bruxelles","Anvers","Gand","Charleroi","Liège","Bruges"]},
  "Togo":{code:"+228",villes:["Lomé","Sokodé","Kpalimé","Atakpamé","Dapaong","Kara"]},
  "Bénin":{code:"+229",villes:["Cotonou","Porto-Novo","Parakou","Abomey","Bohicon","Kandi"]},
  "Burkina Faso":{code:"+226",villes:["Ouagadougou","Bobo-Dioulasso","Koudougou","Banfora","Ouahigouya"]},
  "Rwanda":{code:"+250",villes:["Kigali","Butare","Gitarama","Ruhengeri","Gisenyi"]},
  "Burundi":{code:"+257",villes:["Bujumbura","Gitega","Muyinga","Ngozi","Rumonge"]},
  "Madagascar":{code:"+261",villes:["Antananarivo","Toamasina","Antsirabe","Fianarantsoa","Mahajanga"]},
  "Maroc":{code:"+212",villes:["Casablanca","Rabat","Fès","Marrakech","Agadir","Tanger"]},
  "Algérie":{code:"+213",villes:["Alger","Oran","Constantine","Annaba","Blida","Batna"]},
};
const FLAGS={"Congo Brazzaville":"🇨🇬","Congo RDC":"🇨🇩","Gabon":"🇬🇦","Cameroun":"🇨🇲","Côte d'Ivoire":"🇨🇮","Sénégal":"🇸🇳","Mali":"🇲🇱","Guinée":"🇬🇳","Tchad":"🇹🇩","Centrafrique":"🇨🇫","Angola":"🇦🇴","France":"🇫🇷","Belgique":"🇧🇪","Togo":"🇹🇬","Bénin":"🇧🇯","Burkina Faso":"🇧🇫","Rwanda":"🇷🇼","Burundi":"🇧🇮","Madagascar":"🇲🇬","Maroc":"🇲🇦","Algérie":"🇩🇿"};

function buildCountry(s,def='Congo Brazzaville'){
  s.innerHTML='<option value="">— Choisir un pays —</option>';
  Object.keys(PAYS_DATA).sort().forEach(p=>{
    const o=document.createElement('option');o.value=p;o.textContent=`${FLAGS[p]||''} ${p}`;
    if(p===def)o.selected=true;s.appendChild(o);
  });
}
function buildCity(s,country,def=''){
  s.innerHTML='';
  if(!country||!PAYS_DATA[country]){s.innerHTML='<option value="">— Choisir d\'abord un pays —</option>';return;}
  PAYS_DATA[country].villes.forEach(v=>{
    const o=document.createElement('option');o.value=v;o.textContent=v;
    if(v===def)o.selected=true;s.appendChild(o);
  });
}
function buildInds(code='+242'){
  ['agent-ind','emerg-ind'].forEach(id=>{
    const s=document.getElementById(id);if(!s)return;s.innerHTML='';
    Object.entries(PAYS_DATA).forEach(([p,d])=>{
      const o=document.createElement('option');o.value=d.code;o.textContent=`${FLAGS[p]||''} ${d.code}`;
      if(d.code===code)o.selected=true;s.appendChild(o);
    });
  });
}

const defC='{{ old('country','Congo Brazzaville') }}';
buildCountry(document.getElementById('agent-country'),defC);
buildCity(document.getElementById('agent-city'),defC,'{{ old('city','Pointe-Noire') }}');
buildInds(PAYS_DATA[defC]?.code||'+242');

document.getElementById('agent-country').addEventListener('change',function(){
  buildCity(document.getElementById('agent-city'),this.value);
  buildInds(PAYS_DATA[this.value]?.code||'+242');
});

// Rôle → permissions auto
const ROLE_PERMISSIONS = {
  agent_commercial:['can_manage_properties','can_manage_bookings','can_view_reports'],
  gestionnaire:    ['can_manage_properties','can_manage_bookings','can_manage_stock','can_view_reports'],
  comptable:       ['can_manage_payments','can_view_reports'],
  technicien:      ['can_manage_properties','can_manage_stock'],
  superviseur:     ['can_manage_properties','can_manage_bookings','can_manage_stock','can_manage_payments','can_view_reports','can_manage_users'],
  directeur:       ['can_manage_properties','can_manage_bookings','can_manage_stock','can_manage_payments','can_view_reports','can_manage_users','can_manage_agents'],
};

const roleSelect = document.getElementById('role-select');
const roleDesc = document.getElementById('role-desc');

roleSelect.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const desc = opt.getAttribute('data-desc');
  if (desc) {
    roleDesc.textContent = '💼 ' + desc;
    roleDesc.style.display = '';
  } else {
    roleDesc.style.display = 'none';
  }

  // Auto-set permissions
  const role = this.value;
  const allowedPerms = ROLE_PERMISSIONS[role] || [];
  document.querySelectorAll('[name^="can_"]').forEach(cb => {
    cb.checked = allowedPerms.includes(cb.name);
  });
});

// Init role desc on load
if (roleSelect.value) roleSelect.dispatchEvent(new Event('change'));

function togglePwd(id,eid){
  const i=document.getElementById(id),e=document.getElementById(eid);
  i.type=i.type==='password'?'text':'password';
  e.className=i.type==='text'?'fas fa-eye-slash':'fas fa-eye';
}
</script>
@endsection
