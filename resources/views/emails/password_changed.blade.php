<!DOCTYPE html>
<html>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="padding:20px;">
    <tr>
      <td align="center">

        <table width="100%" style="max-width:500px; background:#ffffff; padding:25px; border-radius:8px;">
          
          <!-- Header -->
          <tr>
            <td align="center">
              <h2 style="color:#dc3545; margin-bottom:10px;">🔐 Password Changed</h2>
            </td>
          </tr>

          <!-- Message -->
          <tr>
            <td style="color:#555; font-size:14px; text-align:center;">
              Hi {{ $user->full_name }},
              <br><br>
              Your account password has been successfully updated.
            </td>
          </tr>

          <!-- Info Box -->
          <tr>
            <td align="center" style="padding:20px 0;">
              <div style="
                background:#fff3cd;
                padding:15px;
                border-radius:6px;
                color:#856404;
                font-size:13px;
              ">
                If you did NOT make this change, please reset your password immediately or contact support.
              </div>
            </td>
          </tr>

          <!-- Details -->
          <tr>
            <td style="font-size:13px; color:#777; text-align:center;">
              <p><strong>Email:</strong> {{ $user->email }}</p>
              <p><strong>Time:</strong> {{ now() }}</p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding-top:20px; text-align:center; font-size:12px; color:#aaa;">
              This is a security notification. Please do not ignore.
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>