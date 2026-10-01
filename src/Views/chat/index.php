<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<section class="chat-container">
    <h2>Chat da Arena</h2>
    <div id="chat-messages">
        <?php foreach ($messages ?? [] as $msg) : ?>
            <div class="chat-message">
                <strong><?= htmlspecialchars($msg['sender']) ?></strong>
                <span><?= htmlspecialchars($msg['message']) ?></span>
                <small><?= htmlspecialchars($msg['sent_at']) ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="POST" action="/chat/messages">
        <input type="text" name="sender" placeholder="Seu e-mail" required>
        <textarea name="message" placeholder="Sua mensagem..." required></textarea>
        <button type="submit">Enviar</button>
    </form>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
