<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignCall;
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
            // Preparar la carga para la llamada
            $payload = [
                'to'            => $contact->phone_number,
                'from'          => config('services.telnyx.from_number'),
                'connection_id' => config('services.telnyx.connection_id'),
                // 'webhook_url'   => config('services.telnyx.webhook_url'),
                'stream_url' => 'wss://telnyxdemo.eastus.cloudapp.azure.com/?' . http_build_query([
                    'campaign_id' => $contact->campaign_id,
                ]),
                // 'stream_track' => 'both_tracks',
                // Agrega otros parámetros requeridos por Telnyx, por ejemplo, para media streaming
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
                $this->info("Llamada iniciada para el teléfono: {$contact->phone_number}");
                // Actualizar el estado del contacto, por ejemplo, a "contacted"
                $contact->status = CampaignCall::STATUS_CALLING;
                $contact->telnyx_data = ['call_control_id' => $response->json('data.call_control_id')];
                $contact->save();
            } else {
                $this->error("No se pudo iniciar la llamada para el teléfono: {$contact->phone_number}");
                // Aquí podrías registrar el error o actualizar el estado a "failed"

                $contact->status = CampaignCall::STATUS_FAILED;
                $contact->save();
            }
        }

        return 0;
    }
}
