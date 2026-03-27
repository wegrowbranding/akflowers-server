<!DOCTYPE html>

<html>
<head>
  <meta charset="UTF-8">
  <title>OTP Verification</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family: Arial, sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:20px;">
    <tr>
      <td align="center">

        <!-- Container -->
        <table width="100%" max-width="500" cellpadding="0" cellspacing="0" 
            style="background:#ffffff; border-radius:10px; padding:30px; box-shadow:0 4px 10px rgba(0,0,0,0.05);">

        <!-- Header -->
        <tr>
            <td align="center" style="padding-bottom:20px;">
            <h2 style="margin:0; color:#333;">🔐 OTP Verification</h2>
            </td>
        </tr>

        <!-- Message -->
        <tr>
            <td align="center" style="color:#555; font-size:14px; line-height:1.6;">
            Please use the following One-Time Password (OTP) to continue.
            </td>
        </tr>

        <!-- OTP Box -->
        <tr>
            <td align="center" style="padding:25px 0;">
            <div style="
                display:inline-block;
                padding:14px 28px;
                font-size:22px;
                font-weight:bold;
                letter-spacing:4px;
                color:#0d6efd;
                background:#eef4ff;
                border-radius:8px;
                border:1px dashed #0d6efd;
            ">
                {{ $otp }}
            </div>
            </td>
        </tr>

        <!-- Expiry -->
        <tr>
            <td align="center" style="color:#888; font-size:13px;">
            This OTP will expire in <strong>10 minutes</strong>.
            </td>
        </tr>

        <!-- Divider -->
        <tr>
            <td style="padding:20px 0;">
            <hr style="border:none; border-top:1px solid #eee;">
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td align="center" style="color:#aaa; font-size:12px;">
            If you didn’t request this, please ignore this email.
            </td>
        </tr>

        </table>

    </td>
    </tr>

  </table>

</body>
</html>
