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

// Prontuário único por paciente
$stmtP = $db->prepare("SELECT * FROM prontuario WHERE paciente_id = ? ORDER BY id DESC LIMIT 1");
$stmtP->execute([$id]);
$prontuario = $stmtP->fetch() ?: null;

// Histórico de edições do prontuário
$prontuarioHistorico = [];
if ($prontuario) {
    $stmtPH = $db->prepare("
        SELECT h.*, u.nome AS editor
        FROM prontuario_historico h
        LEFT JOIN usuarios u ON u.id = h.usuario_id
        WHERE h.prontuario_id = ?
        ORDER BY h.editado_em DESC
    ");
    $stmtPH->execute([$prontuario['id']]);
    $prontuarioHistorico = $stmtPH->fetchAll();
}

// Consultas
$stmtC = $db->prepare("SELECT * FROM consultas WHERE paciente_id = ? ORDER BY data_hora DESC LIMIT 10");
$stmtC->execute([$id]);
$consultas = $stmtC->fetchAll();

// Campos anamnese ativos
$campos = $db->query("SELECT * FROM campos_anamnese WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll();

// Exames
$stmtE = $db->prepare("SELECT * FROM exames WHERE paciente_id = ? ORDER BY created_at DESC");
$stmtE->execute([$id]);
$exames = $stmtE->fetchAll();

$pageTitle  = sanitize($paciente['nome']);
$activePage = 'pacientes';

// Calculate patient age
$idade = null;
if ($paciente['data_nascimento']) {
    $nascimento = new DateTime($paciente['data_nascimento']);
    $hoje = new DateTime();
    $idade = (int)$nascimento->diff($hoje)->y;
}

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
        <?php if ($prontuario): ?>
        <a href="prontuario_novo.php?id=<?= $prontuario['id'] ?>&paciente_id=<?= $id ?>" class="btn btn-primary btn-sm">Editar Prontuário</a>
        <?php else: ?>
        <a href="prontuario_novo.php?paciente_id=<?= $id ?>" class="btn btn-primary btn-sm">+ Prontuário</a>
        <?php endif; ?>
        <a href="consulta_nova.php?paciente_id=<?= $id ?>" class="btn btn-outline btn-sm">+ Consulta</a>
        <a href="pacientes.php" class="btn btn-outline btn-sm">← Voltar</a>
    </div>
</div>

<div style="margin-top:1.25rem;">
    <div class="tabs">
        <button class="tab-btn active" data-tab="tab-dados">Dados</button>
        <button class="tab-btn" data-tab="tab-anamnese">Anamnese</button>
        <button class="tab-btn" data-tab="tab-prontuario">Prontuário</button>
        <button class="tab-btn" data-tab="tab-consultas">Consultas (<?= count($consultas) ?>)</button>
        <button class="tab-btn" data-tab="tab-exames">Exames (<?= count($exames) ?>)</button>
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
                <div class="info-item"><label>Idade</label><span><?= $idade !== null ? $idade . ' anos' : '—' ?></span></div>
                <div class="info-item"><label>Sexo</label><span><?= ['M'=>'Masculino','F'=>'Feminino','O'=>'Outro'][$paciente['sexo']] ?? '—' ?></span></div>
                <div class="info-item"><label>Profissão</label><span><?= sanitize($paciente['profissao'] ?? '—') ?></span></div>
                <div class="info-item"><label>Estado Civil</label><span><?= sanitize($paciente['estado_civil'] ?? '—') ?></span></div>
                <div class="info-item"><label>Número de Filhos</label><span><?= $paciente['numero_filhos'] !== null ? (int)$paciente['numero_filhos'] : '—' ?></span></div>
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
        <?php if ($prontuario): ?>

        <?php
        $prCriado  = !empty($prontuario['created_at'])  ? date('d/m/Y H:i', strtotime($prontuario['created_at']))  : null;
        $prEditado = !empty($prontuario['updated_at'])   ? date('d/m/Y H:i', strtotime($prontuario['updated_at']))  : null;
        $prFoiEditado = $prEditado && $prCriado && $prEditado !== $prCriado;
        ?>

        <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-bottom:.75rem;">
            <a href="prontuario_imprimir.php?paciente_id=<?= $id ?>" target="_blank" class="btn btn-outline btn-sm">Imprimir</a>
            <a href="prontuario_novo.php?id=<?= $prontuario['id'] ?>&paciente_id=<?= $id ?>" class="btn btn-primary btn-sm">Editar Prontuário</a>
        </div>

        <!-- Cabeçalho de datas -->
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.85rem;color:#1e3a8a;display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center;">
            <?php if ($prCriado): ?><span>Criado em: <strong><?= $prCriado ?></strong></span><?php endif; ?>
            <?php if ($prFoiEditado): ?><span>Última edição: <strong><?= $prEditado ?></strong></span><?php endif; ?>
            <span class="badge badge-blue" style="margin-left:auto;"><?= sanitize($prontuario['tipo_atendimento']) ?></span>
        </div>

        <div class="card">
            <div class="info-grid" style="margin-bottom:1rem;">
                <div class="info-item">
                    <label>Data do Atendimento</label>
                    <span><?= date('d/m/Y', strtotime($prontuario['data_atendimento'])) ?></span>
                </div>
                <div class="info-item">
                    <label>Tipo</label>
                    <span><?= sanitize(ucfirst($prontuario['tipo_atendimento'])) ?></span>
                </div>
            </div>

            <?php if (trim($prontuario['subjetivo'] ?? '')): ?>
            <div style="margin-top:1rem;">
                <div class="timeline-section">Evolução Clínica</div>
                <div class="timeline-text" style="white-space:pre-wrap;margin-top:.35rem;"><?= sanitize($prontuario['subjetivo']) ?></div>
            </div>
            <?php endif; ?>

            <?php if (trim($prontuario['prescricao'] ?? '')): ?>
            <div style="margin-top:1rem;">
                <div class="timeline-section">Prescrição</div>
                <div class="timeline-text" style="white-space:pre-wrap;margin-top:.35rem;"><?= sanitize($prontuario['prescricao']) ?></div>
            </div>
            <?php endif; ?>

            <?php if (trim($prontuario['retorno'] ?? '')): ?>
            <div style="margin-top:1rem;">
                <div class="timeline-section">Exames / Retorno</div>
                <div class="timeline-text" style="white-space:pre-wrap;margin-top:.35rem;"><?= sanitize($prontuario['retorno']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($prontuarioHistorico)): ?>
        <div class="card" style="margin-top:1.25rem;">
            <h2 style="margin-bottom:1rem;">Histórico de Edições</h2>
            <ul class="timeline">
                <?php foreach ($prontuarioHistorico as $h):
                    $tipoLetra = strtoupper(substr($h['tipo_atendimento'] ?? 'E', 0, 1));
                    $dataEdit  = date('d/m/Y', strtotime($h['editado_em'])) . ' às ' . date('H:i', strtotime($h['editado_em']));
                ?>
                <li class="timeline-item">
                    <div class="timeline-dot"><?= $tipoLetra ?></div>
                    <div class="timeline-body">
                        <div class="timeline-meta">
                            <span class="timeline-date">Editado em <strong><?= $dataEdit ?></strong></span>
                            <?php if (!empty($h['editor'])): ?>
                            <span class="timeline-date">por <strong><?= sanitize($h['editor']) ?></strong></span>
                            <?php endif; ?>
                            <?php if (!empty($h['tipo_atendimento'])): ?>
                            <span class="badge badge-blue" style="margin-left:auto;"><?= sanitize($h['tipo_atendimento']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($h['data_atendimento'])): ?>
                        <div style="font-size:.8rem;color:#6b7280;margin-bottom:.4rem;">
                            Data do atendimento (antes): <?= date('d/m/Y', strtotime($h['data_atendimento'])) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (trim($h['subjetivo'] ?? '')): ?>
                        <div class="timeline-section">Evolução Clínica</div>
                        <div class="timeline-text"><?= sanitize($h['subjetivo']) ?></div>
                        <?php endif; ?>
                        <?php if (trim($h['prescricao'] ?? '')): ?>
                        <div class="timeline-section">Prescrição</div>
                        <div class="timeline-text"><?= sanitize($h['prescricao']) ?></div>
                        <?php endif; ?>
                        <?php if (trim($h['retorno'] ?? '')): ?>
                        <div class="timeline-section">Exames / Retorno</div>
                        <div class="timeline-text"><?= sanitize($h['retorno']) ?></div>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="card">
            <p style="color:#9ca3af;font-size:.9rem;">Nenhum prontuário criado ainda. <a href="prontuario_novo.php?paciente_id=<?= $id ?>">Criar agora</a>.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- TAB: Consultas -->
    <div id="tab-consultas" class="tab-pane">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
            <span style="font-size:.9rem;color:#6b7280;"><?= count($consultas) ?> consulta(s) cadastrada(s)</span>
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
                            <form method="post" action="consultas.php" style="display:inline;" onsubmit="return confirm('Excluir esta consulta?');">
                                <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                                <input type="hidden" name="paciente_id" value="<?= $id ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                            </form>
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

    <!-- TAB: Exames -->
    <div id="tab-exames" class="tab-pane">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
            <span style="font-size:.9rem;color:#6b7280;"><?= count($exames) ?> exame(s) cadastrado(s)</span>
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('modal-exame').style.display='flex'">+ Novo Exame</button>
        </div>

        <?php if ($exames): ?>
        <div class="card" style="padding:0;overflow:hidden;">
            <table>
                <thead>
                    <tr>
                        <th>NOME DO EXAME</th>
                        <th>DESCRIÇÃO</th>
                        <th>ARQUIVO</th>
                        <th>DATA</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exames as $e): ?>
                    <?php
                    $isImagem = str_starts_with($e['arquivo_tipo'], 'image/');
                    $isPdf    = $e['arquivo_tipo'] === 'application/pdf';
                    $icone    = $isPdf ? 'PDF' : ($isImagem ? 'IMG' : 'ARQ');
                    $tam      = $e['arquivo_tamanho'];
                    $tamanhoFmt = $tam > 0
                        ? (round($tam / 1024, 1) < 1024
                            ? round($tam / 1024, 1) . ' KB'
                            : round($tam / (1024 * 1024), 1) . ' MB')
                        : '';
                    ?>
                    <tr>
                        <td style="font-weight:500;"><?= sanitize($e['nome']) ?></td>
                        <td style="color:#6b7280;font-size:.875rem;"><?= sanitize(mb_strimwidth($e['descricao'] ?? '', 0, 60, '…')) ?></td>
                        <td>
                            <?= $icone ?> <span style="font-size:.82rem;color:#6b7280;"><?= sanitize($e['arquivo_nome']) ?><?= $tamanhoFmt ? " ({$tamanhoFmt})" : '' ?></span>
                        </td>
                        <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($e['created_at'])) ?></td>
                        <td style="white-space:nowrap;">
                            <a href="exame_arquivo.php?id=<?= $e['id'] ?>&acao=ver" target="_blank" class="btn btn-outline btn-sm">Ver</a>
                            <a href="exame_arquivo.php?id=<?= $e['id'] ?>&acao=baixar" class="btn btn-outline btn-sm">Baixar</a>
                            <form method="post" action="exame_excluir.php" style="display:inline;" onsubmit="return confirm('Excluir este exame?');">
                                <input type="hidden" name="exame_id" value="<?= $e['id'] ?>">
                                <input type="hidden" name="paciente_id" value="<?= $id ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card">
            <p style="color:#9ca3af;font-size:.9rem;">Nenhum exame cadastrado ainda. Clique em <strong>+ Novo Exame</strong> para importar.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Upload de Exame -->
