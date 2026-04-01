<?php
require 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = db();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    flash('Registro não informado.', 'error');
    redirect('prontuarios.php');
}

$stmt = $db->prepare("SELECT * FROM prontuario WHERE id = ?");
$stmt->execute([$id]);
$registro = $stmt->fetch();

if (!$registro) {
    flash('Registro não encontrado.', 'error');
    redirect('prontuarios.php');
}

$stmtP = $db->prepare("SELECT id, nome FROM pacientes WHERE id = ?");
$stmtP->execute([$registro['paciente_id']]);
$paciente = $stmtP->fetch();

$pageTitle  = 'Visualizar Prontuário';
$activePage = 'prontuarios';

$criadoEm   = !empty($registro['created_at'])  ? date('d/m/Y', strtotime($registro['created_at']))  . ' às ' . date('H:i', strtotime($registro['created_at']))  : '—';
$editadoEm  = !empty($registro['updated_at'])   ? date('d/m/Y', strtotime($registro['updated_at']))   . ' às ' . date('H:i', strtotime($registro['updated_at']))   : null;
$foiEditado = $editadoEm && $editadoEm !== $criadoEm;

include 'includes/header.php';
?>

<div class="page-bar">
    <h2>Prontuário — <?= sanitize($paciente['nome'] ?? '') ?></h2>
    <div style="display:flex;gap:.5rem;">
        <a href="prontuario_novo.php?id=<?= $id ?>&paciente_id=<?= $registro['paciente_id'] ?>" class="btn btn-primary btn-sm">✏️ Editar</a>
        <a href="paciente_ver.php?id=<?= $registro['paciente_id'] ?>#tab-prontuario" class="btn btn-outline btn-sm">← Voltar</a>
    </div>
</div>

<!-- Cabeçalho de datas -->
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.85rem;color:#166534;display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center;">
    <span>📋 Criado em: <strong><?= $criadoEm ?></strong></span>
    <?php if ($foiEditado): ?>
    <span>✏️ Última edição: <strong><?= $editadoEm ?></strong></span>
    <?php endif; ?>
    <span class="badge badge-blue" style="margin-left:auto;"><?= sanitize($registro['tipo_atendimento']) ?></span>
</div>

<div class="card">
    <h2>Dados do Registro</h2>

    <div class="info-grid">
        <div class="info-item">
            <label>Data do Atendimento</label>
            <span><?= date('d/m/Y', strtotime($registro['data_atendimento'])) ?></span>
        </div>
        <div class="info-item">
            <label>Tipo</label>
            <span><?= sanitize(ucfirst($registro['tipo_atendimento'])) ?></span>
        </div>
    </div>

    <?php if (trim($registro['subjetivo'] ?? '')): ?>
    <div style="margin-top:1rem;">
        <div class="timeline-section">Evolução Clínica</div>
        <div class="timeline-text" style="white-space:pre-wrap;margin-top:.35rem;"><?= sanitize($registro['subjetivo']) ?></div>
    </div>
    <?php endif; ?>

    <?php if (trim($registro['objetivo'] ?? '')): ?>
    <div style="margin-top:1rem;">
        <div class="timeline-section">Objetivo</div>
        <div class="timeline-text" style="white-space:pre-wrap;margin-top:.35rem;"><?= sanitize($registro['objetivo']) ?></div>
    </div>
    <?php endif; ?>

    <?php if (trim($registro['avaliacao'] ?? '')): ?>
    <div style="margin-top:1rem;">
        <div class="timeline-section">Avaliação</div>
        <div class="timeline-text" style="white-space:pre-wrap;margin-top:.35rem;"><?= sanitize($registro['avaliacao']) ?></div>
    </div>
    <?php endif; ?>

    <?php if (trim($registro['plano'] ?? '')): ?>
    <div style="margin-top:1rem;">
        <div class="timeline-section">Plano</div>
        <div class="timeline-text" style="white-space:pre-wrap;margin-top:.35rem;"><?= sanitize($registro['plano']) ?></div>
    </div>
    <?php endif; ?>

    <?php if (trim($registro['prescricao'] ?? '')): ?>
    <div style="margin-top:1rem;">
        <div class="timeline-section">Prescrição</div>
        <div class="timeline-text" style="white-space:pre-wrap;margin-top:.35rem;"><?= sanitize($registro['prescricao']) ?></div>
    </div>
    <?php endif; ?>

    <?php if (trim($registro['retorno'] ?? '')): ?>
    <div style="margin-top:1rem;">
        <div class="timeline-section">Exames / Retorno</div>
        <div class="timeline-text" style="white-space:pre-wrap;margin-top:.35rem;"><?= sanitize($registro['retorno']) ?></div>
    </div>
    <?php endif; ?>

</div>

<div class="form-actions">
    <a href="prontuario_novo.php?id=<?= $id ?>&paciente_id=<?= $registro['paciente_id'] ?>" class="btn btn-primary">✏️ Editar este Prontuário</a>
    <a href="paciente_ver.php?id=<?= $registro['paciente_id'] ?>#tab-prontuario" class="btn btn-outline">← Voltar ao Paciente</a>
</div>

<?php include 'includes/footer.php'; ?>
