<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\CampaignCall;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::query();

        $query->with('calls');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%$search%");
        }

        return $query->paginate();
    }

    public function show(Request $request, Campaign $campaign)
    {
        $campaign->append('information');
        $campaign->loadMissing(['calls.appointment']);
        return $campaign;
    }

    public function store(Request $request)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'prompt' => 'required|string',
            'file' => 'required|file|mimes:pdf',
            'phone_numbers' => 'required|array',
            'phone_numbers.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Guardar el archivo en el disco 'public' dentro de la carpeta 'campaign_files'
        $file = $request->file('file');
        $filePath = $file?->store('campaign_files', 'public');

        /** @var Campaign $campaign */
        $campaign = Campaign::create([
            'name' => $request->name,
            'prompt' => $request->prompt,
            'file_path' => $filePath,
            'start_date' => $request->start_date,
        ]);

        // Crear un registro en campaign_contacts para cada número telefónico
        foreach ($request->phone_numbers as $phone) {
            $campaign->calls()->create([
                'phone_number' => $phone,
            ]);
        }

        return $campaign;
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'prompt' => 'required|string',
            'file' => 'sometimes|file|mimes:pdf',
            'phone_numbers' => 'required|array',
            'phone_numbers.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Guardar el archivo en el disco 'public' dentro de la carpeta 'campaign_files'
        $file = $request->file('file');
        $filePath = $file?->store('campaign_files', 'public');
        if ($filePath && $campaign->file_path) {
            Storage::delete($campaign->file_path);
        }

        $campaign->update([
            'name' => $request->name,
            'prompt' => $request->prompt,
            'file_path' => $filePath ?? $campaign->file_path,
            'start_date' => $request->start_date,
        ]);

        foreach ($request->phone_numbers as $phone) {
            if (!$campaign->calls()->where('phone_number', $phone)->exists()) {
                $campaign->calls()->create([
                    'phone_number' => $phone,
                ]);
            }
        }

        return $campaign;
    }

    public function makeTestCall(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'phone_number' => ['required']
        ]);

        $phoneNumber = $validated['phone_number'];
        $successful = $this->makePhoneCall($phoneNumber, [
            'campaign_id' => $campaign->id
        ]);
        if (!$successful) {
            abort(response([
                'message' => "No fue posible realizar una llamada al numero $phoneNumber"
            ], 502));
        }
        return $campaign->calls()->create([
            'is_test' => true,
            'phone_number' => $phoneNumber
        ]);
    }

    private function makePhoneCall($phoneNumber, $data = [])
    {
        $payload = [
            'to'            => '+' . $phoneNumber,
            'from'          => config('services.telnyx.from_number'),
            'connection_id' => config('services.telnyx.connection_id'),
            'stream_url'    => config('services.telnyx.websocket_url') . '/?' . http_build_query($data),
            // 'stream_track' => 'both_tracks',
        ];
        // Realizar la solicitud a Telnyx
        $apiKey = config('services.telnyx.key');
        $response = Http::baseUrl('https://api.telnyx.com/v2')
            ->withHeader('Authorization', "Bearer $apiKey")
            ->acceptJson()
            ->asJson()
            ->post('calls', $payload);

        logger("Telnyx response", [
            $response->status(),
            $response->json()
        ]);

        if ($response->successful()) {
            logger("Llamada iniciada para el teléfono: {$phoneNumber}");

            return true;
        } else {
            logger("No se pudo iniciar la llamada para el teléfono: {$phoneNumber}");

            return false;
        }
    }
}
