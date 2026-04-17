<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify your email</title>
<style>
  body { margin: 0; padding: 0; background: #f5f5f5; font-family: Inter, Arial, sans-serif; }
  .wrapper { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
  .header  { background: #ff7f11; padding: 32px 40px; text-align: center; }
  .header h1 { margin: 0; color: #fff; font-size: 1.7rem; letter-spacing: 1px; }
  .body    { padding: 32px 40px; }
  .body p  { color: #555; font-size: 0.97rem; line-height: 1.6; margin: 0 0 16px; }
  .code-box {
    display: block;
    margin: 28px auto;
    background: #fff4e6;
    border: 2px dashed #ff7f11;
    border-radius: 12px;
    padding: 18px 0;
    text-align: center;
    font-size: 2.4rem;
    font-weight: 800;
    letter-spacing: 10px;
    color: #ff7f11;
  }
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
    <p>Welcome! To complete your registration, enter this 6-digit verification code in the app:</p>
    <div class="code-box">{{ $code }}</div>
    <p>This code expires in <strong>15 minutes</strong>. If you didn't create an account, you can safely ignore this email.</p>
  </div>
  <div class="footer">
    <p>© {{ date('Y') }} Ticket Center. All rights reserved.</p>
  </div>
</div>
</body>
</html>
