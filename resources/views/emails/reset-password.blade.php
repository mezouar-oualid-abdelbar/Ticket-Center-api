<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset your password</title>
<style>
  body { margin: 0; padding: 0; background: #f5f5f5; font-family: Inter, Arial, sans-serif; }
  .wrapper { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
  .header  { background: #ff7f11; padding: 32px 40px; text-align: center; }
  .header h1 { margin: 0; color: #fff; font-size: 1.7rem; letter-spacing: 1px; }
  .body    { padding: 32px 40px; }
  .body p  { color: #555; font-size: 0.97rem; line-height: 1.6; margin: 0 0 16px; }
  .btn {
    display: inline-block;
    margin: 24px 0;
    padding: 14px 36px;
    background: #ff7f11;
    color: #fff;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 1rem;
  }
  .link-fallback { word-break: break-all; color: #ff7f11; font-size: 0.82rem; }
  .footer  { padding: 20px 40px; border-top: 1px solid #f0f0f0; text-align: center; }
  .footer p { color: #aaa; font-size: 0.8rem; margin: 0; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Ticket Center</h1>
  </div>
  <div class="body">
    <p>Hi <strong>{{ $name }}</strong>,</p>
    <p>We received a request to reset your password. Click the button below to choose a new one:</p>
    <p style="text-align:center;">
      <a href="{{ $url }}" class="btn">Reset Password</a>
    </p>
    <p>This link expires in <strong>60 minutes</strong>. If you didn't request a password reset, you can safely ignore this email — your password won't change.</p>
    <p style="font-size:0.82rem; color:#aaa;">If the button doesn't work, copy and paste this link into your browser:</p>
    <p class="link-fallback">{{ $url }}</p>
  </div>
  <div class="footer">
    <p>© {{ date('Y') }} Ticket Center. All rights reserved.</p>
  </div>
</div>
</body>
</html>
