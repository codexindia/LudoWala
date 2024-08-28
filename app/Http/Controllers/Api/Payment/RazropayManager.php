<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\UserOrders;
use Illuminate\Http\Request;
use Razorpay\Api\Api as Razorpay;
use Razorpay\Api\Errors\SignatureVerificationError;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class RazropayManager extends Controller
{
    public function depositAmount(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0|lt:2000',
        ]);
        $neworder = new UserOrders;
        $neworder->user_id = $request->user()->id;
        $neworder->amount = $request->amount;
        $neworder->description = 'Amount Deposit Through Online Gateway';
        $neworder->type = 'payment';

        $api = new Razorpay(config('services.razorpay.key'), config('services.razorpay.secret'));

        try {
            $razorpayOrder = $api->order->create([
                'amount' => $request->amount * 100, // Razorpay expects amount in paise
                'currency' => 'INR',
                'receipt' => $neworder->id,
            ]);
            $neworder->order_id = $razorpayOrder['id'];
            $neworder->save();
            return response()->json([
                // 'order_id' => $neworder->order_id,
                'status' => true,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $neworder->amount,
                'currency' => 'INR',
                'key' => config('services.razorpay.key'),
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' =>  $e->getMessage(),
            ]);
        }
    }
    public function RazorppaywebHookHander(Request $request)
    {

        $webhookSecret = config('services.razorpay.webhook_secret');

        $razorpaySignature = $request->header('X-Razorpay-Signature');

        try {
            $api = new Razorpay(config('services.razorpay.key'), config('services.razorpay.secret'));
            $api->utility->verifyWebhookSignature($request->getContent(), $razorpaySignature, $webhookSecret);
        } catch (SignatureVerificationError $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Webhook signature is valid, process the webhook payload
        $payload = $request->all();

        // Handle different event types
        switch ($payload['event']) {
            case 'payment.captured':
                Log::info($payload);
                $orderId = $payload['payload']['payment']['entity']['order_id'];
                $paymentId = $payload['payload']['payment']['entity']['id'];
                $checkOldtrx = UserOrders::where('order_id', $orderId)->first();
                $user = User::find($checkOldtrx->user_id);
                if ($checkOldtrx->status == "pending") {
                    if ($user->refBy != null) {
                        creditBal($user->refBy, $checkOldtrx->amount * 0.02, 0, "deposit_wallet", "Received Referral Commission From {$user->fullname}",'referral_commission');
                    }
                    creditBal($checkOldtrx->user_id, $checkOldtrx->amount, 0, "deposit_wallet", "Amount Deposit Through Online Gateway Payment Id: {$paymentId}",'fund_added');
                }
                $checkOldtrx->update([
                    'payment_id' => $paymentId,
                    'status' => 'success',
                    'webhookResp' => $payload
                ]);
                break;
            case 'payment.failed':
                Log::info($payload);
                break;
                // Add more cases for other events you want to handle
        }

        return response()->json(['status' => 'Webhook processed successfully']);
    }
}
