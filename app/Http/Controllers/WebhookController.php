<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\FingerspotService;

class WebhookController extends Controller
{
    protected $fingerspotService;

    public function __construct(FingerspotService $fingerspotService)
    {
        $this->fingerspotService = $fingerspotService;
    }

    public function handle(Request $request)
    {
        // 1. Ambil data yang dikirim Fingerspot
        $data = $request->all();

        // 2. Log data untuk memastikan data masuk (cek di storage/logs/laravel.log)
        Log::info('Webhook Fingerspot diterima:', $data);

        // 3. Proses logika Anda di sini (misal: simpan ke database)
        if (empty($data)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Empty payload'
            ], 400);
        }

        $type = $data['type'] ?? $data['command'] ?? null;

        // If it's a get_userinfo event
        if ($type === 'get_userinfo' || $type === 'userinfo') {
            $users = [];
            if (isset($data['data'])) {
                if (is_array($data['data'])) {
                    if (isset($data['data'][0])) {
                        $users = $data['data'];
                    } else {
                        $users = [$data['data']];
                    }
                }
            } elseif (isset($data['users']) && is_array($data['users'])) {
                $users = $data['users'];
            }

            if (!empty($users)) {
                $result = $this->fingerspotService->syncUserList($users);
                Log::info("Fingerspot Webhook: Synced {$result['santri']} santri and {$result['asatidz']} asatidz.");
            }
        } 
        
        // If it's an attlog event
        if ($type === 'attlog') {
            $logs = [];
            if (isset($data['data'])) {
                if (is_array($data['data'])) {
                    if (isset($data['data'][0])) {
                        $logs = $data['data'];
                    } else {
                        $logs = [$data['data']];
                    }
                }
            } elseif (isset($data['logs']) && is_array($data['logs'])) {
                $logs = $data['logs'];
            }

            if (!empty($logs)) {
                $this->fingerspotService->processAttlogs($logs);
                Log::info("Fingerspot Webhook: Processed " . count($logs) . " attlogs.");
            }
        }

        // 4. Berikan respons 200 OK agar Fingerspot tahu pengiriman sukses
        return response()->json(['status' => 'success'], 200);
    }
}
