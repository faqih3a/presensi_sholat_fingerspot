<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FingerspotService;
use Illuminate\Support\Facades\Log;

class FingerspotWebhookController extends Controller
{
    protected $fingerspotService;

    public function __construct(FingerspotService $fingerspotService)
    {
        $this->fingerspotService = $fingerspotService;
    }

    public function handle(Request $request)
    {
        // Log incoming webhook data for debugging
        Log::info('Fingerspot Webhook Payload: ' . $request->getContent());

        $payload = $request->json()->all();

        if (empty($payload)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Empty payload'
            ], 400);
        }

        $type = $payload['type'] ?? $payload['command'] ?? null;

        // If it's a get_userinfo event
        if ($type === 'get_userinfo' || $type === 'userinfo') {
            $users = [];
            if (isset($payload['data'])) {
                if (is_array($payload['data'])) {
                    if (isset($payload['data'][0])) {
                        $users = $payload['data'];
                    } else {
                        $users = [$payload['data']];
                    }
                }
            } elseif (isset($payload['users']) && is_array($payload['users'])) {
                $users = $payload['users'];
            }

            if (!empty($users)) {
                $result = $this->fingerspotService->syncUserList($users);
                Log::info("Fingerspot Webhook: Synced {$result['santri']} santri and {$result['asatidz']} asatidz.");
                return response()->json([
                    'status' => 'success',
                    'message' => "Successfully synced {$result['santri']} santri and {$result['asatidz']} asatidz."
                ]);
            }
        } 
        
        // If it's an attlog event
        if ($type === 'attlog') {
            $logs = [];
            if (isset($payload['data'])) {
                if (is_array($payload['data'])) {
                    if (isset($payload['data'][0])) {
                        $logs = $payload['data'];
                    } else {
                        $logs = [$payload['data']];
                    }
                }
            } elseif (isset($payload['logs']) && is_array($payload['logs'])) {
                $logs = $payload['logs'];
            }

            if (!empty($logs)) {
                $this->fingerspotService->processAttlogs($logs);
                Log::info("Fingerspot Webhook: Processed " . count($logs) . " attlogs.");
                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully processed attlogs.'
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received, but no matching action was taken.'
        ]);
    }
}
