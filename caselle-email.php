<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');
require_once('funzioni/classi/clienti.php');
require_once('funzioni/classi/provider-email.php');
require_once('funzioni/classi/caselle-email.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina=new Pagina('Elenco caselle email');

$_caselleEmail=new CaselleEmail;
$_caselleEmail->ottieniRisultatiFiltrati();

$_providers=new ProvidersEmail();
$_providers->ottieniListaOpzioni();

if (count($_caselleEmail)==0) {
    $_pagina->creaTesta();
    echo 'Nessuna Casella email trovata';
    $_pagina->creaFooter();
    exit();
}

$form=new Campi(FORM_FILTRA_CASELLEEMAIL);
$form->campi['email']=new Campo('email', 'Email', TIPO_STRINGA);
$form->campi['provider']=new Campo('provider', 'Provider', TIPO_INTERO, array('valori'=>$_providers->listaOpzioni));
$form->campi['risultatiPerPagina']=new Campo('risultatiPerPagina', 'Risultati per pagina', TIPO_INTERO, array('valori'=>$_config['risultatiPerPaginaAmmessi']));
$form->campi['attivo']=new Campo('attivo', 'Attivo', TIPO_STRINGA, array('valori'=>['t' => 'Si', 'f' => 'No']));

if (isset($_REQUEST['email']) and $_REQUEST['email']) {
    $form->campi['email']->default=$_REQUEST['email'];
}

if (isset($_REQUEST['provider']) and $_REQUEST['provider']) {
    $form->campi['provider']->default=(int)$_REQUEST['provider'];
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
        <h2>Filtra Caselle Email per:</h2>
        <?php
        $form->creaCampoDiv('email', CAMPO_INPUTTEXT);
        $form->creaCampoDiv('provider', CAMPO_SELECT, null, 'Selezionare');
        $form->creaCampoDiv('risultatiPerPagina', CAMPO_SELECT);
        $form->creaCampoDiv('attivo', CAMPO_SELECT, null, 'Tutti');
        ?>
        <input type="hidden" value="<?=$form->formID?>" name="formID" />
        <div class="campi-bottoni">
            <input type="submit" name="filtra" value="Cerca" class="btn btn-search" />
            <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-annulla">Azzera</a>
            <a href="casella-email.php?azione=crea" class="btn btn-nuovo">Nuova Casella Email</a>
        </div>
    </form>
</div>
<?php
echo $_caselleEmail->buildHtml();
$_pagina->creaFooter();
