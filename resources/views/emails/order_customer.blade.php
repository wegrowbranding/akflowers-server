<!DOCTYPE html>
<html>
<body style="font-family: Arial; background:#f4f6f8; padding:20px;">

<div style="max-width:600px; margin:auto; background:#fff; padding:25px; border-radius:8px;">

  <h2 style="color:#198754;">🎉 Order Confirmed</h2>

  <p>Hi {{ $order->customer->full_name }},</p>

  <p>Your order has been placed successfully.</p>

  <div style="background:#f1f5ff; padding:15px; border-radius:6px;">
    <p><strong>Order ID:</strong> {{ $order->order_number }}</p>
    <p><strong>Total:</strong> ₹{{ $order->final_amount }}</p>
    <p><strong>Payment:</strong> {{ strtoupper($order->payment_method) }}</p>
  </div>

  <h4>Items:</h4>
  <ul>
    @foreach($items as $item)
      <li>{{ $item->product->product_name }} (x{{ $item->quantity }})</li>
    @endforeach
  </ul>

  <p style="margin-top:20px;">We’ll notify you once your order is processed 🚚</p>

  <hr>
  <p style="font-size:12px; color:#888;">Thank you for shopping with us 🌸</p>

</div>

</body>
</html>