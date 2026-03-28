@extends('admin.layouts.app')
@section('title', 'Nouveau propriétaire')

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.owners.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);">Enregistrer un propriétaire</h2>
    <p style="font-size:13px;color:var(--txt3);">Compte propriétaire pour la gestion des biens ImmoStay</p>
  </div>
</div>

<form method="POST" action="{{ route('admin.owners.store') }}" enctype="multipart/form-data">
@csrf

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:20px;">
  <i class="fas fa-exclamation-circle"></i>
  <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="grid-2" style="align-items:start;">
<div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-user" style="color:var(--tholad-blue);"></i><h3>Informations personnelles</h3></div>
    <div style="padding:22px;">

      <div class="form-group">
        <label>Type de propriétaire *</label>
        <div style="display:flex;gap:10px;margin-top:6px;">
          <label class="owner-type-btn" id="btn-particulier">
            <input type="radio" name="legal_form" value="Particulier" {{ old('legal_form','Particulier')=='Particulier'?'checked':'' }} style="display:none">
            <i class="fas fa-user"></i> Particulier
          </label>
          <label class="owner-type-btn" id="btn-entreprise">
            <input type="radio" name="legal_form" value="SARL" style="display:none">
            <i class="fas fa-building"></i> Entreprise / Société
          </label>
        </div>
      </div>

      <div class="form-group">
        <label>Nom complet *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Prénom NOM">
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="email@exemple.com">
      </div>

      <div class="form-group">
        <label>Téléphone *</label>
        <div style="display:flex;gap:8px;">
          <select id="owner-ind" class="form-control" style="width:120px;flex-shrink:0;"></select>
          <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required placeholder="06 XXX XX XX" style="flex:1;">
        </div>
        <small style="color:var(--txt3);font-size:11px;">Indicatif mis à jour automatiquement selon le pays sélectionné</small>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label>Mot de passe *</label>
          <div style="position:relative;">
            <input type="password" name="password" class="form-control" id="pwd1" required minlength="8">
            <button type="button" onclick="togglePwd('pwd1','eye1')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--txt3);"><i class="fas fa-eye" id="eye1"></i></button>
          </div>
        </div>
        <div class="form-group">
          <label>Confirmer *</label>
          <div style="position:relative;">
            <input type="password" name="password_confirmation" class="form-control" id="pwd2" required>
            <button type="button" onclick="togglePwd('pwd2','eye2')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--txt3);"><i class="fas fa-eye" id="eye2"></i></button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-map-marker-alt" style="color:var(--tholad-blue);"></i><h3>Adresse</h3></div>
    <div style="padding:22px;">
      <div class="form-group">
        <label>Adresse</label>
        <input type="text" name="address" class="form-control" value="{{ old('address') }}" placeholder="N° rue, quartier...">
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Pays *</label>
          <select name="country" class="form-control" id="owner-country" required></select>
        </div>
        <div class="form-group">
          <label>Ville *</label>
          <select name="city" class="form-control" id="owner-city" required>
            <option value="">— Choisir d'abord un pays —</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-id-card" style="color:var(--tholad-blue);"></i><h3>Pièce d'identité</h3></div>
    <div style="padding:22px;">
      <div class="grid-2">
        <div class="form-group">
          <label>Type de document</label>
          <select name="id_document_type" class="form-control">
            <option value="">—</option>
            <option value="cni"          {{ old('id_document_type')=='cni'?'selected':'' }}>CNI / Carte Nationale</option>
            <option value="passeport"    {{ old('id_document_type')=='passeport'?'selected':'' }}>Passeport</option>
            <option value="titre_sejour" {{ old('id_document_type')=='titre_sejour'?'selected':'' }}>Titre de séjour</option>
            <option value="permis"       {{ old('id_document_type')=='permis'?'selected':'' }}>Permis de conduire</option>
          </select>
        </div>
        <div class="form-group">
          <label>Numéro du document</label>
          <input type="text" name="id_document_number" class="form-control" value="{{ old('id_document_number') }}" placeholder="Ex: 123456789">
        </div>
      </div>
      <div class="form-group">
        <label>Date d'expiration</label>
        <input type="date" name="id_document_expiry" class="form-control" value="{{ old('id_document_expiry') }}">
      </div>
      <div class="form-group">
        <label>Scan du document</label>
        <input type="file" name="id_document_file" class="form-control" accept="image/*,.pdf">
        <small style="color:var(--txt3);font-size:11px;">JPG, PNG ou PDF — Max 5 Mo</small>
      </div>
    </div>
  </div>

