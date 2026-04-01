<?php
require 'config.php';

$db = db();
$pageTitle  = 'Consultas';
$activePage = 'consultas';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $db->prepare('DELETE FROM consultas WHERE id = ?')->execute([(int)$_POST['delete_id']]);
    flash('Consulta excluída.');
    $back = !empty($_POST['paciente_id']) ? 'paciente_ver.php?id=' . (int)$_POST['paciente_id'] . '&tab=consultas' : 'consultas.php';
    redirect($back);
}

$filtroData   = $_GET['data']   ?? '';
$filtroStatus = $_GET['status'] ?? '';
$busca        = trim($_GET['q'] ?? '');

$where  = '1=1';
$params = [];
if ($filtroData)   { $where .= ' AND DATE(c.data_hora) = ?'; $params[] = $filtroData; }
if ($filtroStatus) { $where .= ' AND c.status = ?';          $params[] = $filtroStatus; }
if ($busca)        { $where .= ' AND p.nome LIKE ?';         $params[] = "%$busca%"; }

$stmt = $db->prepare(
    "SELECT c.*, p.nome as paciente_nome FROM consultas c
     JOIN pacientes p ON p.id = c.paciente_id
     WHERE $where ORDER BY c.data_hora ASC"
);
$stmt->execute($params);
$consultas = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-bar">
    <h2>Consultas</h2>
    <a href="consulta_nova.php" class="btn btn-primary">+ Nova Consulta</a>
</div>

<div class="card">
    <form method="get" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;">
        <div class="form-group" style="margin:0;">
            <input type="text" name="q" value="<?= sanitize($busca) ?>" placeholder="Buscar por nome do paciente…"
                   style="padding:.5rem .75rem;border:1.5px solid #d1d5db;border-radius:6px;font-size:.875rem;min-width:220px;">
        </div>
        <div class="form-group" style="margin:0;">
            <input type="date" name="data" value="<?= sanitize($filtroData) ?>"
                   style="padding:.5rem .75rem;border:1.5px solid #d1d5db;border-radius:6px;font-size:.875rem;">
        </div>
        <div class="form-group" style="margin:0;">
            <select name="status" style="padding:.5rem .75rem;border:1.5px solid #d1d5db;border-radius:6px;font-size:.875rem;">
                <option value="">Todos os status</option>
                <?php foreach (['Agendado','Confirmado','Realizado','Cancelado'] as $s): ?>
                <option value="<?= $s ?>" <?= $filtroStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-outline">Buscar</button>
        <?php if ($filtroData || $filtroStatus || $busca): ?>
        <a href="consultas.php" class="btn btn-outline">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if ($consultas): ?>
    <p style="font-size:.85rem;color:#6b7280;margin-bottom:.75rem;"><?= count($consultas) ?> consulta(s) encontrada(s)</p>
    <table>
        <thead>
            <tr><th>DATA / HORA</th><th>PACIENTE</th><th>TIPO</th><th>STATUS</th><th>OBSERVAÇÕES</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($consultas as $c): ?>
            <?php
            $badgeClass = match($c['status']) {
                'Confirmado' => 'badge-green',
                'Agendado'   => 'badge-orange',
                'Realizado'  => 'badge-blue',
                default      => 'badge-gray'
            };
            ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($c['data_hora'])) ?></td>
                <td><a href="paciente_ver.php?id=<?= $c['paciente_id'] ?>" style="color:#2d7a50;font-weight:600;"><?= sanitize($c['paciente_nome']) ?></a></td>
                <td><?= sanitize($c['tipo']) ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= sanitize($c['status']) ?></span></td>
                <td><?= sanitize(mb_strimwidth($c['observacoes'] ?? '', 0, 50, '…')) ?></td>
                <td style="white-space:nowrap;">
                    <a href="consulta_nova.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">Editar</a>
                    <form method="post" style="display:inline;" onsubmit="return confirmDelete(this);">
                        <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:#9ca3af;font-size:.9rem;">Nenhuma consulta encontrada.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
