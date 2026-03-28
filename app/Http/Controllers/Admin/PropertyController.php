<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyAmenity;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PropertyController extends Controller
{
    // ─── Upload image : Cloudinary en prod, local en dev ───────────────────
    private function uploadImage($file, int $propertyId): string
    {
        $cloudName = config('services.cloudinary.cloud_name');

        if ($cloudName) {
            try {
                $cloudinary = new CloudinaryService();
                return $cloudinary->upload($file, "immostay/properties/{$propertyId}");
            } catch (\Throwable $e) {
                Log::error('Cloudinary upload failed: ' . $e->getMessage());
            }
        }

        // Fallback : stockage local (dev)
        $path = $file->store('properties/' . $propertyId, 'public');
        return Storage::url($path);
    }

    public function index(Request $request)
    {
        $properties = Property::with(['owner', 'primaryImage'])
            ->when($request->search, fn($q, $v) => $q->where('title', 'like', "%$v%")
                ->orWhere('city', 'like', "%$v%"))
            ->when($request->type,   fn($q, $v) => $q->where('type', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->approved !== null && $request->approved !== '',
                fn($q) => $q->where('is_approved', (int) $request->approved))
            ->latest()->paginate(15);

        return view('admin.properties.index', compact('properties'));
    }

    public function create()
    {
        $owners = User::where('role', 'owner')
            ->with('ownerProfile')
            ->orderBy('name')
            ->get();
        return view('admin.properties.create', compact('owners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'required|string',
            'owner_id'    => 'required|exists:users,id',
            'type'        => 'required|string',
            'price'       => 'required|numeric|min:0',
            'price_period'=> 'required|string',
            'city'        => 'required|string',
            'country'     => 'required|string',
            'images'      => 'nullable|array|max:20',
            'images.*'    => 'nullable|image|max:5120',
        ]);

        $property = Property::create([
            'owner_id'          => $request->owner_id,
            'title'             => $request->title,
            'description'       => $request->description,
            'type'              => $request->type,
            'price'             => $request->price,
            'price_period'      => $request->price_period,
            'currency'          => $request->currency ?? 'XAF',
            'address'           => $request->address,
            'city'              => $request->city,
            'district'          => $request->district,
            'country'           => $request->country,
            'latitude'          => $request->latitude,
            'longitude'         => $request->longitude,
            'bedrooms'          => $request->bedrooms ?? 1,
            'bathrooms'         => $request->bathrooms ?? 1,
            'area'              => $request->area,
            'max_guests'        => $request->max_guests ?? 2,
            'status'            => $request->status ?? 'disponible',
            'is_featured'       => $request->has('is_featured'),
            'is_approved'       => (bool)($request->is_approved ?? false),
        ]);

        // Upload images → Cloudinary
        if ($request->hasFile('images')) {
            $sort = 0;
            foreach ($request->file('images') as $file) {
                $url = $this->uploadImage($file, $property->id);
                PropertyImage::create([
                    'property_id' => $property->id,
                    'url'         => $url,
                    'is_primary'  => $sort === 0,
                    'sort_order'  => $sort++,
                ]);
            }
        }

        // Save amenities as boolean flags
        $amenityFields = [
            'has_wifi','has_electricity','has_water','has_generator','has_security','has_parking',
            'has_clim','has_heating','has_pool','has_garden','has_elevator','has_balcony',
            'has_kitchen','has_laundry','has_tv','has_gym',
            'has_projector','has_visio','has_whiteboard','has_reception','has_kitchen_pro','has_printing',
            'has_sound_system','has_lighting','has_stage','has_dancefloor','has_catering','has_photo_service',
        ];

        $amenityLabels = [
            'has_wifi'=>'WiFi','has_electricity'=>'Électricité','has_water'=>'Eau courante',
            'has_generator'=>'Groupe électrogène','has_security'=>'Gardiennage','has_parking'=>'Parking',
            'has_clim'=>'Climatisation','has_heating'=>'Chauffage','has_pool'=>'Piscine',
            'has_garden'=>'Jardin','has_elevator'=>'Ascenseur','has_balcony'=>'Balcon',
            'has_kitchen'=>'Cuisine équipée','has_laundry'=>'Lave-linge','has_tv'=>'Télévision','has_gym'=>'Salle de sport',
            'has_projector'=>'Vidéoprojecteur','has_visio'=>'Visioconférence','has_whiteboard'=>'Tableau blanc',
            'has_reception'=>"Salle d'accueil",'has_kitchen_pro'=>'Cuisine pro','has_printing'=>'Imprimante',
            'has_sound_system'=>'Sono','has_lighting'=>'Éclairage déco','has_stage'=>'Scène',
            'has_dancefloor'=>'Piste de danse','has_catering'=>'Traiteur','has_photo_service'=>'Photo/Vidéo',
        ];

        foreach ($amenityFields as $field) {
            if ($request->has($field)) {
                PropertyAmenity::create([
                    'property_id' => $property->id,
                    'name'        => $amenityLabels[$field] ?? $field,
                    'icon'        => 'check-circle',
                ]);
            }
        }

        // Custom amenities
        if ($request->custom_amenities) {
            foreach (array_filter($request->custom_amenities) as $name) {
                PropertyAmenity::create([
                    'property_id' => $property->id,
                    'name'        => $name,
                    'icon'        => 'star',
                ]);
            }
        }

        return redirect()->route('admin.properties.index')
            ->with('success', 'Propriété enregistrée avec succès.');
    }

    public function show(string $id)
    {
        $property = Property::with(['owner', 'images', 'amenities', 'bookings.user', 'reviews.user'])
            ->findOrFail($id);
        return view('admin.properties.show', compact('property'));
    }

    public function edit(string $id)
    {
        $property = Property::with(['images', 'amenities'])->findOrFail($id);
        $owners   = User::where('role', 'owner')->with('ownerProfile')->orderBy('name')->get();
        return view('admin.properties.edit', compact('property', 'owners'));
    }

    public function update(Request $request, string $id)
    {
        $property = Property::findOrFail($id);

        $request->validate([
            'title'        => 'required|string|max:200',
            'description'  => 'required|string',
            'owner_id'     => 'required|exists:users,id',
            'type'         => 'required|string',
            'price'        => 'required|numeric|min:0',
            'price_period' => 'required|string',
            'city'         => 'required|string',
            'country'      => 'required|string',
            'images.*'     => 'nullable|image|max:5120',
        ]);

        $property->update($request->only([
            'owner_id','title','description','type','price','price_period','currency',
            'address','city','district','country','latitude','longitude',
            'bedrooms','bathrooms','area','max_guests','status','is_approved',
        ]));
        $property->is_featured = $request->has('is_featured');
        $property->save();

        // Nouvelles images → Cloudinary
        if ($request->hasFile('images')) {
            $sort = $property->images()->max('sort_order') + 1;
            foreach ($request->file('images') as $file) {
                $url = $this->uploadImage($file, $property->id);
                PropertyImage::create([
                    'property_id' => $property->id,
                    'url'         => $url,
                    'is_primary'  => $sort === 1 && $property->images()->count() === 0,
                    'sort_order'  => $sort++,
                ]);
            }
        }

        return redirect()->route('admin.properties.show', $property->id)
            ->with('success', 'Propriété mise à jour.');
    }

    public function approve(string $id)
    {
        Property::findOrFail($id)->update([
            'is_approved' => true,
            'status'      => 'disponible',
        ]);
        return back()->with('success', 'Propriété approuvée avec succès.');
    }

    public function destroy(string $id)
    {
        Property::findOrFail($id)->update([
            'status'      => 'suspendu',
            'is_approved' => false,
        ]);
        return back()->with('success', 'Propriété suspendue.');
    }
}