</div>
<div>

  <div class="card" style="margin-bottom:20px;display:none;" id="company-card">
    <div class="card-header"><i class="fas fa-building" style="color:var(--tholad-blue);"></i><h3>Informations société</h3></div>
    <div style="padding:22px;">
      <div class="form-group">
        <label>Nom de la société</label>
        <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" placeholder="Ex: AYANDA SARL, Groupe Tholad...">
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Forme juridique</label>
          <select name="legal_form_detail" class="form-control">
            <option value="">—</option>
            <option value="SARL"        {{ old('legal_form')=='SARL'?'selected':'' }}>SARL</option>
            <option value="SA"          {{ old('legal_form')=='SA'?'selected':'' }}>SA</option>
            <option value="SAS"         {{ old('legal_form')=='SAS'?'selected':'' }}>SAS</option>
            <option value="GIE"         {{ old('legal_form')=='GIE'?'selected':'' }}>GIE</option>
            <option value="Association" {{ old('legal_form')=='Association'?'selected':'' }}>Association</option>
            <option value="ONG"         {{ old('legal_form')=='ONG'?'selected':'' }}>ONG</option>
          </select>
        </div>
        <div class="form-group">
          <label>N° RCCM / Registre</label>
          <input type="text" name="siret" class="form-control" value="{{ old('siret') }}" placeholder="CG-BZV-2024-B-00123">
        </div>
      </div>
      <div class="form-group">
        <label>Fichier RCCM / Kbis</label>
        <input type="file" name="kbis_file" class="form-control" accept="image/*,.pdf">
      </div>
      <div class="form-group">
        <label>Personne de contact</label>
        <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}" placeholder="Nom du responsable">
      </div>
      <div class="form-group">
        <label>Téléphone du contact</label>
        <div style="display:flex;gap:8px;">
          <select id="contact-ind" class="form-control" style="width:120px;flex-shrink:0;"></select>
          <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone') }}" placeholder="06 XXX XX XX">
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-percentage" style="color:var(--tholad-blue);"></i><h3>Commission & Paramètres financiers</h3></div>
    <div style="padding:22px;">
      <div class="form-group">
        <label>Commission ImmoStay (%)</label>
        <div style="position:relative;">
          <input type="number" name="commission_rate" class="form-control" value="{{ old('commission_rate',10) }}" min="0" max="50" step="0.5" style="padding-right:36px;">
          <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--txt3);">%</span>
        </div>
        <small style="color:var(--txt3);font-size:12px;">Prélevé sur chaque réservation confirmée</small>
      </div>
      <div class="form-group">
        <label>Mode de paiement préféré</label>
        <select name="preferred_payment" class="form-control">
          <option value="mtn_momo"    {{ old('preferred_payment','mtn_momo')=='mtn_momo'?'selected':'' }}>📱 MTN Mobile Money</option>
          <option value="airtel_money"{{ old('preferred_payment')=='airtel_money'?'selected':'' }}>📱 Airtel Money</option>
          <option value="bank"        {{ old('preferred_payment')=='bank'?'selected':'' }}>🏦 Virement bancaire</option>
          <option value="cash"        {{ old('preferred_payment')=='cash'?'selected':'' }}>💵 Espèces</option>
        </select>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-mobile-alt" style="color:var(--tholad-blue);"></i><h3>Coordonnées bancaires / Mobile Money</h3></div>
    <div style="padding:22px;">
      <div class="form-group">
        <label>Numéro Mobile Money</label>
        <div style="display:flex;gap:8px;">
          <select id="mm-ind" class="form-control" style="width:120px;flex-shrink:0;"></select>
          <input type="text" name="mobile_money_number" class="form-control" value="{{ old('mobile_money_number') }}" placeholder="06 XXX XX XX">
        </div>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Banque</label>
          <select name="bank_name" class="form-control">
            <option value="">—</option>
            <optgroup label="Congo Brazzaville">
              <option value="LCB"    {{ old('bank_name')=='LCB'?'selected':'' }}>LCB — La Congolaise de Banque</option>
              <option value="BGFI"   {{ old('bank_name')=='BGFI'?'selected':'' }}>BGFI Bank</option>
              <option value="ECOBANK"{{ old('bank_name')=='ECOBANK'?'selected':'' }}>Ecobank</option>
              <option value="UBA"    {{ old('bank_name')=='UBA'?'selected':'' }}>UBA</option>
              <option value="BCI"    {{ old('bank_name')=='BCI'?'selected':'' }}>BCI</option>
              <option value="MUCODEC"{{ old('bank_name')=='MUCODEC'?'selected':'' }}>MUCODEC</option>
              <option value="CREDIT_DU_CONGO" {{ old('bank_name')=='CREDIT_DU_CONGO'?'selected':'' }}>Crédit du Congo</option>
            </optgroup>
            <optgroup label="Autre"><option value="Autre">Autre banque</option></optgroup>
          </select>
        </div>
        <div class="form-group">
          <label>Numéro de compte</label>
          <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account') }}" placeholder="CG42...">
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-toggle-on" style="color:var(--tholad-blue);"></i><h3>Statut du compte</h3></div>
    <div style="padding:22px;">
      <div class="form-group">
        <label>Statut de vérification</label>
        <select name="status" class="form-control">
          <option value="en_attente" {{ old('status','en_attente')=='en_attente'?'selected':'' }}>⏳ En attente de vérification</option>
          <option value="vérifié"    {{ old('status')=='vérifié'?'selected':'' }}>✅ Vérifié immédiatement</option>
          <option value="suspendu"   {{ old('status')=='suspendu'?'selected':'' }}>🚫 Suspendu</option>
        </select>
      </div>
      <div class="form-group">
        <label>Notes internes</label>
        <textarea name="notes" class="form-control" rows="3" placeholder="Informations complémentaires...">{{ old('notes') }}</textarea>
      </div>
    </div>
  </div>

