<?php
/**
 * Exporta as candidaturas de uma vaga (ou de todas as vagas da empresa)
 * em formato CSV abrivel pelo Excel.
 *
 * Uso:
 *   /teste/empresa/exportar.php?vaga_id=N -> candidaturas da vaga N
 *   /teste/empresa/exportar.php           -> todas as candidaturas da empresa
 */

require_once __DIR__ . '/../includes/auth.php';
requireEmpresa();

$db = getDB();
$empresaId = (int)$_SESSION['perfil_id'];
$vagaId = isset($_GET['vaga_id']) ? (int)$_GET['vaga_id'] : 0;

if ($vagaId) {
    // Confirma que a vaga e da empresa logada
    $stmt = $db->prepare("SELECT titulo FROM vagas WHERE id=? AND empresa_id=?");
    $stmt->execute([$vagaId, $empresaId]);
    $vaga = $stmt->fetch();
    if (!$vaga) {
        http_response_code(404);
        exit('Vaga nao encontrada.');
    }
    $stmt = $db->prepare("
        SELECT c.id, u.nome AS aluno_nome, u.email, a.curso, a.universidade, a.semestre,
               a.cidade, a.estado, a.linkedin, a.github, c.status, c.carta, c.criado_em,
               v.titulo AS vaga_titulo
          FROM candidaturas c
          JOIN alunos a ON c.aluno_id = a.id
          JOIN usuarios u ON a.usuario_id = u.id
          JOIN vagas v ON c.vaga_id = v.id
         WHERE c.vaga_id = ? AND v.empresa_id = ?
         ORDER BY c.criado_em DESC
    ");
    $stmt->execute([$vagaId, $empresaId]);
    $arquivo = 'candidaturas_vaga_' . $vagaId . '_' . date('Y-m-d') . '.csv';
} else {
    $stmt = $db->prepare("
        SELECT c.id, u.nome AS aluno_nome, u.email, a.curso, a.universidade, a.semestre,
               a.cidade, a.estado, a.linkedin, a.github, c.status, c.carta, c.criado_em,
               v.titulo AS vaga_titulo
          FROM candidaturas c
          JOIN alunos a ON c.aluno_id = a.id
          JOIN usuarios u ON a.usuario_id = u.id
          JOIN vagas v ON c.vaga_id = v.id
         WHERE v.empresa_id = ?
         ORDER BY c.criado_em DESC
    ");
    $stmt->execute([$empresaId]);
    $arquivo = 'candidaturas_empresa_' . date('Y-m-d') . '.csv';
}
$linhas = $stmt->fetchAll();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $arquivo . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');

$out = fopen('php://output', 'w');
// BOM para o Excel reconhecer UTF-8
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, [
    'ID', 'Aluno', 'E-mail', 'Curso', 'Universidade', 'Semestre',
    'Cidade', 'Estado', 'LinkedIn', 'GitHub', 'Status', 'Vaga',
    'Carta', 'Data candidatura'
], ';');

foreach ($linhas as $l) {
    fputcsv($out, [
        $l['id'],
        $l['aluno_nome'],
        $l['email'],
        $l['curso'],
        $l['universidade'],
        $l['semestre'],
        $l['cidade'],
        $l['estado'],
        $l['linkedin'],
        $l['github'],
        $l['status'],
        $l['vaga_titulo'],
        str_replace(["\r","\n"], ' ', (string)$l['carta']),
        $l['criado_em'],
    ], ';');
}
fclose($out);
exit;