<div id="modal-exame" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:.75rem;padding:2rem;width:100%;max-width:480px;box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <h2 style="margin-bottom:1.25rem;">Novo Exame</h2>
        <form method="post" action="exame_upload.php" enctype="multipart/form-data">
            <input type="hidden" name="paciente_id" value="<?= $id ?>">
            <div class="form-group">
                <label class="form-label">Nome do Exame *</label>
                <input type="text" name="nome" class="form-control" required placeholder="Ex: Hemograma completo">
            </div>
            <div class="form-group">
                <label class="form-label">Descrição (opcional)</label>
                <input type="text" name="descricao" class="form-control" placeholder="Ex: Resultado de 10/03/2026">
            </div>
            <div class="form-group">
                <label class="form-label">Arquivo (PDF, JPG, PNG, GIF — máx. 20 MB) *</label>
                <input type="file" name="arquivo" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.gif">
            </div>
            <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1.25rem;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-exame').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Fecha modal ao clicar fora
document.getElementById('modal-exame').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

// Abre a aba correta conforme parâmetro ?tab=
(function() {
    var tabParam = new URLSearchParams(window.location.search).get('tab');
    if (tabParam) {
        var btn = document.querySelector('[data-tab="tab-' + tabParam + '"]');
        if (btn) btn.click();
    }
})();
</script>

<?php include 'includes/footer.php'; ?>