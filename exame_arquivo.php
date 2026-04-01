<?php
require 'config.php';

$db  = db();
$id  = (int)($_GET['id'] ?? 0);
$acao = $_GET['acao'] ?? 'ver'; // 'ver' ou 'baixar'

if (!$id) {
    flash('Exame inválido.', 'error');
    redirect('pacientes.php');
}

$stmt = $db->prepare("SELECT * FROM exames WHERE id = ?");
$stmt->execute([$id]);
$exame = $stmt->fetch();

if (!$exame) {
    flash('Exame não encontrado.', 'error');
    redirect('pacientes.php');
}

// Garante que o nome do arquivo no disco não contenha path traversal
$nomeArquivo = basename($exame['arquivo_path']);
$caminho     = __DIR__ . '/uploads/exames/' . $nomeArquivo;

if (!file_exists($caminho)) {
    flash('Arquivo não encontrado no servidor.', 'error');
    redirect("paciente_ver.php?id={$exame['paciente_id']}&tab=exames");
}

// Tipos MIME seguros para exibição inline
$mimesSeguros = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
$mime = in_array($exame['arquivo_tipo'], $mimesSeguros, true) ? $exame['arquivo_tipo'] : 'application/octet-stream';

$nomeDownload = $exame['arquivo_nome'];

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($caminho));
header('X-Content-Type-Options: nosniff');

// RFC 5987 / RFC 6266 — codificação segura do nome do arquivo
$nomeSeguro = preg_replace('/[^\w\-. ]/u', '_', $nomeDownload);
$nomeEncoded = rawurlencode($nomeDownload);

if ($acao === 'baixar') {
    header("Content-Disposition: attachment; filename=\"{$nomeSeguro}\"; filename*=UTF-8''{$nomeEncoded}");
} else {
    header("Content-Disposition: inline; filename=\"{$nomeSeguro}\"; filename*=UTF-8''{$nomeEncoded}");
}

// Limpa qualquer output buffer antes de enviar o arquivo
while (ob_get_level()) ob_end_clean();
readfile($caminho);
exit;
