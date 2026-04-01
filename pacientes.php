<?php
require 'config.php';

$pageTitle  = 'Pacientes';
$activePage = 'pacientes';

$db = db();

// =========================
// EXCLUIR PACIENTE (SEGURO)
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {

    $id = (int)$_POST['delete_id'];

    // 🔍 Verificar vínculos
    $temConsultas = $db->prepare("SELECT COUNT(*) FROM consultas WHERE paciente_id = ?");
    $temConsultas->execute([$id]);

    $temProntuario = $db->prepare("SELECT COUNT(*) FROM prontuario WHERE paciente_id = ?");
    $temProntuario->execute([$id]);

    $temAnamnese = $db->prepare("SELECT COUNT(*) FROM anamnese WHERE paciente_id = ?");
    $temAnamnese->execute([$id]);

    if (
        $temConsultas->fetchColumn() > 0 ||
        $temProntuario->fetchColumn() > 0 ||
        $temAnamnese->fetchColumn() > 0
    ) {
        flash('Não é possível excluir este paciente pois ele possui histórico (consultas, prontuário ou anamnese).', 'error');
        redirect('pacientes.php');
    }

    // ✅ Se não tiver vínculo, pode excluir
    $stmt = $db->prepare('DELETE FROM pacientes WHERE id = ?');
    $stmt->execute([$id]);

    flash('Paciente excluído com sucesso.');
    redirect('pacientes.php');
}

// =========================
// BUSCA
// =========================
$busca = trim($_GET['q'] ?? '');

if ($busca) {
    $stmt = $db->prepare(
        "SELECT * FROM pacientes 
         WHERE nome LIKE ? OR cpf LIKE ? OR celular LIKE ? 
         ORDER BY nome ASC"
    );
    $like = "%$busca%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $db->query("SELECT * FROM pacientes ORDER BY nome ASC");
}

$pacientes = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-bar">
    <h2>Lista de Pacientes</h2>
    <a href="paciente_novo.php" class="btn btn-primary">+ Novo Paciente</a>
</div>

<div class="card">
    <form method="get" style="display:flex;gap:.75rem;margin-bottom:1rem;">
        <input type="text" name="q" value="<?= sanitize($busca) ?>"
               placeholder="Buscar por nome, CPF ou celular…"
               style="padding:.5rem .75rem;border:1.5px solid #d1d5db;border-radius:6px;font-size:.875rem;flex:1;">
        <button type="submit" class="btn btn-outline">Buscar</button>

        <?php if ($busca): ?>
            <a href="pacientes.php" class="btn btn-outline">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if ($pacientes): ?>
        <table>
            <thead>
                <tr>
                    <th>NOME</th>
                    <th>CPF</th>
                    <th>CELULAR</th>
                    <th>CIDADE</th>
                    <th>CADASTRO</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pacientes as $p): ?>
                    <tr>
                        <td>
                            <a href="paciente_ver.php?id=<?= $p['id'] ?>" style="color:#2d7a50;font-weight:600;">
                                <?= sanitize($p['nome']) ?>
                            </a>
                        </td>
                        <td><?= sanitize($p['cpf'] ?? '—') ?></td>
                        <td><?= sanitize($p['celular'] ?? '—') ?></td>
                        <td><?= sanitize($p['cidade'] ?? '—') ?></td>
                        <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                        <td style="white-space:nowrap;">
                            <a href="paciente_ver.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Ver</a>
                            <a href="paciente_novo.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Editar</a>

                            <form method="post" style="display:inline;" onsubmit="return confirmDelete(this);">
                                <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color:#9ca3af;font-size:.9rem;padding:.5rem 0;">
            <?= $busca 
                ? 'Nenhum paciente encontrado para "' . sanitize($busca) . '".' 
                : 'Nenhum paciente cadastrado ainda.' ?>
        </p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>