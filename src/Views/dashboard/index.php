<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Painel - UTFPR Arena</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #f8fafc; min-height: 100vh; }
        header {
            background: #1e293b;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
        }
        header h1 { font-size: 1.2rem; color: #38bdf8; }
        header nav a { color: #94a3b8; text-decoration: none; margin-left: 1rem; font-size: .9rem; }
        main { max-width: 960px; margin: 2rem auto; padding: 0 1.5rem; }
        .alert { padding: .8rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #14532d; color: #86efac; border: 1px solid #166534; }
        .welcome { font-size: 1.5rem; margin-bottom: .5rem; }
        .role-badge {
            display: inline-block;
            background: #1d4ed8;
            color: #bfdbfe;
            padding: .2rem .7rem;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .card { background: #1e293b; border-radius: 12px; padding: 1.5rem; border: 1px solid #334155; }
        .card h3 { color: #94a3b8; font-size: .85rem; margin-bottom: .5rem; }
        .card p { font-size: 1.4rem; font-weight: 700; }
        .logout-btn {
            background: #ef4444;
            color: #fff;
            border: none;
            padding: .5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: .9rem;
        }
        .session-info {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 2rem;
        }
        .session-info pre { color: #34d399; font-size: .85rem; overflow-x: auto; }
    </style>
</head>
<body>
    <header>
        <h1>🏖️ UTFPR Arena</h1>
        <nav>
            <a href="/complaints">Reclamações</a>
            <a href="/chat/messages">Chat</a>
            <form method="POST" action="/logout" style="display:inline">
                <button type="submit" class="logout-btn">Sair</button>
            </form>
        </nav>
    </header>

    <main>
        <?php if (!empty($success)) : ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <p class="welcome">Olá, <strong><?= htmlspecialchars((string)($user['email'] ?? 'Usuário')) ?>!</strong></p>
        <p style="color:#94a3b8; margin-top:.3rem">
            Papel: <span class="role-badge"><?= htmlspecialchars((string)($user['role'] ?? '')) ?></span>
        </p>

        <div class="cards">
            <div class="card">
                <h3>Status da Conta</h3>
                <p style="color:#34d399">✅ Ativo</p>
            </div>
            <div class="card">
                <h3>Mensalidades</h3>
                <p>Ver faturas →</p>
            </div>
            <div class="card">
                <h3>Reclamações</h3>
                <p>Registrar →</p>
            </div>
        </div>

        <div class="session-info">
            <h3 style="color:#94a3b8; margin-bottom:.5rem; font-size:.85rem">🔐 Dados da Sessão (demonstração)</h3>
            <pre><?= htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT) ?: '{}') ?></pre>
        </div>
    </main>
</body>
</html>
