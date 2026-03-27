<!DOCTYPE html>
<html>
<body style="font-family: Arial; background:#f4f6f8; padding:20px;">

<div style="max-width:600px; margin:auto; background:#fff; padding:25px; border-radius:8px;">

  <h2 style="color:#dc3545;">New Order Received</h2>

  <div style="background:#fff3cd; padding:15px; border-radius:6px;">
    <p><strong>Order ID:</strong> {{ $order->order_number }}</p>
    <p><strong>Customer:</strong> {{ $order->customer->full_name }}</p>
    <p><strong>Email:</strong> {{ $order->customer->email }}</p>
    <p><strong>Total:</strong> ₹{{ $order->final_amount }}</p>
  </div>

  <h4>Items:</h4>
  <ul>
    @foreach($items as $item)
      <li>{{ $item->product->product_name }} (x{{ $item->quantity }})</li>
    @endforeach
  </ul>

  <p style="margin-top:20px;">Please process this order immediately.</p>

</div>

</body>
</html>