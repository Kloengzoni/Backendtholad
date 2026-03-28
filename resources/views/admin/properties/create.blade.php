@extends('admin.layouts.app')
@section('title', 'Nouvelle propriété')

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.properties.index') }}" class="btn btn-outline btn-sm">
    <i class="fas fa-arrow-left"></i>
  </a>
  <div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);">Ajouter une propriété</h2>
    <p style="font-size:13px;color:var(--txt3);">Remplissez tous les champs requis selon le type de bien</p>
  </div>
</div>

<form method="POST" action="{{ route('admin.properties.store') }}" enctype="multipart/form-data" id="property-form">
@csrf

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:20px;">
  <i class="fas fa-exclamation-circle"></i>
  <ul style="margin:0;padding-left:16px;">
    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
  </ul>
</div>
@endif

<!-- ═══ ÉTAPE 1 : TYPE DE BIEN ═══ -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-header">
    <i class="fas fa-home" style="color:var(--tholad-blue);"></i>
    <h3>Type de bien</h3>
  </div>
  <div style="padding:22px;">
    <div class="form-group">
      <label>Type de propriété *</label>
      <div id="type-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-top:8px;">
        @php
        $types = [
          'appartement' => ['icon'=>'building','label'=>'Appartement'],
          'villa'       => ['icon'=>'home','label'=>'Villa'],
          'studio'      => ['icon'=>'door-open','label'=>'Studio'],
          'maison'      => ['icon'=>'house-user','label'=>'Maison'],
          'chambre'     => ['icon'=>'bed','label'=>'Chambre'],
          'bureau'      => ['icon'=>'briefcase','label'=>'Bureau'],
          'salle_reunion'=>['icon'=>'chalkboard-teacher','label'=>'Salle de réunion'],
          'salle_fete'  => ['icon'=>'glass-cheers','label'=>'Salle des fêtes'],
          'terrain'     => ['icon'=>'map','label'=>'Terrain'],
          'entrepot'    => ['icon'=>'warehouse','label'=>'Entrepôt'],
          'commerce'    => ['icon'=>'store','label'=>'Commerce'],
        ];
        @endphp
        @foreach($types as $val => $t)
        <label class="type-card" data-type="{{ $val }}">
          <input type="radio" name="type" value="{{ $val }}" {{ old('type','appartement')==$val?'checked':'' }} style="display:none">
          <i class="fas fa-{{ $t['icon'] }}" style="font-size:22px;margin-bottom:6px;"></i>
          <span>{{ $t['label'] }}</span>
        </label>
        @endforeach
      </div>
    </div>
  </div>
</div>

<div class="grid-2" style="align-items:start;">