</div>
</div>

<div style="display:flex;gap:12px;margin-top:4px;margin-bottom:40px;">
  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer le propriétaire</button>
  <a href="{{ route('admin.owners.index') }}" class="btn btn-outline">Annuler</a>
</div>
</form>

<style>
.owner-type-btn {
  display:flex;align-items:center;gap:8px;padding:10px 20px;
  border:2px solid var(--border);border-radius:8px;cursor:pointer;
  font-weight:500;font-size:14px;transition:all .2s;flex:1;justify-content:center;
}
.owner-type-btn:hover{border-color:var(--tholad-blue);color:var(--tholad-blue);}
.owner-type-btn.selected{border-color:var(--tholad-blue);background:#EEF2FF;color:var(--tholad-blue);}
</style>

<script>
const PAYS_DATA = {
  "Congo Brazzaville":{code:"+242",villes:["Brazzaville","Pointe-Noire","Dolisie","Nkayi","Impfondo","Ouesso","Owando","Mossendjo","Madingou","Kinkala","Sibiti","Gamboma","Djambala","Boundji","Ewo","Liranga","Makabana"]},
  "Congo RDC":{code:"+243",villes:["Kinshasa","Lubumbashi","Mbuji-Mayi","Kisangani","Goma","Bukavu","Kananga","Matadi","Kolwezi","Likasi","Beni","Butembo"]},
  "Gabon":{code:"+241",villes:["Libreville","Port-Gentil","Franceville","Oyem","Moanda","Mouila","Lambaréné","Tchibanga","Makokou"]},
  "Cameroun":{code:"+237",villes:["Yaoundé","Douala","Garoua","Bamenda","Bafoussam","Ngaoundéré","Bertoua","Kumba","Kribi"]},
  "Côte d'Ivoire":{code:"+225",villes:["Abidjan","Yamoussoukro","Bouaké","Daloa","Korhogo","Man","San-Pédro","Divo","Gagnoa"]},
  "Sénégal":{code:"+221",villes:["Dakar","Thiès","Kaolack","Ziguinchor","Saint-Louis","Touba","Mbour","Rufisque","Tambacounda"]},
  "Mali":{code:"+223",villes:["Bamako","Sikasso","Ségou","Mopti","Koutiala","Gao","Kayes","Bougouni"]},
  "Guinée":{code:"+224",villes:["Conakry","Kankan","Labé","Kindia","Nzérékoré","Mamou","Boke","Faranah"]},
  "Tchad":{code:"+235",villes:["N'Djamena","Moundou","Sarh","Abéché","Kélo","Koumra","Bongor"]},
  "Centrafrique":{code:"+236",villes:["Bangui","Berbérati","Carnot","Bambari","Bouar","Bossangoa"]},
  "Angola":{code:"+244",villes:["Luanda","Huambo","Lobito","Benguela","Kuito","Lubango","Cabinda"]},
  "France":{code:"+33",villes:["Paris","Marseille","Lyon","Toulouse","Nice","Nantes","Bordeaux","Lille","Rennes"]},
  "Belgique":{code:"+32",villes:["Bruxelles","Anvers","Gand","Charleroi","Liège","Bruges","Namur"]},
  "Togo":{code:"+228",villes:["Lomé","Sokodé","Kpalimé","Atakpamé","Dapaong","Kara"]},
  "Bénin":{code:"+229",villes:["Cotonou","Porto-Novo","Parakou","Abomey","Bohicon","Kandi"]},
  "Burkina Faso":{code:"+226",villes:["Ouagadougou","Bobo-Dioulasso","Koudougou","Banfora","Ouahigouya"]},
  "Rwanda":{code:"+250",villes:["Kigali","Butare","Gitarama","Ruhengeri","Gisenyi"]},
  "Burundi":{code:"+257",villes:["Bujumbura","Gitega","Muyinga","Ngozi","Rumonge"]},
  "Madagascar":{code:"+261",villes:["Antananarivo","Toamasina","Antsirabe","Fianarantsoa","Mahajanga"]},
  "Maroc":{code:"+212",villes:["Casablanca","Rabat","Fès","Marrakech","Agadir","Tanger","Oujda"]},
  "Algérie":{code:"+213",villes:["Alger","Oran","Constantine","Annaba","Blida","Batna","Sétif"]},
};
const FLAGS={"Congo Brazzaville":"🇨🇬","Congo RDC":"🇨🇩","Gabon":"🇬🇦","Cameroun":"🇨🇲","Côte d'Ivoire":"🇨🇮","Sénégal":"🇸🇳","Mali":"🇲🇱","Guinée":"🇬🇳","Tchad":"🇹🇩","Centrafrique":"🇨🇫","Angola":"🇦🇴","France":"🇫🇷","Belgique":"🇧🇪","Togo":"🇹🇬","Bénin":"🇧🇯","Burkina Faso":"🇧🇫","Rwanda":"🇷🇼","Burundi":"🇧🇮","Madagascar":"🇲🇬","Maroc":"🇲🇦","Algérie":"🇩🇿"};

function buildCountry(sel,def='Congo Brazzaville'){
  sel.innerHTML='<option value="">— Choisir un pays —</option>';
  Object.keys(PAYS_DATA).sort().forEach(p=>{
    const o=document.createElement('option');
    o.value=p;o.textContent=`${FLAGS[p]||''}  ${p}`;
    if(p===def)o.selected=true;
    sel.appendChild(o);
  });
}
function buildCity(sel,country,def=''){
  sel.innerHTML='';
  if(!country||!PAYS_DATA[country]){sel.innerHTML='<option value="">— Choisir d\'abord un pays —</option>';return;}
  PAYS_DATA[country].villes.forEach(v=>{
    const o=document.createElement('option');
    o.value=v;o.textContent=v;
    if(v===def)o.selected=true;
    sel.appendChild(o);
  });
}
function buildIndicatifs(code='+242'){
  ['owner-ind','contact-ind','mm-ind'].forEach(id=>{
    const s=document.getElementById(id);if(!s)return;
    s.innerHTML='';
    Object.entries(PAYS_DATA).forEach(([p,d])=>{
      const o=document.createElement('option');
      o.value=d.code;o.textContent=`${FLAGS[p]||''} ${d.code}`;
      if(d.code===code)o.selected=true;
      s.appendChild(o);
    });
  });
}

const defC='{{ old('country','Congo Brazzaville') }}';
const defV='{{ old('city','Pointe-Noire') }}';
buildCountry(document.getElementById('owner-country'),defC);
buildCity(document.getElementById('owner-city'),defC,defV);
buildIndicatifs(PAYS_DATA[defC]?.code||'+242');

document.getElementById('owner-country').addEventListener('change',function(){
  buildCity(document.getElementById('owner-city'),this.value);
  buildIndicatifs(PAYS_DATA[this.value]?.code||'+242');
});

// Type propriétaire
const btnP=document.getElementById('btn-particulier'),btnE=document.getElementById('btn-entreprise'),cc=document.getElementById('company-card');
function setType(t){
  const isE=t==='entreprise';
  btnP.classList.toggle('selected',!isE);btnE.classList.toggle('selected',isE);
  cc.style.display=isE?'':'none';
}
btnP.addEventListener('click',()=>{btnP.querySelector('input').value='Particulier';setType('particulier');});
btnE.addEventListener('click',()=>{btnE.querySelector('input').value='SARL';setType('entreprise');});
setType('{{ old('legal_form','Particulier') }}'==='Particulier'?'particulier':'entreprise');

function togglePwd(id,eid){
  const i=document.getElementById(id),e=document.getElementById(eid);
  i.type=i.type==='password'?'text':'password';
  e.className=i.type==='text'?'fas fa-eye-slash':'fas fa-eye';
}
</script>
@endsection
