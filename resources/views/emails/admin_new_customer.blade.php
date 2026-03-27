<!DOCTYPE html>
<html>
<body style="font-family: Arial; background:#f4f6f8; padding:20px;">
  <div style="max-width:500px; margin:auto; background:#fff; padding:25px; border-radius:8px;">
    
    <h2 style="color:#198754;">New Customer Registered</h2>

    <p>A new customer has signed up.</p>

    <div style="margin:20px 0; padding:15px; background:#eef7ee; border-radius:6px;">
      <p><strong>Name:</strong> {{ $user->full_name }}</p>
      <p><strong>Email:</strong> {{ $user->email }}</p>
      <p><strong>Phone:</strong> {{ $user->phone }}</p>
    </div>

    <p>Please review or monitor activity if needed.</p>

  </div>
</body>
</html>