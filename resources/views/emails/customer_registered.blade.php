<!DOCTYPE html>
<html>
<body style="font-family: Arial; background:#f4f6f8; padding:20px;">
  <div style="max-width:500px; margin:auto; background:#fff; padding:25px; border-radius:8px;">
    
    <h2 style="color:#0d6efd;">Welcome, {{ $user->full_name }} 🎉</h2>

    <p>Thank you for registering with <strong>AK Flowers</strong>.</p>

    <p>We’re excited to have you onboard 🌸</p>

    <div style="margin:20px 0; padding:15px; background:#f1f5ff; border-radius:6px;">
      <p><strong>Email:</strong> {{ $user->email }}</p>
      <p><strong>Phone:</strong> {{ $user->phone }}</p>
    </div>

    <p>Start exploring our flowers and make someone smile today 💐</p>

    <hr>

    <p style="font-size:12px; color:#888;">
      If you did not create this account, please contact support.
    </p>

  </div>
</body>
</html>