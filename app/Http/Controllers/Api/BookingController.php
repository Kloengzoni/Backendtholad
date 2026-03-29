<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyAvailability;
use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['property.primaryImage', 'payment'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $bookings->map(fn($b) => $this->bookingResource($b)),
            'meta'    => ['total' => $bookings->total(), 'last_page' => $bookings->lastPage()],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'check_in'    => 'required|date|after_or_equal:today',
            'check_out'   => 'required|date|after:check_in',
            'guests'      => 'required|integer|min:1',
            'notes'       => 'nullable|string|max:500',
            'options'     => 'nullable|array',
            'options.*'   => 'nullable|string',
        ]);

        $property = Property::findOrFail($request->property_id);

        if ($property->status !== 'disponible' || !$property->is_approved) {
            return response()->json([
                'success' => false,
                'message' => "Ce bien n'est pas disponible.",
            ], 422);
        }

        if ($property->max_guests && $request->guests > $property->max_guests) {
            return response()->json([
                'success' => false,
                'message' => "Ce bien accueille maximum {$property->max_guests} personne(s).",
            ], 422);
        }

        $checkIn  = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $nights   = $checkIn->diffInDays($checkOut);

        if ($nights < 1) {
            return response()->json([
                'success' => false,
                'message' => "La durée du séjour doit être d'au moins 1 nuit.",
            ], 422);
        }

        $conflict = PropertyAvailability::where('property_id', $request->property_id)
            ->whereBetween('unavailable_date', [
                $checkIn->toDateString(),
                $checkOut->clone()->subDay()->toDateString(),
            ])->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Ces dates ne sont pas disponibles.',
            ], 409);
        }

        $price = (float) ($property->price ?? 0);
        if ($price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Le prix de ce bien est invalide.',
            ], 422);
        }

        $baseAmount = round($price * $nights, 2);
        $feesAmount = round($baseAmount * 0.05, 2);
        $total      = $baseAmount + $feesAmount;

        $booking = Booking::create([
            'user_id'      => $request->user()->id,
            'property_id'  => $request->property_id,
            'check_in'     => $request->check_in,
            'check_out'    => $request->check_out,
            'nights'       => $nights,
            'guests'       => $request->guests,
            'base_amount'  => $baseAmount,
            'fees_amount'  => $feesAmount,
            'total_amount' => $total,
            'currency'     => $property->currency ?? 'XAF',
            'status'       => 'en_attente',
            'notes'        => $request->notes,
        ]);

        Notification::create([
            'user_id' => $request->user()->id,
            'title'   => 'Réservation créée 📅',
            'body'    => "Votre réservation {$booking->reference} a été créée. Procédez au paiement.",
            'type'    => 'booking',
            'data'    => ['booking_id' => $booking->id, 'reference' => $booking->reference],
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->bookingResource($booking->load(['property', 'user'])),
            'message' => 'Réservation créée. Procédez au paiement.',
        ], 201);
    }

    public function show(Request $request, string $ref)
    {
        $booking = Booking::with(['property.images', 'user', 'payment', 'review'])
            ->where('reference', $ref)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $this->bookingResource($booking)]);
    }

    public function cancel(Request $request, string $ref)
    {
        $booking = Booking::where('reference', $ref)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($booking->status, ['en_attente', 'confirmé'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation ne peut pas être annulée.',
            ], 422);
        }

        $booking->update([
            'status'        => 'annulé',
            'cancel_reason' => $request->reason ?? 'Annulé par le client',
            'cancelled_at'  => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Réservation annulée.']);
    }

    public function confirm(Request $request, string $ref)
    {
        if (!$request->user()->isAdmin() && !$request->user()->isOwner()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        Booking::where('reference', $ref)->firstOrFail()->update(['status' => 'confirmé']);

        return response()->json(['success' => true, 'message' => 'Réservation confirmée.']);
    }

    // ── Ressource ─────────────────────────────────────────────────────────────
    private function bookingResource(Booking $b): array
    {
        return [
            'id'             => $b->id,
            'reference'      => $b->reference,
            'ref'            => $b->reference,
            // FIX CRITIQUE : retourner en format ISO (yyyy-mm-dd)
            // et non en "dd/mm/yyyy" qui crashe DateTime.parse() côté Flutter
            'check_in'       => $b->check_in?->format('Y-m-d'),
            'check_out'      => $b->check_out?->format('Y-m-d'),
            'nights'         => $b->nights,
            'guests'         => $b->guests,
            'base_amount'    => (float) $b->base_amount,
            'fees_amount'    => (float) $b->fees_amount,   // FIX: fees_amount (pas services_fee)
            'total_amount'   => (float) $b->total_amount,
            'currency'       => $b->currency ?? 'XAF',
            'status'         => $b->status,
            'notes'          => $b->notes,
            'created_at'     => $b->created_at?->toISOString(),
            'payment_status' => $b->payment?->status ?? 'non_payé',
            'property'       => $b->property ? [
                'id'          => $b->property->id,
                'title'       => $b->property->title,
                'name'        => $b->property->title,
                'city'        => $b->property->city,
                'image_url'   => $b->property->primaryImage?->url,
                'cover_image' => $b->property->primaryImage?->url,
            ] : null,
        ];
    }
}