<!-- ═══ COLONNE GAUCHE ═══ -->
<div>

  <!-- Informations générales -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <i class="fas fa-info-circle" style="color:var(--tholad-blue);"></i>
      <h3>Informations générales</h3>
    </div>
    <div style="padding:22px;">
      <div class="form-group">
        <label>Titre de l'annonce *</label>
        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required placeholder="Ex: Villa Luxe avec piscine — Plateau">
      </div>
      <div class="form-group">
        <label>Description détaillée *</label>
        <textarea name="description" class="form-control" rows="5" required placeholder="Décrivez la propriété en détail : style, environnement, équipements, règles de location...">{{ old('description') }}</textarea>
      </div>
      <div class="form-group">
        <label>Propriétaire *</label>
        <select name="owner_id" class="form-control" required>
          <option value="">— Sélectionner un propriétaire —</option>
          @foreach($owners as $owner)
            <option value="{{ $owner->id }}" {{ old('owner_id')==$owner->id?'selected':'' }}>
              {{ $owner->name }} — {{ $owner->email }}
              @if($owner->ownerProfile?->company_name) ({{ $owner->ownerProfile->company_name }}) @endif
            </option>
          @endforeach
        </select>
      </div>
    </div>
  </div>

  <!-- Prix et conditions -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <i class="fas fa-tags" style="color:var(--tholad-blue);"></i>
      <h3>Prix et conditions</h3>
    </div>
    <div style="padding:22px;">
      <div class="grid-2">
        <div class="form-group">
          <label>Prix *</label>
          <div style="position:relative;">
            <input type="number" name="price" class="form-control" value="{{ old('price') }}" required min="0" step="500" placeholder="0" style="padding-right:60px;">
            <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--txt3);font-size:12px;">XAF</span>
          </div>
        </div>
        <div class="form-group">
          <label>Période de facturation *</label>
          <select name="price_period" class="form-control" required>
            <option value="nuit"   {{ old('price_period')=='nuit'?'selected':'' }}>Par nuit</option>
            <option value="jour"   {{ old('price_period','jour')=='jour'?'selected':'' }}>Par jour</option>
            <option value="semaine"{{ old('price_period')=='semaine'?'selected':'' }}>Par semaine</option>
            <option value="mois"   {{ old('price_period')=='mois'?'selected':'' }}>Par mois</option>
            <option value="an"     {{ old('price_period')=='an'?'selected':'' }}>Par an</option>
            <option value="total"  {{ old('price_period')=='total'?'selected':'' }}>Prix total</option>
          </select>
        </div>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Devise</label>
          <select name="currency" class="form-control">
            <option value="XAF" {{ old('currency','XAF')=='XAF'?'selected':'' }}>XAF — Franc CFA</option>
            <option value="USD" {{ old('currency')=='USD'?'selected':'' }}>USD — Dollar américain</option>
            <option value="EUR" {{ old('currency')=='EUR'?'selected':'' }}>EUR — Euro</option>
            <option value="XOF" {{ old('currency')=='XOF'?'selected':'' }}>XOF — Franc CFA (UEMOA)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Caution / dépôt (XAF)</label>
          <input type="number" name="deposit" class="form-control" value="{{ old('deposit', 0) }}" min="0" step="500">
        </div>
      </div>
    </div>
  </div>

  <!-- Localisation -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <i class="fas fa-map-marker-alt" style="color:var(--tholad-blue);"></i>
      <h3>Localisation</h3>
    </div>
    <div style="padding:22px;">
      <div class="form-group">
        <label>Adresse complète</label>
        <input type="text" name="address" class="form-control" value="{{ old('address') }}" placeholder="N° rue, quartier...">
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Pays *</label>
          <select name="country" class="form-control" id="country-select-prop" required>
            <option value="">— Choisir un pays —</option>
          </select>
        </div>
        <div class="form-group">
          <label>Ville *</label>
          <select name="city" class="form-control" id="city-select-prop" required>
            <option value="">— Choisir d'abord un pays —</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Quartier / District</label>
        <input type="text" name="district" class="form-control" value="{{ old('district') }}" placeholder="Ex: Centre-ville, Plateau, Bacongo...">
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Latitude</label>
          <input type="number" name="latitude" class="form-control" value="{{ old('latitude') }}" step="0.0000001" placeholder="-4.2634...">
        </div>
        <div class="form-group">
          <label>Longitude</label>
          <input type="number" name="longitude" class="form-control" value="{{ old('longitude') }}" step="0.0000001" placeholder="15.2428...">
        </div>
      </div>
    </div>
  </div>

  <!-- Contact de la propriété -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <i class="fas fa-phone" style="color:var(--tholad-blue);"></i>
      <h3>Contact de la propriété</h3>
    </div>
    <div style="padding:22px;">
      <div class="grid-2">
        <div class="form-group">
          <label>Téléphone de contact</label>
          <div style="display:flex;gap:8px;">
            <select id="phone-indicatif-prop" style="width:100px;flex-shrink:0;" class="form-control">
              <option value="+242">🇨🇬 +242</option>
            </select>
            <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone') }}" placeholder="06 XXX XX XX" id="phone-number-prop">
          </div>
        </div>
        <div class="form-group">
          <label>Email de contact</label>
          <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email') }}" placeholder="contact@...">
        </div>
      </div>
    </div>
  </div>

</div>

