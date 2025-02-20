<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
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

    public function store(Request $request)
    {
        // Validar la solicitud
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
                'campaign_id'  => $campaign->id,
                'phone_number' => $phone,
            ]);
        }

        return response()->json([
            'message' => 'Campaña creada exitosamente.',
            'campaign' => $campaign,
        ], 201);
    }

    public function show(Request $request, Campaign $campaign)
    {
        $campaign->append('information');
        return $campaign;
    }
}
