<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Mi Cuenta — agro</title>
    <style>
        body { font-family: sans-serif; max-width: 720px; margin: 40px auto; padding: 0 20px; color: #333; }
        h1 { color: #5a8dee; background: linear-gradient(90deg, #5a8dee, #b86fff); -webkit-background-clip: text; color: transparent; }
        dt { font-weight: bold; margin-top: 12px; }
        dd { margin-left: 0; color: #666; }
        button.btn { padding:10px 20px; background:#5a8dee; color:#fff; border:0; cursor:pointer; border-radius:6px; margin-top:20px; font-size:1em; }
    </style>
</head>
<body>
    <h1>Mi cuenta — agro</h1>
    <p>Sesión iniciada vía <strong>Cuenta Gurztac</strong> (SSO).</p>
    <dl>
        <dt>Email</dt><dd>{{ $claims['email'] ?? '—' }}</dd>
        <dt>Nombre</dt><dd>{{ $claims['nombre'] ?? '—' }}</dd>
        <dt>User ID Ecosistema</dt><dd>{{ $claims['user_ecosistema_id'] ?? $claims['sub'] ?? '—' }}</dd>
        <dt>Token expira</dt><dd>{{ isset($claims['exp']) ? date('Y-m-d H:i:s', $claims['exp']) : '—' }}</dd>
    </dl>
    <form action="/auth/sso/logout" method="post">
        @csrf
        <button type="submit" class="btn">Cerrar sesión</button>
    </form>
</body>
</html>