<!-- ═══ COLONNE DROITE ═══ -->
<div>

  <!-- Photos de la propriété -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <i class="fas fa-images" style="color:var(--tholad-blue);"></i>
      <h3>Photos de la propriété</h3>
    </div>
    <div style="padding:22px;">
      <p style="font-size:12px;color:var(--txt3);margin-bottom:14px;">
        <i class="fas fa-info-circle"></i> Ajoutez entre 4 et 20 photos. La première sera l'image principale. Formats acceptés : JPG, PNG, WebP. Max 5 Mo par image.
      </p>

      <!-- Zone de drag & drop -->
      <div id="image-dropzone" onclick="document.getElementById('images-input').click()"
           style="border:2px dashed var(--border);border-radius:12px;padding:30px;text-align:center;cursor:pointer;background:var(--bg-soft);transition:all .2s;margin-bottom:16px;">
        <i class="fas fa-cloud-upload-alt" style="font-size:32px;color:var(--tholad-blue);margin-bottom:10px;display:block;"></i>
        <div style="font-weight:600;color:var(--navy);margin-bottom:4px;">Cliquez ou glissez vos photos ici</div>
        <div style="font-size:12px;color:var(--txt3);">JPG, PNG, WebP — minimum 4 photos recommandées</div>
      </div>
      <input type="file" name="images[]" id="images-input" multiple accept="image/jpeg,image/png,image/webp" style="display:none">

      <!-- Prévisualisation -->
      <div id="image-preview-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;"></div>
      <div id="image-count-msg" style="font-size:12px;color:var(--txt3);margin-top:8px;text-align:center;"></div>
    </div>
  </div>

  <!-- Caractéristiques générales -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <i class="fas fa-sliders-h" style="color:var(--tholad-blue);"></i>
      <h3>Caractéristiques</h3>
    </div>
    <div style="padding:22px;">

      <!-- Champs pour habitations (appartement, villa, studio, maison, chambre) -->
      <div id="fields-habitation" class="type-fields">
        <div class="grid-2">
          <div class="form-group">
            <label><i class="fas fa-bed" style="color:var(--tholad-blue);"></i> Chambres</label>
            <input type="number" name="bedrooms" class="form-control" value="{{ old('bedrooms',1) }}" min="0" max="50">
          </div>
          <div class="form-group">
            <label><i class="fas fa-bath" style="color:var(--tholad-blue);"></i> Salles de bain</label>
            <input type="number" name="bathrooms" class="form-control" value="{{ old('bathrooms',1) }}" min="0" max="30">
          </div>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label><i class="fas fa-users" style="color:var(--tholad-blue);"></i> Personnes max</label>
            <input type="number" name="max_guests" class="form-control" value="{{ old('max_guests',2) }}" min="1" max="200">
          </div>
          <div class="form-group">
            <label><i class="fas fa-ruler-combined" style="color:var(--tholad-blue);"></i> Superficie (m²)</label>
            <input type="number" name="area" class="form-control" value="{{ old('area') }}" min="0" step="0.5" placeholder="0">
          </div>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Étage</label>
            <select name="floor" class="form-control">
              <option value="">—</option>
              <option value="rdc" {{ old('floor')=='rdc'?'selected':'' }}>Rez-de-chaussée</option>
              @for($i=1;$i<=30;$i++)
              <option value="{{ $i }}" {{ old('floor')==$i?'selected':'' }}>{{ $i }}{{ $i==1?'er':'ème' }} étage</option>
              @endfor
            </select>
          </div>
          <div class="form-group">
            <label>Vue</label>
            <select name="view_type" class="form-control">
              <option value="">—</option>
              <option value="fleuve"   {{ old('view_type')=='fleuve'?'selected':'' }}>Vue fleuve / mer</option>
              <option value="ville"    {{ old('view_type')=='ville'?'selected':'' }}>Vue ville</option>
              <option value="jardin"   {{ old('view_type')=='jardin'?'selected':'' }}>Vue jardin</option>
              <option value="piscine"  {{ old('view_type')=='piscine'?'selected':'' }}>Vue piscine</option>
              <option value="montagne" {{ old('view_type')=='montagne'?'selected':'' }}>Vue montagne</option>
              <option value="interieur"{{ old('view_type')=='interieur'?'selected':'' }}>Vue intérieure</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Champs pour bureaux/salles de réunion -->
      <div id="fields-bureau" class="type-fields" style="display:none;">
        <div class="grid-2">
          <div class="form-group">
            <label><i class="fas fa-users" style="color:var(--tholad-blue);"></i> Capacité (personnes)</label>
            <input type="number" name="capacity" class="form-control" value="{{ old('capacity',10) }}" min="1" max="2000">
          </div>
          <div class="form-group">
            <label><i class="fas fa-ruler-combined" style="color:var(--tholad-blue);"></i> Superficie (m²)</label>
            <input type="number" name="area" class="form-control" value="{{ old('area') }}" min="0" step="0.5">
          </div>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Étage</label>
            <select name="floor_bureau" class="form-control">
              <option value="">—</option>
              <option value="rdc">Rez-de-chaussée</option>
              @for($i=1;$i<=30;$i++)
              <option value="{{ $i }}">{{ $i }}{{ $i==1?'er':'ème' }} étage</option>
              @endfor
            </select>
          </div>
          <div class="form-group">
            <label>Nombre de postes de travail</label>
            <input type="number" name="workstations" class="form-control" value="{{ old('workstations') }}" min="0">
          </div>
        </div>
      </div>

      <!-- Champs pour terrain -->
      <div id="fields-terrain" class="type-fields" style="display:none;">
        <div class="grid-2">
          <div class="form-group">
            <label><i class="fas fa-ruler-combined" style="color:var(--tholad-blue);"></i> Superficie (m²) *</label>
            <input type="number" name="area_terrain" class="form-control" value="{{ old('area_terrain') }}" min="0" step="1" placeholder="0">
          </div>
          <div class="form-group">
            <label>Type de terrain</label>
            <select name="terrain_type" class="form-control">
              <option value="">—</option>
              <option value="constructible" {{ old('terrain_type')=='constructible'?'selected':'' }}>Constructible</option>
              <option value="agricole"      {{ old('terrain_type')=='agricole'?'selected':'' }}>Agricole</option>
              <option value="commercial"    {{ old('terrain_type')=='commercial'?'selected':'' }}>Commercial</option>
              <option value="industriel"    {{ old('terrain_type')=='industriel'?'selected':'' }}>Industriel</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Titre foncier</label>
          <input type="text" name="land_title" class="form-control" value="{{ old('land_title') }}" placeholder="N° TF...">
        </div>
      </div>

    </div>
  </div>

  <!-- Équipements & Services -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <i class="fas fa-concierge-bell" style="color:var(--tholad-blue);"></i>
      <h3>Équipements & Services</h3>
    </div>
    <div style="padding:22px;">

      <!-- Commodités essentielles -->
      <div style="margin-bottom:16px;">
        <div style="font-size:12px;font-weight:600;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Essentiels</div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
          @php
          $essentials = [
            'has_wifi'         => ['WiFi / Internet','wifi'],
            'has_electricity'  => ['Électricité','bolt'],
            'has_water'        => ['Eau courante','tint'],
            'has_generator'    => ['Groupe électrogène','charging-station'],
            'has_security'     => ['Gardiennage / Sécurité','shield-alt'],
            'has_parking'      => ['Parking','parking'],
          ];
          @endphp
          @foreach($essentials as $key => $item)
          <label class="amenity-checkbox">
            <input type="checkbox" name="{{ $key }}" value="1" {{ old($key)?'checked':'' }}>
            <i class="fas fa-{{ $item[1] }}"></i>
            <span>{{ $item[0] }}</span>
          </label>
          @endforeach
        </div>
      </div>

      <!-- Confort -->
      <div id="amenities-confort" style="margin-bottom:16px;" class="type-fields">
        <div style="font-size:12px;font-weight:600;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Confort</div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
          @php
          $comfort = [
            'has_clim'        => ['Climatisation','wind'],
            'has_heating'     => ['Chauffage','fire'],
            'has_pool'        => ['Piscine','swimming-pool'],
            'has_garden'      => ['Jardin / Terrasse','leaf'],
            'has_elevator'    => ['Ascenseur','chevron-circle-up'],
            'has_balcony'     => ['Balcon / Véranda','archway'],
            'has_kitchen'     => ['Cuisine équipée','utensils'],
            'has_laundry'     => ['Lave-linge / Blanchisserie','tshirt'],
            'has_tv'          => ['Télévision','tv'],
            'has_gym'         => ['Salle de sport','dumbbell'],
          ];
          @endphp
          @foreach($comfort as $key => $item)
          <label class="amenity-checkbox">
            <input type="checkbox" name="{{ $key }}" value="1" {{ old($key)?'checked':'' }}>
            <i class="fas fa-{{ $item[1] }}"></i>
            <span>{{ $item[0] }}</span>
          </label>
          @endforeach
        </div>
      </div>

      <!-- Bureaux/réunions spécifiques -->
      <div id="amenities-bureau" style="margin-bottom:16px;display:none;">
        <div style="font-size:12px;font-weight:600;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Équipements professionnels</div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
          @php
          $pro = [
            'has_projector'    => ['Vidéoprojecteur','chalkboard-teacher'],
            'has_visio'        => ['Visioconférence','video'],
            'has_whiteboard'   => ['Tableau blanc','edit'],
            'has_reception'    => ['Salle d\'accueil','concierge-bell'],
            'has_kitchen_pro'  => ['Cuisine / Cafétéria','coffee'],
            'has_printing'     => ['Imprimante / Copie','print'],
          ];
          @endphp
          @foreach($pro as $key => $item)
          <label class="amenity-checkbox">
            <input type="checkbox" name="{{ $key }}" value="1" {{ old($key)?'checked':'' }}>
            <i class="fas fa-{{ $item[1] }}"></i>
            <span>{{ $item[0] }}</span>
          </label>
          @endforeach
        </div>
      </div>

      <!-- Événements/fêtes -->
      <div id="amenities-fete" style="margin-bottom:16px;display:none;">
        <div style="font-size:12px;font-weight:600;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Équipements événementiels</div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
          @php
          $event = [
            'has_sound_system'  => ['Sono / Musique','music'],
            'has_lighting'      => ['Éclairage déco','lightbulb'],
            'has_stage'         => ['Scène / Podium','theater-masks'],
            'has_dancefloor'    => ['Piste de danse','compact-disc'],
            'has_catering'      => ['Service traiteur','utensils'],
            'has_photo_service' => ['Photo / Vidéo','camera'],
          ];
          @endphp
          @foreach($event as $key => $item)
          <label class="amenity-checkbox">
            <input type="checkbox" name="{{ $key }}" value="1" {{ old($key)?'checked':'' }}>
            <i class="fas fa-{{ $item[1] }}"></i>
            <span>{{ $item[0] }}</span>
          </label>
          @endforeach
        </div>
      </div>

      <!-- Équipements personnalisés -->
      <div>
        <div style="font-size:12px;font-weight:600;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Équipements supplémentaires</div>
        <div id="custom-amenities-list" style="margin-bottom:10px;"></div>
        <button type="button" onclick="addCustomAmenity()" class="btn btn-outline btn-sm">
          <i class="fas fa-plus"></i> Ajouter un équipement
        </button>
      </div>

    </div>
  </div>

  <!-- Statut et paramètres -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <i class="fas fa-cog" style="color:var(--tholad-blue);"></i>
      <h3>Statut & Paramètres</h3>
    </div>
    <div style="padding:22px;">
      <div class="grid-2">
        <div class="form-group">
          <label>Statut initial</label>
          <select name="status" class="form-control">
            <option value="disponible" {{ old('status','disponible')=='disponible'?'selected':'' }}>Disponible</option>
            <option value="occupé"     {{ old('status')=='occupé'?'selected':'' }}>Occupé</option>
            <option value="maintenance"{{ old('status')=='maintenance'?'selected':'' }}>En maintenance</option>
            <option value="suspendu"   {{ old('status')=='suspendu'?'selected':'' }}>Suspendu</option>
          </select>
        </div>
        <div class="form-group">
          <label>Approbation</label>
          <select name="is_approved" class="form-control">
            <option value="0" {{ old('is_approved',0)==0?'selected':'' }}>En attente d'approbation</option>
            <option value="1" {{ old('is_approved')==1?'selected':'' }}>Approuver immédiatement</option>
          </select>
        </div>
      </div>
      <label class="amenity-checkbox" style="margin-top:8px;">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured')?'checked':'' }}>
        <i class="fas fa-star"></i>
        <span>Mettre en avant (propriété vedette)</span>
      </label>
      <div class="form-group" style="margin-top:16px;">
        <label>Règlement intérieur / Conditions</label>
        <textarea name="rules" class="form-control" rows="3" placeholder="Pas d'animaux, check-in 14h, check-out 12h...">{{ old('rules') }}</textarea>
      </div>
    </div>
  </div>

