<?php

declare(strict_types=1);
error_reporting(E_ALL); ini_set('display_errors','1');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  
  function norm($s){ $t=@iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); return $t!==false?$t:(string)$s; }
  function esc($t){ return str_replace(["\\","(",")","\r","\n"],["\\\\","\(","\)","",""],(string)$t); }
  function T($s){ return esc(norm($s)); }

  // Dados básicos
  $nome        = $_POST['nome']        ?? '';
  $endereco    = $_POST['endereco']    ?? '';
  $cidade      = $_POST['cidade']      ?? '';
  $email       = $_POST['email']       ?? '';
  $telefone    = $_POST['telefone']    ?? '';
  $objetivo    = $_POST['objetivo']    ?? '';
  $formacao    = $_POST['formacao']    ?? '';
  $qualif      = $_POST['qualif']      ?? '';
  $conhec      = $_POST['conhec']      ?? '';
  $idiomas     = $_POST['idiomas']     ?? '';

  // Experiências 
  $empresa   = $_POST['empresa']   ?? [];
  $local     = $_POST['local']     ?? [];
  $funcao    = $_POST['funcao']    ?? [];
  $periodo   = $_POST['periodo']   ?? []; // periodo que ficou na empresa
  $atividades= $_POST['atividades']?? [];

  
  $x=50; $y=800; $lh=16; $L=[];

  $line=function($txt,$sz=12,$bold=false) use(&$L,$x,&$y,$lh){
    
    $L[] = sprintf("BT /F1 %d Tf %.2f %.2f Td (%s) Tj ET", $bold?($sz+1):$sz, $x, $y, T($txt));
    $y -= $lh;
  };
  $wrap=function($txt,$maxw=480,$sz=12) use($line){
    $txt = trim((string)$txt);
    if ($txt===''){ $line('—',$sz); return; }
    
    $raw = preg_split('/\r?\n/',$txt);
    if (count($raw)>1){
      foreach($raw as $r){
        $r = trim($r);
        if ($r==='') continue;
        $line('• '. $r, $sz);
      }
      return;
    }
    
    $txt=preg_replace('/\s+/',' ',$txt); $w=explode(' ',$txt); $buf='';
    foreach($w as $p){
      $t=trim(($buf?$buf.' ':'').$p);
      if(strlen($t)*6>$maxw){ if($buf!=='')$line($buf,$sz); $buf=$p; } else { $buf=$t; }
    }
    if($buf!=='') $line($buf,$sz);
  };

  
  $line(strtoupper($nome!==''?$nome:'NOME SOBRENOME'), 18, true);
  // Bloco de contato (linhas curtas como no modelo)
  if ($endereco!=='') $line($endereco);
  if ($cidade!=='')   $line($cidade);
  if ($telefone!=='') $line('Telefone – '.$telefone);
  if ($email!=='')    $line('E-mail: '.$email);
  $y -= 6;

  // Seção: Objetivo
  $line('Objetivo Profissional:', 12, true);
  $wrap($objetivo);  $y -= 2;

  // Seção: Formação Acadêmica
  $y -= 4;
  $line('Formacao Academica:', 12, true);
  $wrap($formacao);  $y -= 2;

  // Seção: Resumo das Qualificações Profissionais
  $y -= 4;
  $line('Resumo das Qualificacoes Profissionais:', 12, true);
  $wrap($qualif);  $y -= 2;

  // Seção: Conhecimentos
  $y -= 4;
  $line('Conhecimentos:', 12, true);
  $wrap($conhec);  $y -= 2;

  // Seção: Lingua Estrangeira
  $y -= 4;
  $line('Lingua Estrangeira:', 12, true);
  $wrap($idiomas);  $y -= 2;

  // Seção: Experiência Profissional
  $y -= 4;
  $line('Experiencia Profissional:', 12, true);
  $len = max(count($empresa),count($local),count($funcao),count($periodo),count($atividades));
  if ($len===0){
    $line('—');
  } else {
    for($i=0;$i<$len;$i++){
      $emp = trim(($empresa[$i]??''));
      if ($emp!=='') $line('Empresa: '.$emp, 12);
      $loc = trim(($local[$i]??''));
      if ($loc!=='') $line('Cidade: '.$loc, 12);
      $fun = trim(($funcao[$i]??''));
      if ($fun!=='') $line('Funcao: '.$fun, 12);
      $per = trim(($periodo[$i]??''));
      if ($per!=='') $line('Periodo: '.$per, 12);
      $ati = trim(($atividades[$i]??''));
      if ($ati!==''){ $wrap('Atividades: '.$ati, 480, 12); }
      $y -= 4;
    }
  }

  // Construção do PDF
  $content = "q\n".implode("\n",$L)."\nQ\n"; $lenC = strlen($content);
  $objs = [];
  $objs[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj";
  $objs[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj";
  $objs[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj";
  $objs[] = "4 0 obj << /Length $lenC >> stream\n$content\nendstream endobj";
  $objs[] = "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj";

  $pdf = "%PDF-1.4\n"; $offs=[0];
  foreach($objs as $o){ $offs[] = strlen($pdf); $pdf .= $o."\n"; }
  $xref = strlen($pdf); $n = count($objs)+1;

  $pdf .= "xref\n0 $n\n0000000000 65535 f \n";
  for($i=1;$i<$n;$i++) $pdf .= sprintf("%010d 00000 n \n",$offs[$i]);
  $pdf .= "trailer << /Size $n /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

  while (ob_get_level()) ob_end_clean();
  header('Content-Type: application/pdf');
  header('Content-Disposition: inline; filename=\"curriculo.pdf\"');
  echo $pdf; exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gerador de Curriculo</title>
<style>
  
  body{margin:0;font-family:system-ui,Segoe UI,Arial,sans-serif;background:#fff;color:#222}
  .shell{max-width:820px;margin:28px auto;padding:0 14px}
  h1{margin:0 0 14px;text-align:center;font-size:22px;letter-spacing:.4px}
  .grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  @media (max-width:760px){.grid2{grid-template-columns:1fr}}
  .field{margin:8px 0}
  .label{font-weight:600;margin-bottom:6px;display:block}
  input,textarea{width:100%;padding:10px 12px;border:1px solid #cfd6e4;border-radius:6px;font-size:15px}
  textarea{min-height:80px;resize:vertical}
  .muted{color:#5b6b7a;font-size:13px}
  .exp{border:1px dashed #cfd6e4;border-radius:8px;padding:10px;margin:10px 0}
  .row{display:flex;gap:10px;flex-wrap:wrap}
  .row>*{flex:1 1 220px}
  .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:14px}
  .btn{padding:10px 14px;border-radius:8px;border:1px solid #cfd6e4;background:#f8fafc;cursor:pointer;font-weight:600}
  .btn.primary{background:#111;color:#fff;border-color:#111}
  .btn.remove{color:#b00020;border-color:#f3c2c2;background:#fff}
</style>
</head>
<body>
  <div class="shell">
    <h1>Gerador de Curriculo</h1>
    
    <form method="post">
      <div class="field">
        <span class="label">Nome</span>
        <input required name="nome" placeholder="Seu nome completo">
      </div>

      <div class="field">
        <span class="label">Endereço</span>
        <input name="endereco" placeholder="Rua, número, complemento">
      </div>

      <div class="row">
        <div class="field">
          <span class="label">Cidade</span>
          <input name="cidade" placeholder="Cidade – Estado">
        </div>
        <div class="field">
          <span class="label">Telefone</span>
          <input name="telefone" placeholder="(xx) 9 9999-9999">
        </div>
        <div class="field">
          <span class="label">E-mail</span>
          <input type="email" name="email" placeholder="voce@provedor.com.br">
        </div>
      </div>

      <div class="field">
        <span class="label">Objetivo Profissional</span>
        <textarea name="objetivo" placeholder="Ex.: Crescimento profissional na area de..."></textarea>
      </div>

      <div class="field">
        <span class="label">Formação Acadêmica</span>
        <textarea name="formacao" placeholder="Ex.: Ensino Medio — Nome da escola; data.&#10;Nivel superior — Curso, Universidade; data."></textarea>
      </div>

      <div class="field">
        <span class="label">Resumo das Qualificações</span>
        <textarea name="qualif" placeholder="Escreva suas qualificacoes (uma por linha)."></textarea>
      </div>

      <div class="field">
        <span class="label">Conhecimentos</span>
        <textarea name="conhec" placeholder="- Avancado em: ...&#10;- Intermediario em: ..."></textarea>
      </div>

      <div class="field">
        <span class="label">Línguas</span>
        <textarea name="idiomas" placeholder="Ingles — leitura, escrita, conversacao.&#10;Espanhol — leitura, escrita, conversacao."></textarea>
      </div>

      <div class="field">
        <span class="label">Experiência Profissional</span>
        <div id="exps"></div>
        <div class="actions" style="justify-content:flex-start">
          <button class="btn" type="button" id="add">+ Adicionar experiência</button>
        </div>
      </div>

      <div class="actions">
        <button class="btn" type="reset">Limpar</button>
        <button class="btn primary" type="submit">Gerar PDF</button>
      </div>
    </form>
  </div>

<script>
const exps = document.getElementById('exps');
const addBtn = document.getElementById('add');

function bloco(){
  const d = document.createElement('div');
  d.className = 'exp';
  d.innerHTML = `
    <div class="row">
      <div class="field"><span class="label">Empresa</span><input name="empresa[]" placeholder="Empresa X"></div>
      <div class="field"><span class="label">Cidade/UF</span><input name="local[]" placeholder="Cidade – Estado"></div>
      <div class="field"><span class="label">Função</span><input name="funcao[]" placeholder="Cargo/Função"></div>
    </div>
    <div class="row">
      <div class="field"><span class="label">Período</span><input name="periodo[]" placeholder="Ex.: 2019 a 2025"></div>
    </div>
    <div class="field"><span class="label">Atividades</span><textarea name="atividades[]" placeholder="Descreva suas atividades e resultados."></textarea></div>
    <div class="actions" style="justify-content:flex-start"><button type="button" class="btn remove">Remover</button></div>
  `;
  d.querySelector('.remove').onclick = ()=> d.remove();
  return d;
}
addBtn.onclick = ()=> exps.appendChild(bloco());
exps.appendChild(bloco()); // 1 bloco inicial
</script>
</body>
</html>
