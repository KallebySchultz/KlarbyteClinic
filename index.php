<?php
require 'config.php';
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$db = db();

$totalPacientes = $db->query('SELECT COUNT(*) FROM pacientes')->fetchColumn();
$totalProntuario = $db->query('SELECT COUNT(*) FROM prontuario')->fetchColumn();
$consultasHoje = $db->query("SELECT COUNT(*) FROM consultas WHERE DATE(data_hora) = CURDATE()")->fetchColumn();

$agendaHoje = $db->query(
    "SELECT c.*, p.nome FROM consultas c
     JOIN pacientes p ON p.id = c.paciente_id
     WHERE DATE(c.data_hora) = CURDATE()
     ORDER BY c.data_hora ASC LIMIT 10"
)->fetchAll();

$ultimosPacientes = $db->query(
    "SELECT id, nome, celular, created_at FROM pacientes ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

include 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">👤</div>
        <div>
            <div class="stat-value"><?= $totalPacientes ?></div>
            <div class="stat-label">Pacientes Cadastrados</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📘</div>
        <div>
            <div class="stat-value"><?= $consultasHoje ?></div>
            <div class="stat-label">Consultas Hoje</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">📋</div>
        <div>
            <div class="stat-value"><?= $totalProntuario ?></div>
            <div class="stat-label">Registros no Prontuário</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal">📅</div>
        <div>
            <div class="stat-value"><?= date('d/m/Y') ?></div>
            <div class="stat-label">Data de Hoje</div>
        </div>
    </div>
</div>

<div class="panels-grid">
    <div class="panel">
        <div class="panel-header">
            <h2>Consultas de Hoje</h2>
            <a href="consultas.php" class="btn btn-outline btn-sm">Ver todas</a>
        </div>
        <?php if ($agendaHoje): ?>
        <table>
            <thead>
                <tr>
                    <th>HORÁRIO</th>
                    <th>PACIENTE</th>
                    <th>TIPO</th>
                    <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agendaHoje as $c): ?>
                <?php
                    $badgeClass = match($c['status']) {
                        'Confirmado' => 'badge-green',
                        'Agendado'   => 'badge-orange',
                        'Realizado'  => 'badge-blue',
                        default      => 'badge-gray'
                    };
                ?>
                <tr>
                    <td><?= date('H:i', strtotime($c['data_hora'])) ?></td>
                    <td><a href="paciente_ver.php?id=<?= $c['paciente_id'] ?>"><?= sanitize($c['nome']) ?></a></td>
                    <td><?= sanitize($c['tipo']) ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= sanitize($c['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#9ca3af;font-size:.9rem;padding:.5rem 0;">Nenhuma consulta agendada para hoje.</p>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>Últimos Pacientes</h2>
            <a href="pacientes.php" class="btn btn-outline btn-sm">Ver todos</a>
        </div>
        <?php if ($ultimosPacientes): ?>
        <table>
            <thead>
                <tr>
                    <th>NOME</th>
                    <th>CELULAR</th>
                    <th>CADASTRO</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimosPacientes as $p): ?>
                <tr>
                    <td><a href="paciente_ver.php?id=<?= $p['id'] ?>"><?= sanitize($p['nome']) ?></a></td>
                    <td><?= sanitize($p['celular'] ?? '—') ?></td>
                    <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#9ca3af;font-size:.9rem;padding:.5rem 0;">Nenhum paciente cadastrado ainda.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
