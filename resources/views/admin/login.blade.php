<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login &mdash; Pad Preview</title>
<style>
  :root{ --bg:#0e1013; --panel:#16191d; --line:#2a2f36; --text:#e8e6e0; --muted:#8b9099; --accent:#c98a3d; }
  *{box-sizing:border-box;}
  body{
    margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
    background:var(--bg); color:var(--text); font-family:'Segoe UI', system-ui, sans-serif;
  }
  form{
    background:var(--panel); border:1px solid var(--line); border-radius:14px;
    padding:28px; width:100%; max-width:320px;
  }
  h1{ margin:0 0 18px; font-size:1.1rem; }
  input[type=password]{
    width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--line);
    background:#1d2126; color:var(--text); font-size:.95rem; margin-bottom:12px;
  }
  button{
    width:100%; padding:10px; border-radius:8px; border:none;
    background:var(--accent); color:#181008; font-weight:700; font-size:.9rem; cursor:pointer;
  }
  .error{ color:#e0517a; font-size:.82rem; margin:-6px 0 12px; }
</style>
</head>
<body>
<form method="POST" action="{{ route('admin.login.submit') }}">
  @csrf
  <h1>Admin Login</h1>
  @error('password')<div class="error">{{ $message }}</div>@enderror
  <input type="password" name="password" placeholder="Password" autofocus required>
  <button type="submit">Log in</button>
</form>
</body>
</html>
