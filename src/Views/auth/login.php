<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UTFPR Arena</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #1e293b; border-radius: 12px; padding: 2rem; width: 100%; max-width: 420px; box-shadow: 0 25px 50px rgba(0,0,0,.5); }
        h1 { color: #f8fafc; font-size: 1.6rem; margin-bottom: .3rem; }
        p.subtitle { color: #94a3b8; font-size: .9rem; margin-bottom: 1.5rem; }
        .alert { padding: .8rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .9rem; }
        .alert-error   { background: #7f1d1d; color: #fca5a5; border: 1px solid #991b1b; }
        .alert-success { background: #14532d; color: #86efac; border: 1px solid #166534; }
        label { display: block; color: #94a3b8; font-size: .85rem; margin-bottom: .35rem; margin-top: 1rem; }
        input { width: 100%; padding: .65rem .9rem; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f8fafc; font-size: 1rem; }
        input:focus { outline: none; border-color: #3b82f6; }
        button { width: 100%; margin-top: 1.5rem; padding: .75rem; background: #3b82f6; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background .2s; }
        button:hover { background: #2563eb; }
        .hint { margin-top: 1.2rem; text-align: center; color: #64748b; font-size: .82rem; }
        .hint a { color: #3b82f6; text-decoration: none; }
        .credentials { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: .8rem 1rem; margin-top: 1.5rem; font-size: .8rem; color: #64748b; }
        .credentials strong { color: #94a3b8; }
        .credentials code { color: #34d399; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🏖️ UTFPR Arena</h1>
        <p class="subtitle">Faça login para acessar o sistema</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="seu@email.com" required autofocus>

            <label for="password">Senha</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>

            <button type="submit">Entrar</button>
        </form>

        <div class="credentials">
            <strong>Contas de demonstração:</strong><br>
            Admin: <code>admin@arena.com</code> / <code>admin123</code><br>
            Aluno: <code>aluno@arena.com</code> / <code>aluno123</code>
        </div>

        <p class="hint">Não tem conta? <a href="/register">Cadastre-se</a></p>
    </div>
</body>
</html>
