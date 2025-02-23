<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignCall;
use App\Services\CallServiceContract;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class InitiateCampaignCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app-campaign:initiate-calls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inicia las llamadas pendientes de las campañas';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (CampaignCall::where('status', CampaignCall::STATUS_CALLING)->exists()) {
            $this->info('Ya hay llamadas en curso.');
            return 0;
        }

        // Obtener los contactos pendientes cuya campaña ya inició y no esté cerrada
        $contacts = CampaignCall::where('status', CampaignCall::STATUS_PENDING)
            ->whereHas('campaign', function ($query) {
                $query->where('start_date', '<=', Carbon::now())
                    ->where('status', Campaign::ACTIVE_STATUS);
            })->limit(1)->get();

        if ($contacts->isEmpty()) {
            $this->info('No se encontraron llamadas pendientes para despachar.');
            return 0;
        }

        foreach ($contacts as $contact) {
            $callService = resolve(CallServiceContract::class);
            $callId = $callService->call($contact->phone_number, [
                'campaign_id' => $contact->campaign_id
            ]);

            if ($callId) {
                $contact->status = CampaignCall::STATUS_CALLING;
                $contact->telnyx_data = ['call_control_id' => $callId];
                $contact->save();
            } else {
                $contact->status = CampaignCall::STATUS_FAILED;
                $contact->save();
            }
        }

        return 0;
    }
}
