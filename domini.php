<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');

require_once('funzioni/classi/clienti.php');
require_once('funzioni/classi/registrar.php');
require_once('funzioni/classi/domini.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina=new Pagina('Elenco domini');

$_domini=new Domini;
$_domini->ottieniRisultatiFiltrati();

$_registrars=new Registrars;
$_registrars->ottieniListaOpzioni();

if (count($_domini)==0) {
    $_pagina->creaTesta();
    echo 'Nessun dominio trovato';
    $_pagina->creaFooter();
    exit();
}

$form=new Campi(FORM_FILTRA_DOMINI);
$form->campi['name']=new Campo('name', 'Dominio', TIPO_STRINGA);
$form->campi['registrar']=new Campo('registrar', 'Registrar', TIPO_INTERO, array('valori'=>$_registrars->listaOpzioni));
$form->campi['risultatiPerPagina']=new Campo('risultatiPerPagina', 'Risultati per pagina', TIPO_INTERO, array('valori'=>$_config['risultatiPerPaginaAmmessi']));
$form->campi['attivo']=new Campo('attivo', 'Attivo', TIPO_STRINGA, array('valori'=>['t' => 'Si', 'f' => 'No']));

if (isset($_REQUEST['name']) and $_REQUEST['name']) {
    $form->campi['name']->default=$_REQUEST['name'];
}

if (isset($_REQUEST['registrar']) and $_REQUEST['registrar']) {
    $form->campi['registrar']->default=(int)$_REQUEST['registrar'];
}

if (isset($_REQUEST['risultatiPerPagina']) and $_REQUEST['risultatiPerPagina']) {
    $form->campi['risultatiPerPagina']->default=(int)$_REQUEST['risultatiPerPagina'];
}

if (isset($_REQUEST['attivo']) and $_REQUEST['attivo']) {
    if ($_REQUEST['attivo'] == 't' or $_REQUEST['attivo'] ==  'f') {
        $form->campi['attivo']->default = $_REQUEST['attivo'];
    }
}

$_pagina->creaTesta();
?>
<div class="container">
    <form action="<?=$_SERVER['PHP_SELF']?>" method="get" class="clearfix form-inline">
        <h2>Filtra domini per:</h2>
        <?php
        $form->creaCampoDiv('name', CAMPO_INPUTTEXT);
        $form->creaCampoDiv('registrar', CAMPO_SELECT, null, 'Selezionare');
        $form->creaCampoDiv('risultatiPerPagina', CAMPO_SELECT);
        $form->creaCampoDiv('attivo', CAMPO_SELECT, null, 'Tutti');
        ?>
        <input type="hidden" value="<?=$form->formID?>" name="formID" />
        <div class="campi-bottoni">
            <input type="submit" name="filtra" value="Cerca" class="btn btn-search" />
            <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-annulla">Azzera</a>
            <a href="dominio.php?azione=crea" class="btn btn-nuovo">Nuovo dominio</a>
        </div>
    </form>
</div>
<?php
echo $_domini->buildHtml();
$_pagina->creaFooter();
