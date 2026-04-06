<?php
require 'config.php';

$db = db();
$pageTitle  = 'Prontuários';
$activePage = 'prontuarios';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $stmt = $db->prepare('DELETE FROM prontuario WHERE id = ?');
    $stmt->execute([(int)$_POST['delete_id']]);
    flash('Registro excluído.');
    $back = !empty($_POST['paciente_id']) ? 'paciente_ver.php?id=' . (int)$_POST['paciente_id'] . '#tab-prontuario' : 'prontuarios.php';
    redirect($back);
}

$busca = trim($_GET['q'] ?? '');

// Show one row per patient (unique prontuário per patient).
// Search covers patient name and clinical notes.
if ($busca) {
    $like = "%$busca%";
    $stmt = $db->prepare(
        "SELECT p.id as paciente_id, p.nome as paciente_nome,
                COUNT(pr.id) as total_registros,
                MAX(pr.data_atendimento) as ultima_data,
                MAX(pr.updated_at) as ultima_edicao
         FROM prontuario pr
         JOIN pacientes p ON p.id = pr.paciente_id
         WHERE p.nome LIKE ? OR pr.subjetivo LIKE ? OR pr.prescricao LIKE ? OR pr.retorno LIKE ?
         GROUP BY p.id, p.nome
         ORDER BY ultima_data DESC"
    );
    $stmt->execute([$like, $like, $like, $like]);
} else {
    $stmt = $db->query(
        "SELECT p.id as paciente_id, p.nome as paciente_nome,
                COUNT(pr.id) as total_registros,
                MAX(pr.data_atendimento) as ultima_data,
                MAX(pr.updated_at) as ultima_edicao
         FROM prontuario pr
         JOIN pacientes p ON p.id = pr.paciente_id
         GROUP BY p.id, p.nome
         ORDER BY ultima_data DESC
         LIMIT 100"
    );
}

$registros = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-bar">
    <h2>Prontuários</h2>
    <a href="pacientes.php" class="btn btn-primary">+ Novo Registro</a>
</div>

<div class="card">
    <form method="get" style="display:flex;gap:.75rem;margin-bottom:1rem;">
        <input type="text" name="q" value="<?= sanitize($busca) ?>" placeholder="Buscar por nome do paciente, evolução, prescrição…"
               style="padding:.5rem .75rem;border:1.5px solid #d1d5db;border-radius:6px;font-size:.875rem;flex:1;">
        <button type="submit" class="btn btn-outline">Buscar</button>
        <?php if ($busca): ?>
        <a href="prontuarios.php" class="btn btn-outline">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if ($registros): ?>
    <p style="font-size:.85rem;color:#6b7280;margin-bottom:.75rem;"><?= count($registros) ?> paciente(s) <?= $busca ? 'encontrado(s)' : 'com prontuário' ?></p>
    <table>
        <thead>
            <tr>
                <th>PACIENTE</th>
                <th>REGISTROS</th>
                <th>ÚLTIMA CONSULTA</th>
                <th>ÚLTIMA EDIÇÃO</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $r): ?>
            <tr>
                <td>
                    <a href="paciente_ver.php?id=<?= $r['paciente_id'] ?>&tab=prontuario" style="color:#1a5fb4;font-weight:600;">
                        <?= sanitize($r['paciente_nome']) ?>
                    </a>
                </td>

                <td><?= (int)$r['total_registros'] ?> registro(s)</td>

                <td><?= date('d/m/Y', strtotime($r['ultima_data'])) ?></td>

                <td>
                    <?php if ($r['ultima_edicao']): ?>
                    <span style="color:#6b7280;font-size:.82rem;">
                        <?= date('d/m/Y H:i', strtotime($r['ultima_edicao'])) ?>
                    </span>
                    <?php else: ?>
                    <span style="color:#9ca3af;font-size:.82rem;">—</span>
                    <?php endif; ?>
                </td>

                <td style="white-space:nowrap;">
                    <a href="paciente_ver.php?id=<?= $r['paciente_id'] ?>&tab=prontuario" class="btn btn-outline btn-sm">
                        Ver Prontuário
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:#9ca3af;font-size:.9rem;">Nenhum prontuário encontrado.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>