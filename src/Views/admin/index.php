<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - UTFPR Arena</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #f8fafc; min-height: 100vh; }
        header { background: #1e293b; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        header h1 { font-size: 1.2rem; color: #f59e0b; }
        .badge-admin { background: #7c2d12; color: #fed7aa; padding: .2rem .7rem; border-radius: 20px; font-size: .75rem; font-weight: 700; margin-left: .5rem; }
        main { max-width: 1100px; margin: 2rem auto; padding: 0 1.5rem; }
        .alert { padding: .8rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #14532d; color: #86efac; border: 1px solid #166534; }
        .alert-error   { background: #7f1d1d; color: #fca5a5; border: 1px solid #991b1b; }
        h2 { font-size: 1.3rem; margin-bottom: 1.5rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat { background: #1e293b; border-radius: 12px; padding: 1.2rem; border: 1px solid #334155; }
        .stat h3 { color: #94a3b8; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .5rem; }
        .stat .value { font-size: 1.8rem; font-weight: 700; }
        .stat .value.danger { color: #f87171; }
        .stat .value.success { color: #34d399; }
        .logout-btn { background: #ef4444; color: #fff; border: none; padding: .5rem 1rem; border-radius: 8px; cursor: pointer; font-size: .9rem; }
        .section { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; margin-top: 1.5rem; }
        .section h3 { color: #94a3b8; font-size: .9rem; margin-bottom: 1rem; }
        code { background: #0f172a; padding: .2rem .5rem; border-radius: 4px; font-family: monospace; color: #34d399; font-size: .85rem; }
    </style>
</head>
<body>
    <header>
        <h1>⚙️ UTFPR Arena <span class="badge-admin">ADMIN</span></h1>
        <div style="display:flex; align-items:center; gap:1rem">
            <span style="color:#94a3b8; font-size:.9rem"><?= htmlspecialchars((string)($user['email'] ?? '')) ?></span>
            <form method="POST" action="/logout">
                <button type="submit" class="logout-btn">Sair</button>
            </form>
        </div>
    </header>

    <main>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2>Painel Administrativo</h2>

        <div class="stats">
            <div class="stat">
                <h3>Total de Alunos</h3>
                <div class="value"><?= (int)($stats['total_alunos'] ?? 0) ?></div>
            </div>
            <div class="stat">
                <h3>Alunos Bloqueados</h3>
                <div class="value danger"><?= (int)($stats['alunos_bloqueados'] ?? 0) ?></div>
            </div>
            <div class="stat">
                <h3>Faturas Vencidas</h3>
                <div class="value danger"><?= (int)($stats['faturas_vencidas'] ?? 0) ?></div>
            </div>
            <div class="stat">
                <h3>Receita do Mês</h3>
                <div class="value success">R$ <?= number_format((float)($stats['receita_mes'] ?? 0), 2, ',', '.') ?></div>
            </div>
        </div>

        <div class="section">
            <h3>🔐 Informações da Sessão Administrativa</h3>
            <p style="color:#94a3b8; font-size:.85rem; margin-bottom:.5rem">
                Esta área só é acessível por usuários com role <code>manager</code>.
                O <strong>RoleMiddleware</strong> garante isso — alunos autenticados recebem <code>403 Forbidden</code>.
            </p>
            <pre style="color:#34d399; font-size:.8rem; background:#0f172a; padding:.8rem; border-radius:8px; overflow-x:auto"><?= htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT) ?: '{}') ?></pre>
        </div>
    </main>
</body>
</html>
