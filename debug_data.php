<?php
// ARQUIVO: blocks/teacher_checklist/debug_data.php

require_once('../../config.php');

// 1. Segurança: Exige login e permissão de administrador
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Configuração da Página
$PAGE->set_url('/blocks/teacher_checklist/debug_data.php');
$PAGE->set_context($context);
$PAGE->set_title('Debug Teacher Checklist');
$PAGE->set_heading('Raio-X dos Dados do Checklist');

echo $OUTPUT->header();

// 2. Informações do Usuário Atual
echo $OUTPUT->box_start('generalbox');
echo "<h3>Diagnóstico de Identidade</h3>";
echo "<p><strong>Você está logado como:</strong> " . fullname($USER) . "</p>";
echo "<p><strong>Seu User ID (userid):</strong> <span class='badge badge-primary' style='font-size:1.2em'>$USER->id</span></p>";
echo "<p><em>Se os registros abaixo não tiverem este ID ($USER->id), eles NÃO aparecerão na sua exportação de privacidade.</em></p>";
echo $OUTPUT->box_end();

// 3. Consulta SQL (Busca os últimos 100 registros)
// Usamos {chaves} para que o Moodle coloque o prefixo correto (mdl_) automaticamente.
$sql = "SELECT b.id, 
               b.title, 
               b.type,
               b.subtype,
               b.courseid, 
               c.shortname AS coursename, 
               b.userid, 
               u.username,
               u.firstname,
               u.lastname,
               b.timecreated
        FROM {block_teacher_checklist} b
        JOIN {course} c ON c.id = b.courseid
        LEFT JOIN {user} u ON u.id = b.userid
        ORDER BY b.id DESC";

$records = $DB->get_records_sql($sql, [], 0, 100);

// 4. Exibição da Tabela
if (empty($records)) {
    echo $OUTPUT->notification('A tabela block_teacher_checklist está vazia! Nenhum dado encontrado.', 'warning');
} else {
    echo "<hr>";
    echo "<h4>Últimos 100 registros no banco de dados:</h4>";
    
    echo '<table class="table table-bordered table-striped table-hover">';
    echo '<thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Título / Item</th>
                <th>Curso (ID)</th>
                <th>Dono do Registro (UserID)</th>
                <th>Data Criação</th>
                <th>Status na Exportação</th>
            </tr>
          </thead>';
    echo '<tbody>';

    foreach ($records as $r) {
        $date = userdate($r->timecreated);
        
        // Lógica de Destaque
        $row_style = "";
        $status_msg = "";
        $is_suspect = false;

        // Verifica se o dono é o usuário atual
        if ($r->userid == $USER->id) {
            $row_style = "table-success"; // Verde (Tudo certo)
            $status_msg = "<span class='text-success'>✓ Ok (Seu dado)</span>";
        } 
        // Verifica se o dono é Admin (geralmente ID 2) ou Guest (0/1)
        elseif ($r->userid <= 2) {
            $row_style = "table-danger"; // Vermelho (Problema provável)
            $is_suspect = true;
            $status_msg = "<strong>⚠ ALERTA:</strong> Pertence ao Admin/Guest";
        } 
        else {
            $row_style = "table-warning"; // Amarelo (Outro usuário)
            $status_msg = "Pertence a outro usuário";
        }

        // Formatação do nome do dono
        $owner_name = $r->username ? "{$r->firstname} ({$r->username})" : "<strong>USUÁRIO DESCONHECIDO (ID {$r->userid})</strong>";
        
        echo "<tr class='$row_style'>";
        echo "<td>{$r->id}</td>";
        echo "<td><span class='badge badge-secondary'>{$r->type}</span> <small>{$r->subtype}</small></td>";
        echo "<td>" . format_string($r->title) . "</td>";
        echo "<td>{$r->coursename} <small class='text-muted'>(ID: {$r->courseid})</small></td>";
        
        // Célula do Dono (Destaque forte se for suspeito)
        if ($is_suspect) {
             echo "<td class='font-weight-bold text-danger'>ID: {$r->userid}<br><small>$owner_name</small></td>";
        } else {
             echo "<td>ID: {$r->userid}<br><small>$owner_name</small></td>";
        }

        echo "<td>$date</td>";
        echo "<td>$status_msg</td>";
        echo "</tr>";
    }

    echo '</tbody>';
    echo '</table>';
}

echo $OUTPUT->footer();
