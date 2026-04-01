<?php
require 'config.php';

$db = db();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { flash('Paciente inválido.', 'error'); redirect('pacientes.php'); }

$stmt = $db->prepare("SELECT * FROM pacientes WHERE id = ?");
$stmt->execute([$id]);
$paciente = $stmt->fetch();
if (!$paciente) { flash('Paciente não encontrado.', 'error'); redirect('pacientes.php'); }

// Anamnese
$stmtA = $db->prepare("SELECT * FROM anamnese WHERE paciente_id = ? ORDER BY id DESC LIMIT 1");
$stmtA->execute([$id]);
$anamnese = $stmtA->fetch() ?: [];

// ✅ PRONTUÁRIO CORRIGIDO
$stmtP = $db->prepare("SELECT * FROM prontuario WHERE paciente_id = ? ORDER BY data_atendimento DESC, id DESC");
$stmtP->execute([$id]);
$prontuarios = $stmtP->fetchAll();

// Consultas
$stmtC = $db->prepare("SELECT * FROM consultas WHERE paciente_id = ? ORDER BY data_hora DESC LIMIT 10");
$stmtC->execute([$id]);
$consultas = $stmtC->fetchAll();

// Campos anamnese ativos
$campos = $db->query("SELECT * FROM campos_anamnese WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll();

$pageTitle  = sanitize($paciente['nome']);
$activePage = 'pacientes';

// Initials for avatar
$initials = '';
foreach (explode(' ', $paciente['nome']) as $w) {
    $initials .= mb_strtoupper(mb_substr($w,0,1));
    if(strlen($initials)>=2) break;
}

include 'includes/header.php';
?>

<div class="page-bar">
    <div class="patient-header" style="margin-bottom:0;">
        <div class="patient-avatar"><?= sanitize($initials) ?></div>
        <div class="patient-info">
            <strong><?= sanitize($paciente['nome']) ?></strong>
            <span>
                <?php if ($paciente['data_nascimento']): ?>
                <?= date('d/m/Y', strtotime($paciente['data_nascimento'])) ?> ·
                <?php endif; ?>
                <?= sanitize($paciente['celular'] ?? '') ?>
                <?php if ($paciente['email']): ?> · <?= sanitize($paciente['email']) ?><?php endif; ?>
            </span>
        </div>
    </div>
    <div style="display:flex;gap:.5rem;">
        <a href="paciente_novo.php?id=<?= $id ?>" class="btn btn-outline btn-sm">Editar</a>
        <a href="prontuario_novo.php?paciente_id=<?= $id ?>" class="btn btn-primary btn-sm">+ Prontuário</a>
        <a href="consulta_nova.php?paciente_id=<?= $id ?>" class="btn btn-outline btn-sm">+ Consulta</a>
        <a href="pacientes.php" class="btn btn-outline btn-sm">← Voltar</a>
    </div>
</div>

<div style="margin-top:1.25rem;">
    <div class="tabs">
        <button class="tab-btn active" data-tab="tab-dados">Dados</button>
        <button class="tab-btn" data-tab="tab-anamnese">Anamnese</button>
        <button class="tab-btn" data-tab="tab-prontuario">Prontuário (<?= count($prontuarios) ?>)</button>
        <button class="tab-btn" data-tab="tab-consultas">Consultas (<?= count($consultas) ?>)</button>
    </div>

    <!-- TAB: Dados pessoais -->
    <div id="tab-dados" class="tab-pane active">
        <div class="card">
            <h2>Dados Pessoais</h2>
            <div class="info-grid">
                <div class="info-item"><label>Nome</label><span><?= sanitize($paciente['nome']) ?></span></div>
                <div class="info-item"><label>CPF</label><span><?= sanitize($paciente['cpf'] ?? '—') ?></span></div>
                <div class="info-item"><label>Celular</label><span><?= sanitize($paciente['celular'] ?? '—') ?></span></div>
                <div class="info-item"><label>E-mail</label><span><?= sanitize($paciente['email'] ?? '—') ?></span></div>
                <div class="info-item"><label>Nascimento</label><span><?= $paciente['data_nascimento'] ? date('d/m/Y', strtotime($paciente['data_nascimento'])) : '—' ?></span></div>
                <div class="info-item"><label>Sexo</label><span><?= ['M'=>'Masculino','F'=>'Feminino','O'=>'Outro'][$paciente['sexo']] ?? '—' ?></span></div>
                <div class="info-item"><label>Profissão</label><span><?= sanitize($paciente['profissao'] ?? '—') ?></span></div>
                <div class="info-item"><label>Estado Civil</label><span><?= sanitize($paciente['estado_civil'] ?? '—') ?></span></div>
                <div class="info-item"><label>Filhos</label><span><?= isset($paciente['tem_filhos']) ? ($paciente['tem_filhos'] ? 'Sim' : 'Não') : '—' ?></span></div>
                <?php if ($paciente['tem_filhos']): ?>
                <div class="info-item"><label>Qtd. de Filhos</label><span><?= $paciente['quantidade_filhos'] !== null ? (int)$paciente['quantidade_filhos'] : '—' ?></span></div>
                <?php if ($paciente['idades_filhos']): ?>
                <div class="info-item"><label>Idades dos Filhos</label><span><?= sanitize($paciente['idades_filhos']) ?></span></div>
                <?php endif; ?>
                <?php endif; ?>
                <div class="info-item"><label>Cidade</label><span><?= sanitize($paciente['cidade'] ?? '—') ?></span></div>
                <div class="info-item" style="grid-column:span 3"><label>Endereço</label><span><?= sanitize($paciente['endereco'] ?? '—') ?></span></div>
                <?php if ($paciente['observacoes']): ?>
                <div class="info-item" style="grid-column:span 3"><label>Observações</label><span><?= sanitize($paciente['observacoes']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TAB: Anamnese -->
    <div id="tab-anamnese" class="tab-pane">
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.1rem;padding-bottom:.6rem;border-bottom:1px solid #e5e7eb;">
                <h2 style="margin-bottom:0;padding-bottom:0;border:none;">Anamnese</h2>
                <a href="paciente_novo.php?id=<?= $id ?>#anamnese" class="btn btn-outline btn-sm">Editar</a>
            </div>
            <?php if ($anamnese && $campos): ?>
            <div class="info-grid" style="grid-template-columns:1fr;">
                <?php foreach ($campos as $campo): ?>
                <?php $val = trim($anamnese[$campo['nome']] ?? ''); ?>
                <?php if ($val): ?>
                <div class="info-item">
                    <label><?= sanitize($campo['label']) ?></label>
                    <span style="white-space:pre-wrap;"><?= sanitize($val) ?></span>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color:#9ca3af;font-size:.9rem;">Anamnese não preenchida. <a href="paciente_novo.php?id=<?= $id ?>">Preencher agora</a>.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB: Prontuário -->
    <div id="tab-prontuario" class="tab-pane">
        <div style="display:flex;justify-content:flex-end;margin-bottom:.75rem;">
            <a href="prontuario_novo.php?paciente_id=<?= $id ?>" class="btn btn-primary btn-sm">+ Novo Registro</a>
        </div>
        <?php if ($prontuarios): ?>
        <ul class="timeline">
            <?php foreach ($prontuarios as $pr): ?>

            <?php
            $evolucao = trim(
                ($pr['subjetivo'] ?? '') . ' ' .
                ($pr['objetivo'] ?? '') . ' ' .
                ($pr['avaliacao'] ?? '')
            );
            ?>

            <li class="timeline-item">
                <div class="timeline-dot"><?= mb_strtoupper(mb_substr($pr['tipo_atendimento'],0,1)) ?></div>
                <div class="timeline-body">
                    <div class="timeline-meta">
                        <strong><?= date('d/m/Y', strtotime($pr['data_atendimento'])) ?></strong>
                        <span class="badge badge-blue"><?= sanitize($pr['tipo_atendimento']) ?></span>

                        <a href="prontuario_novo.php?id=<?= $pr['id'] ?>&paciente_id=<?= $id ?>" style="margin-left:auto;font-size:.78rem;color:#2d7a50;">Editar</a>

                        <form method="post" action="prontuarios.php" style="display:inline;" onsubmit="return confirmDelete(this);">
                            <input type="hidden" name="delete_id" value="<?= $pr['id'] ?>">
                            <input type="hidden" name="paciente_id" value="<?= $id ?>">
                            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.78rem;color:#ef4444;">Excluir</button>
                        </form>
                    </div>

                    <?php if ($evolucao): ?>
                    <div class="timeline-section">Evolução Clínica</div>
                    <div class="timeline-text"><?= sanitize($evolucao) ?></div>
                    <?php endif; ?>

                    <?php if (trim($pr['prescricao'] ?? '')): ?>
                    <div class="timeline-section">Prescrição</div>
                    <div class="timeline-text"><?= sanitize($pr['prescricao']) ?></div>
                    <?php endif; ?>

                    <?php if (trim($pr['retorno'] ?? '')): ?>
                    <div class="timeline-section">Exames / Retorno</div>
                    <div class="timeline-text"><?= sanitize($pr['retorno']) ?></div>
                    <?php endif; ?>

                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="card">
            <p style="color:#9ca3af;font-size:.9rem;">Nenhum registro no prontuário ainda. <a href="prontuario_novo.php?paciente_id=<?= $id ?>">Adicionar agora</a>.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- TAB: Consultas -->
    <div id="tab-consultas" class="tab-pane">
        <div style="display:flex;justify-content:flex-end;margin-bottom:.75rem;">
            <a href="consulta_nova.php?paciente_id=<?= $id ?>" class="btn btn-primary btn-sm">+ Nova Consulta</a>
        </div>
        <?php if ($consultas): ?>
        <div class="card" style="padding:0;overflow:hidden;">
            <table>
                <thead>
                    <tr><th>DATA / HORA</th><th>TIPO</th><th>STATUS</th><th>OBSERVAÇÕES</th><th></th></tr>
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
                        <td><?= sanitize($c['tipo']) ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= sanitize($c['status']) ?></span></td>
                        <td><?= sanitize(mb_strimwidth($c['observacoes'] ?? '', 0, 60, '…')) ?></td>
                        <td>
                            <a href="consulta_nova.php?id=<?= $c['id'] ?>&paciente_id=<?= $id ?>" class="btn btn-outline btn-sm">Editar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card">
            <p style="color:#9ca3af;font-size:.9rem;">Nenhuma consulta agendada. <a href="consulta_nova.php?paciente_id=<?= $id ?>">Agendar agora</a>.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>