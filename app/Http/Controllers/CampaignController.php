<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateGreetingAudio;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\CampaignCall;
use App\Services\CallServiceContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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

    protected function validateCampaign(Request $request, ?Campaign $campaign = null)
    {
        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'status' => 'required|in:' . implode(',', [Campaign::ACTIVE_STATUS, Campaign::PENDING_STATUS]),
            'prompt' => 'required|string',
            'greeting' => 'required|string',
            'file' => [$campaign ? 'sometimes' : 'required', 'file', 'mimes:pdf'],
            'phone_numbers' => [$campaign ? 'sometimes' : 'required', 'array'],
            'phone_numbers.*' => [$campaign ? 'sometimes' : 'required', 'string'],
        ]);

        $status = $payload['status'];
        if ($status == Campaign::ACTIVE_STATUS && $campaign?->start_date === null) {
            $payload['start_date'] = Date::today();
        }

        $file = Arr::get($payload, 'file');
        if ($file) {
            $filePath = $file->store('campaign_files', 'public');
            $payload['file_path'] = $filePath;
        }

        $payload['phone_numbers'] ??= [];

        return Arr::only($payload, [
            'name',
            'start_date',
            'status',
            'phone_numbers',

            'prompt',
            'greeting',
            'file_path'
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCampaign($request);

        return $this->transaction(function () use ($data) {
            $campaign = Campaign::create(Arr::except($data, 'phone_numbers'));
            foreach ($data['phone_numbers'] as $phone) {
                $campaign->calls()->create([
                    'phone_number' => $phone,
                ]);
            }
            GenerateGreetingAudio::dispatchSync($campaign);

            return $campaign;
        }, function () use ($data) {
            $filePath = Arr::get($data, 'file_path');
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }
        });
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $this->validateCampaign($request, $campaign);

        $oldCampaignFilepath = $campaign->file_path;
        $oldGreeting = $campaign->greeting;
        $oldGreetingAudioPath = $campaign->greeting_audio_path;

        $this->transaction(function () use ($campaign, $data, $oldGreeting) {
            $campaign->update(Arr::except($data, 'phone_numbers'));
            if ($campaign->greeting !== $oldGreeting) {
                GenerateGreetingAudio::dispatchSync($campaign);
            }
            foreach ($data['phone_numbers'] as $phone) {
                if ($campaign->calls()->where('phone_number', $phone)->exists()) {
                    continue;
                }
                $campaign->calls()->create([
                    'phone_number' => $phone,
                ]);
            }
        }, function () use ($data) {
            $filePath = Arr::get($data, 'file_path');
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }
        });

        if ($campaign->file_path != $oldCampaignFilepath) {
            Storage::disk('public')->delete($oldCampaignFilepath);
        }

        if ($campaign->greeting != $oldGreeting) {
            Storage::disk('public')->delete($oldGreetingAudioPath);
        }

        return $campaign;
    }

    public function makeTestCall(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'phone_number' => ['required']
        ]);

        if (!isset($campaign->ai_config['greeting_audio_path'])) {
            abort(response([
                'message' => 'El audio de saludo aun no ha sido generado'
            ], 409));
        }

        $phoneNumber = $validated['phone_number'];
        $callService = resolve(CallServiceContract::class);
        $successful = $callService->call($phoneNumber, [
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
}
