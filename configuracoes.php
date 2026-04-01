<?php
require 'config.php';

$db = db();
$pageTitle  = 'Configurações';
$activePage = 'configuracoes';

// Handle toggle field
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $fid = (int)$_POST['toggle_id'];
    $db->prepare("UPDATE campos_anamnese SET ativo = NOT ativo WHERE id = ?")->execute([$fid]);
    flash('Campo atualizado.');
    redirect('configuracoes.php');
}

// Handle reorder / save labels
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_campos'])) {
    foreach ($_POST['campo'] as $fid => $data) {
        $fid = (int)$fid;
        $label  = trim($data['label'] ?? '');
        $ordem  = (int)($data['ordem'] ?? 0);
        $obrig  = isset($data['obrigatorio']) ? 1 : 0;
        if ($label) {
            $db->prepare("UPDATE campos_anamnese SET label=?, ordem=?, obrigatorio=? WHERE id=?")
               ->execute([$label, $ordem, $obrig, $fid]);
        }
    }
    flash('Configurações salvas.');
    redirect('configuracoes.php');
}

// Handle add new field
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_campo'])) {
    $label = trim($_POST['new_label'] ?? '');
    $tipo  = $_POST['new_tipo'] ?? 'textarea';
    if ($label) {
        $nome = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $label));
        $maxOrdem = $db->query("SELECT MAX(ordem) FROM campos_anamnese")->fetchColumn() ?: 0;
        $db->prepare("INSERT INTO campos_anamnese (nome, label, tipo, ativo, ordem) VALUES (?,?,?,1,?)")
           ->execute([$nome, $label, $tipo, $maxOrdem + 1]);
        flash('Campo adicionado.');
    }
    redirect('configuracoes.php');
}

// Handle delete field
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_campo'])) {
    $db->prepare("DELETE FROM campos_anamnese WHERE id = ?")->execute([(int)$_POST['delete_campo']]);
    flash('Campo removido.');
    redirect('configuracoes.php');
}

$campos = $db->query("SELECT * FROM campos_anamnese ORDER BY ordem ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="page-bar">
    <h2>Configurações do Sistema</h2>
</div>

<div class="card">
    <h2>Campos da Anamnese</h2>
    <p style="color:#6b7280;font-size:.85rem;margin-bottom:1rem;">Personalize os campos que aparecem na anamnese dos pacientes.</p>

    <form method="post">
        <input type="hidden" name="save_campos" value="1">
        <table>
            <thead>
                <tr><th>ORDEM</th><th>LABEL</th><th>TIPO</th><th>OBRIGATÓRIO</th><th>ATIVO</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($campos as $c): ?>
                <tr>
                    <td>
                        <input type="number" name="campo[<?= $c['id'] ?>][ordem]" value="<?= (int)$c['ordem'] ?>"
                               style="width:60px;padding:.3rem .5rem;border:1px solid #d1d5db;border-radius:4px;">
                    </td>
                    <td>
                        <input type="text" name="campo[<?= $c['id'] ?>][label]" value="<?= sanitize($c['label']) ?>"
                               style="width:100%;padding:.3rem .5rem;border:1px solid #d1d5db;border-radius:4px;">
                    </td>
                    <td><span class="badge badge-blue"><?= sanitize($c['tipo']) ?></span></td>
                    <td style="text-align:center;">
                        <input type="checkbox" name="campo[<?= $c['id'] ?>][obrigatorio]" <?= $c['obrigatorio'] ? 'checked' : '' ?>>
                    </td>
                    <td style="text-align:center;">
                        <span class="badge <?= $c['ativo'] ? 'badge-green' : 'badge-gray' ?>"><?= $c['ativo'] ? 'Sim' : 'Não' ?></span>
                    </td>
                    <td style="white-space:nowrap;">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="toggle_id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-outline btn-sm"><?= $c['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                        </form>
                        <form method="post" style="display:inline;" onsubmit="return confirmDelete(this);">
                            <input type="hidden" name="delete_campo" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="form-actions" style="margin-top:.75rem;">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>Adicionar Campo</h2>
    <form method="post" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="add_campo" value="1">
        <div class="form-group" style="flex:1;min-width:180px;">
            <label>Nome do Campo</label>
            <input type="text" name="new_label" placeholder="Ex.: Histórico Emocional" required>
        </div>
        <div class="form-group" style="min-width:140px;">
            <label>Tipo</label>
            <select name="new_tipo">
                <option value="textarea">Área de texto</option>
                <option value="text">Texto curto</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Adicionar</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
