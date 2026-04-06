<?php
require 'config.php';

$db          = db();
$prontuarioId = (int)($_GET['id']          ?? 0);
$pacienteId   = (int)($_GET['paciente_id'] ?? 0);

// Load single record when only id is given
if ($prontuarioId && !$pacienteId) {
    $stmt = $db->prepare("SELECT paciente_id FROM prontuario WHERE id = ?");
    $stmt->execute([$prontuarioId]);
    $row = $stmt->fetch();
    if ($row) $pacienteId = (int)$row['paciente_id'];
}

if (!$pacienteId) {
    flash('Paciente não informado.', 'error');
    redirect('pacientes.php');
}

// Patient
$stmtPac = $db->prepare("SELECT * FROM pacientes WHERE id = ?");
$stmtPac->execute([$pacienteId]);
$paciente = $stmtPac->fetch();
if (!$paciente) {
    flash('Paciente não encontrado.', 'error');
    redirect('pacientes.php');
}

// Anamnese
$stmtA = $db->prepare("SELECT * FROM anamnese WHERE paciente_id = ? ORDER BY id DESC LIMIT 1");
$stmtA->execute([$pacienteId]);
$anamnese = $stmtA->fetch() ?: [];

$campos = $db->query("SELECT * FROM campos_anamnese WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll();

// Prontuários
if ($prontuarioId) {
    $stmtPr = $db->prepare("SELECT p.*, u.nome AS profissional FROM prontuario p LEFT JOIN usuarios u ON u.id = p.usuario_id WHERE p.id = ?");
    $stmtPr->execute([$prontuarioId]);
} else {
    $stmtPr = $db->prepare("SELECT p.*, u.nome AS profissional FROM prontuario p LEFT JOIN usuarios u ON u.id = p.usuario_id WHERE p.paciente_id = ? ORDER BY p.data_atendimento ASC, p.id ASC");
    $stmtPr->execute([$pacienteId]);
}
$prontuarios = $stmtPr->fetchAll();

// Histórico de edições
$historicoByProntuario = [];
if ($prontuarioId) {
    $stmtHist = $db->prepare("SELECT h.id, h.prontuario_id, h.editado_em, h.data_atendimento, h.tipo_atendimento, h.subjetivo, h.prescricao, h.retorno, u.nome AS editor FROM prontuario_historico h LEFT JOIN usuarios u ON u.id = h.usuario_id WHERE h.prontuario_id = ? ORDER BY h.editado_em DESC");
    $stmtHist->execute([$prontuarioId]);
    foreach ($stmtHist->fetchAll() as $h) {
        $historicoByProntuario[$h['prontuario_id']][] = $h;
    }
} elseif ($prontuarios) {
    $ids = array_column($prontuarios, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtHist = $db->prepare("SELECT h.id, h.prontuario_id, h.editado_em, h.data_atendimento, h.tipo_atendimento, h.subjetivo, h.prescricao, h.retorno, u.nome AS editor FROM prontuario_historico h LEFT JOIN usuarios u ON u.id = h.usuario_id WHERE h.prontuario_id IN ($placeholders) ORDER BY h.prontuario_id, h.editado_em DESC");
    $stmtHist->execute($ids);
    foreach ($stmtHist->fetchAll() as $h) {
        $historicoByProntuario[$h['prontuario_id']][] = $h;
    }
}

// Patient age
$idade = null;
if ($paciente['data_nascimento']) {
    $idade = (int)(new DateTime($paciente['data_nascimento']))->diff(new DateTime())->y;
}

$tipoAtendimento = [
    'consulta'  => 'Consulta',
    'retorno'   => 'Retorno',
    'urgencia'  => 'Urgência',
];

$sexoLabel = ['M' => 'Masculino', 'F' => 'Feminino', 'O' => 'Outro'];

$titulo = $prontuarioId
    ? 'Prontuário — ' . date('d/m/Y', strtotime($prontuarios[0]['data_atendimento'] ?? 'now'))
    : 'Prontuário Completo';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sanitize($titulo) ?> — <?= sanitize($paciente['nome']) ?></title>
<style>
/* ── Reset & base ─────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
    font-size: 12pt;
    color: #111;
    background: #f0f4fa;
    line-height: 1.5;
}

/* ── Print wrapper ────────────────────────── */
.print-page {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    padding: 2rem 2.5rem;
    box-shadow: 0 2px 16px rgba(0,0,0,.12);
    min-height: 100vh;
}

/* ── Toolbar (screen only) ────────────────── */
.toolbar {
    display: flex;
    gap: .75rem;
    justify-content: flex-end;
    margin-bottom: 1.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .5rem 1.1rem;
    border-radius: 6px;
    font-size: .875rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    border: none;
    transition: opacity .15s;
}
.btn:hover { opacity: .85; }
.btn-primary { background: #1a5fb4; color: #fff; }
.btn-outline  { background: transparent; color: #1a5fb4; border: 1.5px solid #1a5fb4; }

/* ── Document header ──────────────────────── */
.doc-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #0d2137;
}

.doc-clinic-name {
    font-size: 1.2rem;
    font-weight: 800;
    color: #0d2137;
    letter-spacing: .02em;
}

.doc-meta {
    font-size: .78rem;
    color: #6b7280;
    text-align: right;
    line-height: 1.6;
}

.doc-meta strong { color: #1a2f5a; }

/* ── Section ──────────────────────────────── */
.section {
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: .7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #1a5fb4;
    border-bottom: 1.5px solid #bfdbfe;
    padding-bottom: .3rem;
    margin-bottom: .85rem;
}

/* ── Patient demographics ─────────────────── */
.patient-name {
    font-size: 1.35rem;
    font-weight: 800;
    color: #1a2f5a;
    margin-bottom: .4rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .5rem .75rem;
}

.info-item label {
    display: block;
    font-size: .68rem;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: .1rem;
}

.info-item span {
    font-size: .875rem;
    color: #1f2937;
}

/* ── Anamnese ─────────────────────────────── */
.anamnese-item {
    margin-bottom: .75rem;
}

.anamnese-item label {
    display: block;
    font-size: .72rem;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: .15rem;
}

.anamnese-item p {
    font-size: .875rem;
    color: #1f2937;
    white-space: pre-wrap;
    padding: .4rem .6rem;
    background: #f9fafb;
    border-left: 3px solid #bfdbfe;
    border-radius: 0 4px 4px 0;
}

/* ── Prontuário entries ───────────────────── */
.prontuario-entry {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 1.25rem;
    overflow: hidden;
    page-break-inside: avoid;
}

.entry-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #eff6ff;
    padding: .6rem 1rem;
    border-bottom: 1px solid #bfdbfe;
}

.entry-date {
    font-weight: 700;
    font-size: .95rem;
    color: #1a2f5a;
}

.entry-meta {
    display: flex;
    align-items: center;
    gap: .75rem;
    font-size: .8rem;
    color: #4b5563;
}

.badge {
    display: inline-block;
    padding: .2rem .6rem;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    background: #dbeafe;
    color: #1e40af;
}

.entry-body {
    padding: .85rem 1rem;
}

.field-label {
    font-size: .72rem;
    font-weight: 700;
    color: #1a5fb4;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-top: .75rem;
    margin-bottom: .2rem;
}

.field-label:first-child { margin-top: 0; }

.field-value {
    font-size: .875rem;
    color: #1f2937;
    white-space: pre-wrap;
    line-height: 1.55;
}

/* ── Signature area ───────────────────────── */
.signature-area {
    margin-top: 3rem;
    display: flex;
    justify-content: flex-end;
}

.signature-box {
    text-align: center;
    min-width: 220px;
}

.signature-line {
    border-top: 1px solid #9ca3af;
    margin-bottom: .4rem;
}

.signature-label {
    font-size: .78rem;
    color: #6b7280;
}

/* ── Footer ───────────────────────────────── */
.doc-footer {
    margin-top: 2rem;
    padding-top: .75rem;
    border-top: 1px solid #e5e7eb;
    font-size: .72rem;
    color: #9ca3af;
    text-align: center;
}

/* ── Histórico de edições ─────────────────── */
.historico-section {
    margin-top: .5rem;
    border-top: 1px dashed #bfdbfe;
    padding-top: .5rem;
}

.historico-title {
    font-size: .68rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: .5rem;
}

.historico-item {
    margin-bottom: .6rem;
    padding: .45rem .65rem;
    background: #fafafa;
    border-left: 2px solid #d1d5db;
    border-radius: 0 4px 4px 0;
    font-size: .8rem;
}

.historico-item-meta {
    color: #6b7280;
    margin-bottom: .25rem;
}

.historico-item-meta strong { color: #374151; }

.historico-item-field-label {
    font-size: .7rem;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-top: .3rem;
    margin-bottom: .1rem;
}

.historico-item-field-value {
    color: #4b5563;
    white-space: pre-wrap;
}
@media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .print-page {
        box-shadow: none;
        padding: 0;
        max-width: 100%;
    }

    .prontuario-entry { page-break-inside: avoid; }
    .section { page-break-inside: avoid; }
}
</style>
</head>
<body>

<div class="print-page">

    <!-- Toolbar (screen only) -->
    <div class="toolbar">
        <button class="btn btn-primary" onclick="window.print()">Imprimir</button>
        <button class="btn btn-outline" onclick="window.close()" aria-label="Voltar">← Voltar</button>
    </div>

    <!-- Document header -->
    <div class="doc-header">
        <div>
            <div class="doc-clinic-name">EnterClinic</div>
            <div style="font-size:.8rem;color:#6b7280;margin-top:.2rem;">Sistema de Gestão Clínica</div>
        </div>
        <div class="doc-meta">
            <div><?= $prontuarioId ? 'Registro único' : 'Prontuário completo' ?></div>
            <div>Emitido em: <strong><?= date('d/m/Y') ?> às <?= date('H:i') ?></strong></div>
            <?php if (!$prontuarioId && $prontuarios): ?>
            <div><?= count($prontuarios) ?> registro(s)</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Patient demographics -->
    <div class="section">
        <div class="section-title">Identificação do Paciente</div>
        <div class="patient-name"><?= sanitize($paciente['nome']) ?></div>

        <div class="info-grid" style="margin-top:.6rem;">
            <?php if ($paciente['data_nascimento']): ?>
            <div class="info-item">
                <label>Data de Nascimento</label>
                <span><?= date('d/m/Y', strtotime($paciente['data_nascimento'])) ?><?= $idade !== null ? ' (' . $idade . ' anos)' : '' ?></span>
            </div>
            <?php endif; ?>

            <?php if ($paciente['sexo']): ?>
            <div class="info-item">
                <label>Sexo</label>
                <span><?= sanitize($sexoLabel[$paciente['sexo']] ?? $paciente['sexo']) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($paciente['cpf']): ?>
            <div class="info-item">
                <label>CPF</label>
                <span><?= sanitize($paciente['cpf']) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($paciente['celular']): ?>
            <div class="info-item">
                <label>Celular</label>
                <span><?= sanitize($paciente['celular']) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($paciente['telefone']): ?>
            <div class="info-item">
                <label>Telefone</label>
                <span><?= sanitize($paciente['telefone']) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($paciente['email']): ?>
            <div class="info-item">
                <label>E-mail</label>
                <span><?= sanitize($paciente['email']) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($paciente['profissao']): ?>
            <div class="info-item">
                <label>Profissão</label>
                <span><?= sanitize($paciente['profissao']) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($paciente['estado_civil']): ?>
            <div class="info-item">
                <label>Estado Civil</label>
                <span><?= sanitize($paciente['estado_civil']) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($paciente['numero_filhos'] !== null && $paciente['numero_filhos'] !== ''): ?>
            <div class="info-item">
                <label>N.º de Filhos</label>
                <span><?= (int)$paciente['numero_filhos'] ?></span>
            </div>
            <?php endif; ?>

            <?php if ($paciente['endereco']): ?>
            <div class="info-item" style="grid-column:span 3">
                <label>Endereço</label>
                <span><?= sanitize($paciente['endereco']) ?><?= $paciente['cidade'] ? ', ' . sanitize($paciente['cidade']) : '' ?><?= $paciente['cep'] ? ' — CEP ' . sanitize($paciente['cep']) : '' ?></span>
            </div>
            <?php endif; ?>

            <?php if ($paciente['observacoes']): ?>
            <div class="info-item" style="grid-column:span 3">
                <label>Observações</label>
                <span><?= sanitize($paciente['observacoes']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Anamnese -->
    <?php if ($anamnese && $campos):
        $camposPreenchidos = array_filter($campos, fn($campo) => trim($anamnese[$campo['nome']] ?? '') !== '');
        if ($camposPreenchidos):
    ?>
    <div class="section">
        <div class="section-title">Anamnese</div>
        <?php foreach ($camposPreenchidos as $campo): ?>
        <div class="anamnese-item">
            <label><?= sanitize($campo['label']) ?></label>
            <p><?= sanitize($anamnese[$campo['nome']]) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; endif; ?>

    <!-- Prontuário entries -->
    <div class="section">
        <div class="section-title">
            <?= $prontuarioId ? 'Registro de Atendimento' : 'Histórico de Atendimentos' ?>
        </div>

        <?php if ($prontuarios): ?>
            <?php foreach ($prontuarios as $pr):
                $tipoLabel = $tipoAtendimento[$pr['tipo_atendimento']] ?? ucfirst($pr['tipo_atendimento']);
                $hasContent = trim($pr['subjetivo'] ?? '') || trim($pr['objetivo'] ?? '') ||
                              trim($pr['avaliacao'] ?? '') || trim($pr['plano'] ?? '') ||
                              trim($pr['prescricao'] ?? '') || trim($pr['retorno'] ?? '');
            ?>
            <div class="prontuario-entry">
                <div class="entry-header">
                    <div class="entry-date">
                        <?= date('d/m/Y', strtotime($pr['data_atendimento'])) ?>
                    </div>
                    <div class="entry-meta">
                        <span class="badge"><?= sanitize($tipoLabel) ?></span>
                        <?php if (!empty($pr['profissional']) && $pr['profissional'] !== 'Administrador'): ?>
                        <span>Prof.: <?= sanitize($pr['profissional']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($hasContent): ?>
                <div class="entry-body">
                    <?php if (trim($pr['subjetivo'] ?? '')): ?>
                    <div class="field-label">Evolução Clínica / Subjetivo</div>
                    <div class="field-value"><?= sanitize($pr['subjetivo']) ?></div>
                    <?php endif; ?>

                    <?php if (trim($pr['objetivo'] ?? '')): ?>
                    <div class="field-label">Objetivo</div>
                    <div class="field-value"><?= sanitize($pr['objetivo']) ?></div>
                    <?php endif; ?>

                    <?php if (trim($pr['avaliacao'] ?? '')): ?>
                    <div class="field-label">Avaliação / Hipótese Diagnóstica</div>
                    <div class="field-value"><?= sanitize($pr['avaliacao']) ?></div>
                    <?php endif; ?>

                    <?php if (trim($pr['plano'] ?? '')): ?>
                    <div class="field-label">Plano Terapêutico</div>
                    <div class="field-value"><?= sanitize($pr['plano']) ?></div>
                    <?php endif; ?>

                    <?php if (trim($pr['prescricao'] ?? '')): ?>
                    <div class="field-label">Prescrição</div>
                    <div class="field-value"><?= sanitize($pr['prescricao']) ?></div>
                    <?php endif; ?>

                    <?php if (trim($pr['retorno'] ?? '')): ?>
                    <div class="field-label">Exames / Retorno</div>
                    <div class="field-value"><?= sanitize($pr['retorno']) ?></div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="entry-body" style="color:#9ca3af;font-size:.85rem;font-style:italic;">
                    Nenhum dado registrado neste atendimento.
                </div>
                <?php endif; ?>

                <?php if (!empty($historicoByProntuario[$pr['id']])): ?>
                <div class="entry-body historico-section">
                    <div class="historico-title">Histórico de Edições</div>
                    <?php foreach ($historicoByProntuario[$pr['id']] as $h):
                        $dataEdit = date('d/m/Y', strtotime($h['editado_em'])) . ' às ' . date('H:i', strtotime($h['editado_em']));
                    ?>
                    <div class="historico-item">
                        <div class="historico-item-meta">
                            Editado em <strong><?= $dataEdit ?></strong>
                            <?php if (!empty($h['editor']) && $h['editor'] !== 'Administrador'): ?>
                            por <strong><?= sanitize($h['editor']) ?></strong>
                            <?php endif; ?>
                        </div>
                        <?php if (trim($h['subjetivo'] ?? '')): ?>
                        <div class="historico-item-field-label">Evolução Clínica (antes)</div>
                        <div class="historico-item-field-value"><?= sanitize($h['subjetivo']) ?></div>
                        <?php endif; ?>
                        <?php if (trim($h['prescricao'] ?? '')): ?>
                        <div class="historico-item-field-label">Prescrição (antes)</div>
                        <div class="historico-item-field-value"><?= sanitize($h['prescricao']) ?></div>
                        <?php endif; ?>
                        <?php if (trim($h['retorno'] ?? '')): ?>
                        <div class="historico-item-field-label">Exames / Retorno (antes)</div>
                        <div class="historico-item-field-value"><?= sanitize($h['retorno']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:#9ca3af;font-size:.875rem;font-style:italic;">Nenhum registro de atendimento encontrado.</p>
        <?php endif; ?>
    </div>

    <!-- Signature -->
    <div class="signature-area">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Assinatura e Carimbo do Profissional</div>
        </div>
    </div>

    <!-- Document footer -->
    <div class="doc-footer">
        EnterClinic — Documento gerado em <?= date('d/m/Y') ?> às <?= date('H:i') ?>
        <?php if ($prontuarioId): ?>
        — Registro #<?= $prontuarioId ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