</div>
</div>

<div style="display:flex;gap:12px;margin-top:4px;margin-bottom:40px;">
  <button type="submit" class="btn btn-primary">
    <i class="fas fa-save"></i> Enregistrer la propriété
  </button>
  <a href="{{ route('admin.properties.index') }}" class="btn btn-outline">Annuler</a>
</div>

</form>

<style>
.type-card {
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:14px 10px;border:2px solid var(--border);border-radius:10px;
  cursor:pointer;transition:all .2s;text-align:center;font-size:12px;font-weight:500;
  color:var(--txt2);background:var(--bg-soft);
}
.type-card:hover { border-color:var(--tholad-blue);color:var(--tholad-blue); }
.type-card.selected {
  border-color:var(--tholad-blue);background:#EEF2FF;
  color:var(--tholad-blue);font-weight:600;
}
.type-card i { color:inherit; }

.amenity-checkbox {
  display:flex;align-items:center;gap:8px;padding:8px 10px;
  border:1px solid var(--border);border-radius:8px;cursor:pointer;
  font-size:13px;transition:all .15s;
}
.amenity-checkbox:hover { border-color:var(--tholad-blue);background:#F0F4FF; }
.amenity-checkbox input:checked + i + span,
.amenity-checkbox input:checked ~ i { color:var(--tholad-blue); }
.amenity-checkbox input { accent-color:var(--tholad-blue); width:15px;height:15px; }
.amenity-checkbox i { color:var(--txt3);width:14px;font-size:13px; }

#image-dropzone:hover { border-color:var(--tholad-blue);background:#EEF2FF; }
.img-preview-item {
  position:relative;border-radius:8px;overflow:hidden;aspect-ratio:4/3;
}
.img-preview-item img { width:100%;height:100%;object-fit:cover; }
.img-preview-item .img-badge {
  position:absolute;top:4px;left:4px;background:var(--tholad-blue);
  color:#fff;font-size:10px;padding:2px 6px;border-radius:4px;font-weight:600;
}
.img-preview-item .img-remove {
  position:absolute;top:4px;right:4px;background:rgba(0,0,0,.6);
  color:#fff;border:none;border-radius:50%;width:22px;height:22px;
  cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;
}
</style>

<script>
// ── Données pays/villes/indicatifs ───────────────────────────────
const PAYS_DATA = {
  "Congo Brazzaville": { code:"+242", villes:["Brazzaville","Pointe-Noire","Dolisie","Nkayi","Impfondo","Ouesso","Owando","Mossendjo","Madingou","Kinkala","Sibiti","Gamboma","Djambala","Boundji","Ewo"] },
  "Congo RDC":         { code:"+243", villes:["Kinshasa","Lubumbashi","Mbuji-Mayi","Kisangani","Goma","Bukavu","Kananga","Matadi","Kolwezi","Likasi"] },
  "Gabon":             { code:"+241", villes:["Libreville","Port-Gentil","Franceville","Oyem","Moanda","Mouila","Lambaréné","Tchibanga","Koulamoutou","Makokou"] },
  "Cameroun":          { code:"+237", villes:["Yaoundé","Douala","Garoua","Bamenda","Bafoussam","Ngaoundéré","Bertoua","Loum","Kumba","Édéa"] },
  "Côte d'Ivoire":     { code:"+225", villes:["Abidjan","Yamoussoukro","Bouaké","Daloa","Korhogo","Man","San-Pédro","Divo","Gagnoa","Abengourou"] },
  "Sénégal":           { code:"+221", villes:["Dakar","Thiès","Kaolack","Ziguinchor","Saint-Louis","Touba","Mbour","Rufisque","Diourbel","Tambacounda"] },
  "Mali":              { code:"+223", villes:["Bamako","Sikasso","Ségou","Mopti","Koutiala","Gao","Kayes","Kidal","Kolokani","Bougouni"] },
  "Guinée":            { code:"+224", villes:["Conakry","Kankan","Labé","Kindia","Nzérékoré","Mamou","Boke","Faranah","Siguiri","Fria"] },
  "Tchad":             { code:"+235", villes:["N'Djamena","Moundou","Sarh","Abéché","Kélo","Koumra","Pala","Am Timan","Bongor","Mongo"] },
  "Centrafrique":      { code:"+236", villes:["Bangui","Berbérati","Carnot","Bambari","Bouar","Bossangoa","Bria","Kaga-Bandoro","Nola","Bossembélé"] },
  "Angola":            { code:"+244", villes:["Luanda","Huambo","Lobito","Benguela","Kuito","Lubango","Malanje","Namibe","Soyo","Cabinda"] },
  "France":            { code:"+33",  villes:["Paris","Marseille","Lyon","Toulouse","Nice","Nantes","Strasbourg","Montpellier","Bordeaux","Lille"] },
  "Belgique":          { code:"+32",  villes:["Bruxelles","Anvers","Gand","Charleroi","Liège","Bruges","Namur","Leuven","Mons","Aalst"] },
  "Togo":              { code:"+228", villes:["Lomé","Sokodé","Kpalimé","Atakpamé","Dapaong","Kara","Tsévié","Aného","Mango","Bassar"] },
  "Bénin":             { code:"+229", villes:["Cotonou","Porto-Novo","Parakou","Abomey","Bohicon","Kandi","Lokossa","Ouidah","Djougou","Natitingou"] },
  "Burkina Faso":      { code:"+226", villes:["Ouagadougou","Bobo-Dioulasso","Koudougou","Banfora","Ouahigouya","Pouytenga","Dédougou","Fada N'Gourma","Tenkodogo","Kaya"] },
  "Rwanda":            { code:"+250", villes:["Kigali","Butare","Gitarama","Ruhengeri","Gisenyi","Byumba","Cyangugu","Kibungo","Rwamagana","Nyanza"] },
  "Burundi":           { code:"+257", villes:["Bujumbura","Gitega","Muyinga","Ngozi","Rumonge","Kirundo","Makamba","Cibitoke","Kayanza","Muramvya"] },
  "Madagascar":        { code:"+261", villes:["Antananarivo","Toamasina","Antsirabe","Fianarantsoa","Mahajanga","Toliara","Antsiranana","Ambovombe","Morondava","Manakara"] },
  "Maroc":             { code:"+212", villes:["Casablanca","Rabat","Fès","Marrakech","Agadir","Tanger","Meknès","Oujda","Kénitra","Tétouan"] },
};

function buildCountrySelect(selectEl, defaultCountry = '') {
  selectEl.innerHTML = '<option value="">— Choisir un pays —</option>';
  Object.keys(PAYS_DATA).sort().forEach(pays => {
    const opt = document.createElement('option');
    opt.value = pays;
    opt.textContent = pays;
    if (pays === defaultCountry) opt.selected = true;
    selectEl.appendChild(opt);
  });
}

function buildCitySelect(cityEl, country, defaultCity = '') {
  cityEl.innerHTML = '<option value="">— Choisir une ville —</option>';
  if (country && PAYS_DATA[country]) {
    PAYS_DATA[country].villes.forEach(v => {
      const opt = document.createElement('option');
      opt.value = v; opt.textContent = v;
      if (v === defaultCity) opt.selected = true;
      cityEl.appendChild(opt);
    });
  }
}

function buildIndicatifSelect(selectEl, defaultCode = '+242') {
  selectEl.innerHTML = '';
  const flags = {"Congo Brazzaville":"🇨🇬","Congo RDC":"🇨🇩","Gabon":"🇬🇦","Cameroun":"🇨🇲","Côte d'Ivoire":"🇨🇮","Sénégal":"🇸🇳","Mali":"🇲🇱","Guinée":"🇬🇳","Tchad":"🇹🇩","Centrafrique":"🇨🇫","Angola":"🇦🇴","France":"🇫🇷","Belgique":"🇧🇪","Togo":"🇹🇬","Bénin":"🇧🇯","Burkina Faso":"🇧🇫","Rwanda":"🇷🇼","Burundi":"🇧🇮","Madagascar":"🇲🇬","Maroc":"🇲🇦"};
  Object.entries(PAYS_DATA).forEach(([pays, data]) => {
    const opt = document.createElement('option');
    opt.value = data.code;
    opt.textContent = `${flags[pays]||''} ${data.code}`;
    if (data.code === defaultCode) opt.selected = true;
    selectEl.appendChild(opt);
  });
}

// Init property form selects
const csProp = document.getElementById('country-select-prop');
const vsProp = document.getElementById('city-select-prop');
const indProp = document.getElementById('phone-indicatif-prop');

buildCountrySelect(csProp, '{{ old('country','Congo Brazzaville') }}');
buildCitySelect(vsProp, '{{ old('country','Congo Brazzaville') }}', '{{ old('city','Pointe-Noire') }}');
buildIndicatifSelect(indProp, '+242');

csProp.addEventListener('change', function() {
  buildCitySelect(vsProp, this.value);
  if (PAYS_DATA[this.value]) {
    indProp.value = PAYS_DATA[this.value].code;
  }
});

// ── Type de bien dynamique ───────────────────────────────────────
const typeCards = document.querySelectorAll('.type-card');
const fieldsHab  = document.getElementById('fields-habitation');
const fieldsBur  = document.getElementById('fields-bureau');
const fieldsTer  = document.getElementById('fields-terrain');
const amenBur    = document.getElementById('amenities-bureau');
const amenFete   = document.getElementById('amenities-fete');
const amenConf   = document.getElementById('amenities-confort');

const habitTypes   = ['appartement','villa','studio','maison','chambre'];
const bureauTypes  = ['bureau','salle_reunion'];
const feteTypes    = ['salle_fete'];
const terrainTypes = ['terrain','entrepot','commerce'];

function switchType(type) {
  typeCards.forEach(c => c.classList.toggle('selected', c.dataset.type === type));

  // Champs caractéristiques
  fieldsHab.style.display  = habitTypes.includes(type)  ? '' : 'none';
  fieldsBur.style.display  = bureauTypes.includes(type) ? '' : 'none';
  fieldsTer.style.display  = terrainTypes.includes(type)? '' : 'none';
  if (feteTypes.includes(type)) { fieldsHab.style.display=''; fieldsBur.style.display=''; }

  // Équipements contextuels
  amenBur.style.display  = [...bureauTypes,'salle_fete'].includes(type) ? '' : 'none';
  amenFete.style.display = feteTypes.includes(type) ? '' : 'none';
  amenConf.style.display = terrainTypes.includes(type) ? 'none' : '';
}

typeCards.forEach(card => {
  card.addEventListener('click', () => {
    card.querySelector('input').checked = true;
    switchType(card.dataset.type);
  });
});

// Init with current type
switchType('{{ old('type','appartement') }}');

// ── Prévisualisation images ──────────────────────────────────────
const imageInput = document.getElementById('images-input');
const previewGrid = document.getElementById('image-preview-grid');
const countMsg = document.getElementById('image-count-msg');
let selectedFiles = [];

imageInput.addEventListener('change', function() {
  const newFiles = Array.from(this.files);
  selectedFiles = [...selectedFiles, ...newFiles].slice(0, 20);
  renderPreviews();
});

// Drag & drop
const dropzone = document.getElementById('image-dropzone');
dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.style.borderColor='var(--tholad-blue)'; });
dropzone.addEventListener('dragleave', () => { dropzone.style.borderColor='var(--border)'; });
dropzone.addEventListener('drop', e => {
  e.preventDefault();
  dropzone.style.borderColor='var(--border)';
  const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
  selectedFiles = [...selectedFiles, ...files].slice(0, 20);
  renderPreviews();
});

