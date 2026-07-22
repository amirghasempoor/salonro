<?php

namespace App\Facades\Sms;

use App\Models\SmsHistory;
use Exception;
use Illuminate\Support\Facades\Log;
use Ipe\Sdk\Facades\SmsIr;

class SmsClass
{
    protected string $lineNumber;

    public function __construct()
    {
        $this->lineNumber = config('smsIr.lineNumber');
    }

    /**
     * @throws Exception
     */
    public function send($phone_number, $message): void
    {
        try {
            $response = SmsIr::bulkSend($this->lineNumber, $message, [$phone_number]);

            SmsHistory::query()->create([
                'phone_number' => $phone_number,
                'status' => $response->status,
                'message' => $response->message,
                'pack_id' => $response->data['packId'],
                'message_ids' => json_encode($response->data['messageIds']),
                'cost' => $response->data['cost'],
            ]);
        } catch (Exception $e) {
            Log::channel('sms')->error($e->getMessage());
            throw $e;
        }
    }
}
