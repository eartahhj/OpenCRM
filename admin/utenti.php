<?php
require_once '../funzioni/funzioni.php';
require_once '../funzioni/controlli.php';
require_once '../funzioni/classi/utenti.php';

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina = new Pagina('Utenti', PAGINA_RISERVATA_ADMIN);

$utenti=new Utenti();
$utenti->ottieniRisultatiFiltrati();

$utente = null;
$utenteSelezionato = $_GET['id'] ?? 0;

$form=new Campi(FORM_FILTRA_UTENTI);
$form->campi['username']=new Campo('username', 'Username', TIPO_STRINGA, ['autocomplete' => 'off']);
$form->campi['risultatiPerPagina']=new Campo('risultatiPerPagina', 'Risultati per pagina', TIPO_INTERO, array('valori'=>$_config['risultatiPerPaginaAmmessi']));
$form->campi['attivo']=new Campo('attivo', 'Utente attivo', TIPO_INTERO, array('valori'=>['t'=>'Si','f'=>'No']));

$_pagina->creaTesta();
?>
<div class="container">
    <form id="form-utenti-filtra" action="<?=$_SERVER['PHP_SELF']?>" method="get" class="clearfix form-inline">
        <h2>Filtra utenti per:</h2>
        <div class="grid">
            <?php
            $form->creaCampoDiv('username', CAMPO_INPUTTEXT);
            $form->creaCampoDiv('attivo', CAMPO_SELECT, null, 'Selezionare');
            $form->creaCampoDiv('risultatiPerPagina', CAMPO_SELECT);
            ?>
        </div>
        <input type="hidden" value="<?=$form->formID?>" name="formID" />
        <div class="campi-bottoni">
            <input type="submit" name="filtra" value="Cerca" class="btn btn-search" />
            <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-annulla">Azzera</a>
            <a href="progetto.php?azione=crea" class="btn btn-nuovo">Nuovo utente</a>
        </div>
    </form>
</div>

<?php
echo $utenti->buildHtml();
?>

<?php
$_pagina->creaFooter();
?>
