<?php

function render_login(): void
{
    ?>
    <section class="auth-grid">
        <div class="auth-copy">
            <img class="auth-logo" src="assets/brand/vital-clinic-logo.svg" alt="Vital Clinic">
            <h1>A sua saúde em primeiro lugar!</h1>
            <p> Uma forma rápida e eficaz de deixar sua clínica organizada.</p>
        </div>
        <form method="post" class="panel form-card">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="page_after" value="login">
            <h2>Entrar</h2>
            <label>E-mail <input type="email" name="email" required autocomplete="email"></label>
            <label>Senha <input type="password" name="password" required autocomplete="current-password"></label>
            <button class="button primary" type="submit">Entrar</button>
            <p class="muted">Acesso exclusivo para administradores e médicos.</p>
        </form>
    </section>
    <?php
}
