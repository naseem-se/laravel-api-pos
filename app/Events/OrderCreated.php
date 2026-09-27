<?php

namespace App\Events;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('restaurant.'.$this->order->restaurant_id)];
    }

    // Custom name matches the Node build's socket event name exactly
    // ('order:new'), so the frontend's existing event-listener code
    // barely has to change when swapping the transport.
    public function broadcastAs(): string
    {
        return 'order.new';
    }

    // Controls exactly what's sent over the wire — reusing the API
    // Resource here means the WebSocket payload and the REST response
    // shape never drift apart, since both come from the same source.
    public function broadcastWith(): array
    {
        return (new OrderResource($this->order))->resolve();
    }
}