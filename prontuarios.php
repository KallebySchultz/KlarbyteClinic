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

if ($busca) {
    $stmt = $db->prepare(
        "SELECT pr.*, p.nome as paciente_nome FROM prontuario pr
         JOIN pacientes p ON p.id = pr.paciente_id
         WHERE p.nome LIKE ?
         ORDER BY pr.data_atendimento DESC, pr.id DESC"
    );
    $stmt->execute(["%$busca%"]);
} else {
    $stmt = $db->query(
        "SELECT pr.*, p.nome as paciente_nome FROM prontuario pr
         JOIN pacientes p ON p.id = pr.paciente_id
         ORDER BY pr.data_atendimento DESC, pr.id DESC
         LIMIT 100"
    );
}

$registros = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-bar">
    <h2>Prontuários</h2>
    <a href="prontuario_novo.php" class="btn btn-primary">+ Novo Registro</a>
</div>

<div class="card">
    <form method="get" style="display:flex;gap:.75rem;margin-bottom:1rem;">
        <input type="text" name="q" value="<?= sanitize($busca) ?>" placeholder="Buscar por nome do paciente…"
               style="padding:.5rem .75rem;border:1.5px solid #d1d5db;border-radius:6px;font-size:.875rem;flex:1;">
        <button type="submit" class="btn btn-outline">Buscar</button>
        <?php if ($busca): ?>
        <a href="prontuarios.php" class="btn btn-outline">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if ($registros): ?>
    <table>
        <thead>
            <tr>
                <th>DATA</th>
                <th>PACIENTE</th>
                <th>TIPO</th>
                <th>EVOLUÇÃO</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $r): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($r['data_atendimento'])) ?></td>

                <td>
                    <a href="paciente_ver.php?id=<?= $r['paciente_id'] ?>" style="color:#2d7a50;font-weight:600;">
                        <?= sanitize($r['paciente_nome']) ?>
                    </a>
                </td>

                <td>
                    <span class="badge badge-blue">
                        <?= sanitize($r['tipo_atendimento']) ?>
                    </span>
                </td>

                <td>
                    <?=
                    sanitize(mb_strimwidth(
                        ($r['subjetivo'] ?? '') . ' ' .
                        ($r['objetivo'] ?? '') . ' ' .
                        ($r['avaliacao'] ?? ''),
                        0,
                        80,
                        '…'
                    ))
                    ?>
                </td>

                <td style="white-space:nowrap;">
                    <a href="prontuario_novo.php?id=<?= $r['id'] ?>&paciente_id=<?= $r['paciente_id'] ?>" class="btn btn-outline btn-sm">
                        Editar
                    </a>

                    <form method="post" style="display:inline;" onsubmit="return confirmDelete(this);">
                        <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="paciente_id" value="<?= $r['paciente_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:#9ca3af;font-size:.9rem;">Nenhum registro encontrado.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>