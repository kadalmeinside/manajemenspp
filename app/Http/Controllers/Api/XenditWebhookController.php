<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class XenditWebhookController extends Controller
{
    /**
     * Handle Xendit Disbursement (Withdrawal) Webhook
     */
    public function handleDisbursement(Request $request)
    {
        // 1. Validate Xendit Token (optional but highly recommended)
        $xenditCallbackToken = env('XENDIT_CALLBACK_VERIFICATION_TOKEN');
        $reqToken = $request->header('x-callback-token');
        
        if ($xenditCallbackToken && $reqToken !== $xenditCallbackToken) {
            Log::warning('Invalid Xendit Webhook Token for Disbursement', [
                'expected' => $xenditCallbackToken,
                'received' => $reqToken,
                'ip' => $request->ip()
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        Log::info('Xendit Disbursement Webhook Received', $payload);

        // Required payload fields
        $disbursementId = $request->input('id');
        $status = $request->input('status');
        $amount = $request->input('amount');
        
        if (!$disbursementId) {
            return response()->json(['message' => 'Disbursement ID missing'], 400);
        }

        // Parse completion time if available
        $completedAt = null;
        if ($status === 'COMPLETED' && $request->has('updated')) {
            $completedAt = Carbon::parse($request->input('updated'));
        }

        // Create or update withdrawal record
        $withdrawal = Withdrawal::updateOrCreate(
            ['xendit_disbursement_id' => $disbursementId],
            [
                'amount' => $amount,
                'status' => $status,
                'bank_code' => $request->input('bank_code'),
                'account_name' => $request->input('account_holder_name') ?? $request->input('account_name'),
                'account_number' => $request->input('account_number'),
                'completed_at' => $completedAt,
                'payload' => $payload
            ]
        );

        return response()->json([
            'message' => 'Disbursement webhook processed successfully',
            'data' => $withdrawal
        ], 200);
    }
}
