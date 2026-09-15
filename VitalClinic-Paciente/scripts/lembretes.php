<?php

declare(strict_types=1);
// mesmo "modo rígido" de tipos que o resto do projeto usa

require __DIR__ . '/../app/helpers.php';
// dá pra usar config(), now_sql(), format_datetime()...

date_default_timezone_set(config('timezone') ?: 'America/Sao_Paulo');

require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/repository.php';
// dá pra usar db() e a create_appointment_reminders()

// esse arquivo NÃO é acessado pelo navegador, ele roda direto pela linha
// de comando: "php scripts/lembretes.php" (ou "php scripts/lembretes.php 48"
// pra usar 48h de antecedência em vez das 24h padrão)
$horasAntes = isset($argv[1]) ? max(1, (int) $argv[1]) : 24;

$criados = create_appointment_reminders($horasAntes);

echo 'Lembretes criados: ' . $criados . PHP_EOL;