function renderPreviews() {
  previewGrid.innerHTML = '';
  selectedFiles.forEach((file, idx) => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.className = 'img-preview-item';
      div.innerHTML = `
        <img src="${e.target.result}" alt="">
        ${idx===0 ? '<span class="img-badge">Principale</span>' : ''}
        <button type="button" class="img-remove" onclick="removeImage(${idx})">✕</button>`;
      previewGrid.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
  const c = selectedFiles.length;
  countMsg.textContent = c > 0 ? `${c} photo${c>1?'s':''} sélectionnée${c>1?'s':''}${c<4?' — Recommandé : au moins 4':''}` : '';
  countMsg.style.color = c < 4 && c > 0 ? 'var(--coral)' : 'var(--txt3)';
}

function removeImage(idx) {
  selectedFiles.splice(idx, 1);
  renderPreviews();
}

// ── Équipements personnalisés ────────────────────────────────────
let customCount = 0;
function addCustomAmenity() {
  customCount++;
  const div = document.createElement('div');
  div.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;align-items:center;';
  div.innerHTML = `
    <input type="text" name="custom_amenities[]" class="form-control" placeholder="Ex: Jacuzzi, Roof top..." style="flex:1;">
    <button type="button" onclick="this.parentNode.remove()" class="btn btn-outline btn-sm" style="padding:6px 10px;color:var(--coral)">
      <i class="fas fa-times"></i>
    </button>`;
  document.getElementById('custom-amenities-list').appendChild(div);
}
</script>

@endsection
