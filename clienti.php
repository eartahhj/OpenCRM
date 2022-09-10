<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');
require_once('funzioni/classi/clienti.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina=new Pagina('Elenco clienti');

$_clienti=new Clienti();
$_clienti->ottieniRisultatiFiltrati();

$form=new Campi(FORM_FILTRA_CLIENTI);
$form->campi['nome']=new Campo('nome', 'Cliente', TIPO_STRINGA);
$form->campi['risultatiPerPagina']=new Campo('risultatiPerPagina', 'Risultati per pagina', TIPO_INTERO, array('valori'=>$_config['risultatiPerPaginaAmmessi']));
$form->campi['stato']=new Campo('stato', 'Stato del cliente', TIPO_INTERO, array('valori'=>$_config['stato']));

if (isset($_REQUEST['nome']) and $_REQUEST['nome']) {
    $form->campi['nome']->default=$_REQUEST['nome'];
}
if (isset($_REQUEST['risultatiPerPagina']) and $_REQUEST['risultatiPerPagina']) {
    $form->campi['risultatiPerPagina']->default=(int)$_REQUEST['risultatiPerPagina'];
}
if (isset($_REQUEST['stato']) and $_REQUEST['stato']!=='') {
    $form->campi['stato']->default=(int)$_REQUEST['stato'];
} else {
    $form->campi['stato']->default='';
}

$_pagina->creaTesta();
?>
<div class="container">
    <form action="<?=$_SERVER['PHP_SELF']?>" method="get" class="container form-inline">
        <h2>Filtra i clienti per:</h2>
        <?php
        $form->creaCampoDiv('nome', CAMPO_INPUTTEXT);
        $form->creaCampoDiv('stato', CAMPO_SELECT, null, 'Selezionare');
        $form->creaCampoDiv('risultatiPerPagina', CAMPO_SELECT);
        ?>
        <input type="hidden" value="<?=$form->formID?>" name="formID" />
        <div class="campi-bottoni">
            <input type="submit" name="filtra" value="Cerca" class="btn btn-search" />
            <a href="<?=$_SERVER['PHP_SELF']?>?stato=-1" class="btn btn-annulla">Azzera</a>
            <a href="cliente.php" class="btn btn-nuovo">Nuovo cliente</a>
        </div>
    </form>
</div>
<?php
echo $_clienti->buildHtml();
$_pagina->creaFooter();
