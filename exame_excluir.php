<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pacientes.php');
}

$db         = db();
$id          = (int)($_POST['exame_id'] ?? 0);
$paciente_id = (int)($_POST['paciente_id'] ?? 0);

if (!$id || !$paciente_id) {
    flash('Requisição inválida.', 'error');
    redirect('pacientes.php');
}

$stmt = $db->prepare("SELECT * FROM exames WHERE id = ? AND paciente_id = ?");
$stmt->execute([$id, $paciente_id]);
$exame = $stmt->fetch();

if (!$exame) {
    flash('Exame não encontrado.', 'error');
    redirect("paciente_ver.php?id={$paciente_id}&tab=exames");
}

// Remove arquivo físico
$caminho = __DIR__ . '/uploads/exames/' . basename($exame['arquivo_path']);
if (file_exists($caminho)) {
    unlink($caminho);
}

// Remove registro do banco
$db->prepare("DELETE FROM exames WHERE id = ?")->execute([$id]);

flash('Exame excluído com sucesso.');
redirect("paciente_ver.php?id={$paciente_id}&tab=exames");
