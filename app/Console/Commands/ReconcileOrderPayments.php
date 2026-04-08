<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Notification;
use App\Services\PayMongoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileOrderPayments extends Command
{
    protected $signature = 'payments:reconcile {--max-age=24 : Maximum age in hours to reconcile}';
    protected $description = 'Reconcile unpaid orders with PayMongo to catch missed payments';

    public function handle(PayMongoService $paymongo)
    {
        $this->info('Starting payment reconciliation...');

        $maxAge = (int) $this->option('max-age');
        $cutoffTime = now()->subHours($maxAge);

        // Find unpaid orders from last 24 hours with PayMongo IDs
        $unpaidOrders = Order::where('payment_status', 'unpaid')
            ->whereNotNull('paymongo_payment_id')
            ->whereIn('payment_method', ['gcash', 'paymaya'])
            ->where('created_at', '>=', $cutoffTime)
            ->get();

        if ($unpaidOrders->isEmpty()) {
            $this->info('No unpaid orders to reconcile.');
            return Command::SUCCESS;
        }

        $this->info("Found {$unpaidOrders->count()} unpaid order(s) to check...\n");

        $fixedCount = 0;

        foreach ($unpaidOrders as $order) {
            $this->info("Checking Order: {$order->order_number}");
            $this->line("├─ PayMongo ID: {$order->paymongo_payment_id}");

            if (str_starts_with($order->paymongo_payment_id, 'cs_')) {
                // Checkout session
                $sessionData = $paymongo->retrieveCheckoutSession($order->paymongo_payment_id);

                if ($sessionData) {
                    $status = $sessionData['attributes']['payment_intent']['attributes']['status'] ?? null;
                    $payments = $sessionData['attributes']['payments'] ?? [];

                    $this->line("├─ PayMongo Status: {$status}");
                    $this->line("├─ Payments: " . count($payments));

                    if ($status === 'succeeded' || !empty($payments)) {
                        $this->info("├─ ✓ PAYMENT VERIFIED");

                        $order->update([
                            'payment_status' => 'paid',
                        ]);

                        // Create notification
                        Notification::create([
                            'user_id' => $order->consumer_id,
                            'title' => 'Payment Confirmed',
                            'message' => "Payment for order {$order->order_number} was confirmed via PayMongo.",
                            'type' => 'system',
                            'channel' => 'in_app',
                            'data' => json_encode([
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                            ]),
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);

                        Log::info('Order payment reconciled via checkout session', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'paymongo_id' => $order->paymongo_payment_id,
                        ]);

                        $this->info("└─ ✓ ORDER MARKED PAID\n");
                        $fixedCount++;
                    } else {
                        $this->line("└─ ⏳ Payment still pending\n");
                    }
                } else {
                    $this->line("└─ ❌ Could not retrieve from PayMongo\n");
                }

            } elseif (str_starts_with($order->paymongo_payment_id, 'link_')) {
                // Payment link
                $paymentData = $paymongo->retrievePaymentLink($order->paymongo_payment_id);

                if ($paymentData) {
                    $status = $paymentData['attributes']['status'] ?? null;

                    $this->line("├─ PayMongo Status: {$status}");

                    if ($status === 'paid') {
                        $this->info("├─ ✓ PAYMENT VERIFIED");

                        $order->update([
                            'payment_status' => 'paid',
                        ]);

                        Notification::create([
                            'user_id' => $order->consumer_id,
                            'title' => 'Payment Confirmed',
                            'message' => "Payment for order {$order->order_number} was confirmed via PayMongo.",
                            'type' => 'system',
                            'channel' => 'in_app',
                            'data' => json_encode([
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                            ]),
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);

                        Log::info('Order payment reconciled via payment link', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'paymongo_id' => $order->paymongo_payment_id,
                        ]);

                        $this->info("└─ ✓ ORDER MARKED PAID\n");
                        $fixedCount++;
                    } else {
                        $this->line("└─ ⏳ Payment still pending\n");
                    }
                } else {
                    $this->line("└─ ❌ Could not retrieve from PayMongo\n");
                }
            } else {
                $this->line("└─ ⚠️  Unknown PayMongo ID format\n");
            }
        }

        $this->info("\n=== RECONCILIATION SUMMARY ===");
        $this->info("Orders fixed: {$fixedCount} / {$unpaidOrders->count()}");
        $this->info("✓ Reconciliation completed\n");

        return Command::SUCCESS;
    }
}
