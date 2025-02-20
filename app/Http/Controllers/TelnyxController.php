<?php

namespace App\Http\Controllers;

use App\Models\CampaignCall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelnyxController extends Controller
{
    public function webhook(Request $request)
    {
        logger("Webhook", $request->all());

        if (!$this->validateSignature($request)) {
            return response()->json(['message' => 'Firma inválida'], 400);
        }

        $eventType = data_get($request->all(), 'data.event_type');
        if ($eventType == 'call.answered') {
            $this->handleCallAnswered($request->all());
        } else if ($eventType == 'call.hangup') {
            $this->handleCallHangup($request->all());
        } else if ($eventType == 'call.cost') {
            $this->handleCallCost($request->all());
        }
    }

    private function handleCallAnswered($event)
    {
        // $to = data_get($event, 'data.payload.to');
        // $from = data_get($event, 'data.payload.from');
        // $callDuration = data_get($event, 'data.payload.call_duration');
        // $callCost = data_get($event, 'data.payload.call_cost');

        // $contact = CampaignCall::where('phone_number', $to)->first();

        // if (!$contact) {
        //     Log::warning("No se encontró contacto para el número: $to");
        //     abort(response()->json(['message' => 'Contacto no encontrado'], 404));
        // }

        // $contact->update([
        //     'status' => CampaignCall::STATUS_ANSWERED,
        //     'duration' => $callDuration,
        //     'cost' => $callCost,
        // ]);
    }

    private function handleCallHangup($event)
    {
        $callControlId = data_get($event, 'data.payload.call_control_id');

        $contact = CampaignCall::where('telnyx_data->call_control_id', $callControlId)->first();

        if (!$contact) {
            Log::warning("No se encontró contacto para el número: $callControlId");
            abort(response()->json(['message' => 'Contacto no encontrado'], 404));
        }

        $contact->update([
            'status' => CampaignCall::STATUS_DONE,
            // 'duration' => $callDuration
        ]);
    }

    private function handleCallCost($event)
    {
        $callControlId = data_get($event, 'data.payload.call_control_id');
        $callCost = data_get($event, 'data.payload.total_cost');

        $contact = CampaignCall::where('telnyx_data->call_control_id', $callControlId)->first();

        if (!$contact) {
            Log::warning("No se encontró contacto para el número: $callControlId");
            abort(response()->json(['message' => 'Contacto no encontrado'], 404));
        }

        $contact->update([
            'cost' => $callCost
        ]);
    }

    private function validateSignature(Request $request)
    {
        $signature = $request->header('Telnyx-Signature-Ed25519');
        $timestamp = $request->header('Telnyx-Timestamp');
        $rawPayload = $request->getContent();
        $publicKey = config('services.telnyx.public_key');

        if (!$signature || !$timestamp || !$publicKey) {
            Log::error('Falta header de firma, timestamp o clave pública en el webhook de Telnyx');
            abort(response()->json(['message' => 'Faltan datos para validar la firma'], 400));
        }

        // Concatenar el timestamp, un punto y el payload crudo
        $signedPayload = $timestamp . '.' . $rawPayload;
        $decodedSignature = base64_decode($signature, true);

        if ($decodedSignature === false) {
            Log::error('No se pudo decodificar la firma');
            abort(response()->json(['message' => 'Firma inválida'], 400));
        }

        // Convertir la clave pública de hexadecimal a binario
        $publicKeyBin = hex2bin($publicKey);
        if ($publicKeyBin === false) {
            Log::error('Clave pública inválida');
            abort(response()->json(['message' => 'Clave pública inválida'], 400));
        }

        return sodium_crypto_sign_verify_detached($decodedSignature, $signedPayload, $publicKeyBin);
    }
}
