<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<section class="complaints-container">
    <h2>Registro de Reclamações</h2>

    <ul class="complaint-list">
        <?php foreach ($complaints ?? [] as $complaint) : ?>
            <li class="complaint-item complaint-status-<?= htmlspecialchars($complaint['status']) ?>">
                <strong><?= htmlspecialchars($complaint['title']) ?></strong>
                <p><?= htmlspecialchars($complaint['description']) ?></p>
                <small>
                    Por: <?= htmlspecialchars($complaint['author_email']) ?> |
                    Status: <em><?= htmlspecialchars($complaint['status']) ?></em> |
                    Em: <?= htmlspecialchars($complaint['created_at'] ?? '') ?>
                </small>
            </li>
        <?php endforeach; ?>
    </ul>

    <hr>
    <h3>Nova Reclamação</h3>
    <form method="POST" action="/complaints">
        <input type="text" name="title" placeholder="Título da reclamação" required>
        <textarea name="description" placeholder="Descreva o problema..." required></textarea>
        <input type="email" name="author_email" placeholder="Seu e-mail" required>
        <button type="submit">Registrar</button>
    </form>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
