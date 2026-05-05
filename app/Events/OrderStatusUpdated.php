<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $message;

    /**
     * Create a new event instance.
     *
     * @param  Order  $order
     * @return void
     */
    public function __construct(Order $order, string $message = null)
    {
        $this->order = $order->load(['items.product', 'items.options.productOption']);
        $this->message = $message ?? "Order #{$order->reference_no} status updated to {$order->status}";
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // 1. Broadcast to the specific branch channel
        // 2. Broadcast to the specific order channel for customer tracking
        return [
            new Channel('orders.' . $this->order->branch_id),
            new Channel('order.' . $this->order->id),
        ];
    }

    /**
     * Determine the name of the event being broadcasted.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'OrderStatusUpdated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'order_id' => $this->order->id,
            'reference_no' => $this->order->reference_no,
            'status' => $this->order->status,
            'message' => $this->message,
            'updated_at' => $this->order->updated_at->toDateTimeString(),
            'customer_name' => $this->order->customer_name,
            'total_amount' => (float) $this->order->total_amount,
            // Add any other fields useful for the Delivery App UI
        ];
    }
}
