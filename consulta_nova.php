<?php
require 'config.php';

$db         = db();
$id         = (int)($_GET['id'] ?? 0);
$pacienteId = (int)($_GET['paciente_id'] ?? 0);
$editing    = $id > 0;
$consulta   = [];

// 🔥 pega usuário logado (ou usa 1 como padrão)
$usuarioId = $_SESSION['usuario_id'] ?? 1;

if ($editing) {
    $stmt = $db->prepare("SELECT * FROM consultas WHERE id = ?");
    $stmt->execute([$id]);
    $consulta = $stmt->fetch();
    if (!$consulta) { flash('Consulta não encontrada.', 'error'); redirect('consultas.php'); }
    $pacienteId = $pacienteId ?: $consulta['paciente_id'];
}

// Load patients for dropdown
$pacientes = $db->query("SELECT id, nome FROM pacientes ORDER BY nome ASC")->fetchAll();

$pageTitle  = $editing ? 'Editar Consulta' : 'Nova Consulta';
$activePage = 'consultas';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pId         = (int)($_POST['paciente_id'] ?? 0);
    $dataHora    = $_POST['data_hora'] ?? '';
    $tipo        = $_POST['tipo'] ?? 'Consulta';
    $status      = $_POST['status'] ?? 'Agendado';
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (!$pId || !$dataHora) {
        flash('Paciente e data/hora são obrigatórios.', 'error');
    } else {
        if ($editing) {
            $db->prepare(
                "UPDATE consultas 
                 SET paciente_id=?, data_hora=?, tipo=?, status=?, observacoes=?, usuario_id=? 
                 WHERE id=?"
            )->execute([$pId, $dataHora, $tipo, $status, $observacoes, $usuarioId, $id]);
        } else {
            $db->prepare(
                "INSERT INTO consultas 
                (paciente_id, data_hora, tipo, status, observacoes, usuario_id) 
                VALUES (?,?,?,?,?,?)"
            )->execute([$pId, $dataHora, $tipo, $status, $observacoes, $usuarioId]);
        }

        flash($editing ? 'Consulta atualizada.' : 'Consulta agendada com sucesso.');
        redirect($pId ? 'paciente_ver.php?id=' . $pId : 'consultas.php');
    }
}

include 'includes/header.php';
?>

<div class="page-bar">
    <h2><?= $editing ? 'Editar Consulta' : 'Nova Consulta' ?></h2>
    <a href="consultas.php" class="btn btn-outline">← Voltar</a>
</div>

<form method="post">
    <div class="card">
        <h2>📅 Dados da Consulta</h2>
        <div class="form-grid form-grid-2">
            <div class="form-group col-span-2">
                <label>Paciente *</label>
                <select name="paciente_id" required>
                    <option value="">Selecione o paciente…</option>
                    <?php foreach ($pacientes as $p): ?>
                    <?php $sel = (int)($consulta['paciente_id'] ?? $pacienteId) === $p['id']; ?>
                    <option value="<?= $p['id'] ?>" <?= $sel ? 'selected' : '' ?>>
                        <?= sanitize($p['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Data e Hora *</label>
                <input type="datetime-local" name="data_hora" required
                       value="<?= isset($consulta['data_hora']) ? date('Y-m-d\TH:i', strtotime($consulta['data_hora'])) : '' ?>">
            </div>

            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo">
                    <?php foreach (['Consulta','Retorno','Urgência'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($consulta['tipo'] ?? 'Consulta') === $t ? 'selected' : '' ?>>
                        <?= $t ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach (['Agendado','Confirmado','Realizado','Cancelado'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($consulta['status'] ?? 'Agendado') === $s ? 'selected' : '' ?>>
                        <?= $s ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-span-2">
                <label>Observações</label>
                <textarea name="observacoes"><?= sanitize($consulta['observacoes'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Salvar</button>
        <a href="consultas.php" class="btn btn-outline">Cancelar</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>