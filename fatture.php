<?php
require_once 'funzioni/funzioni.php';
require_once 'funzioni/controlli.php';
require_once 'funzioni/classi/fatture.php';
require_once 'funzioni/classi/clienti.php';
require_once 'funzioni/classi/progetti.php';

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina=new Pagina('Elenco fatture');

$fatture = new FattureProgetti();
$fatture->ottieniRisultatiFiltrati();

$clienti = new Clienti();
$clienti->ottieniRecordTutti();
$clienti->ottieniListaOpzioni();

$progetti = new Progetti();
$progetti->ottieniRecordTutti();
$progetti->ottieniListaOpzioni();

$form = new Campi(FORM_FILTRA_FATTURE);
$form->campi['progetto']=new Campo('progetto', 'Progetto', TIPO_INTERO, ['valori' => $progetti->listaOpzioni]);
$form->campi['cliente']=new Campo('cliente', 'Cliente', TIPO_INTERO, ['valori' => $clienti->listaOpzioni]);
$form->campi['risultatiPerPagina']=new Campo('risultatiPerPagina', 'Risultati per pagina', TIPO_INTERO, array('valori'=>$_config['risultatiPerPaginaAmmessi']));

$form->campi['progetto']->default = intval($_REQUEST['progetto'] ?? 0);
$form->campi['cliente']->default = intval($_REQUEST['cliente'] ?? 0);
$form->campi['risultatiPerPagina']->default = intval($_REQUEST['risultatiPerPagina'] ?? 0);

$_pagina->creaTesta();
?>
<div class="container">
    <form action="<?=$_SERVER['PHP_SELF']?>" method="get" class="container">
        <h2>Filtra le fatture per:</h2>
        <div class="filtra-container">
            <?php
            $form->creaCampoDivCustom('progetto', CAMPO_SELECT, 'col-m-3', null, 'Selezionare');
            $form->creaCampoDivCustom('cliente', CAMPO_SELECT, 'col-m-3', null, 'Selezionare');
            $form->creaCampoDivCustom('risultatiPerPagina', CAMPO_SELECT, 'col-m-2');
            ?>
            <div class="col-m-4">
                <div class="campi-bottoni">
                    <input type="submit" name="filtra" value="Cerca" class="btn btn-search" />
                    <a href="<?=$_SERVER['PHP_SELF']?>?stato=-1" class="btn btn-annulla">Azzera</a>
                    <a href="fattura.php" class="btn btn-nuovo">Nuova fattura</a>
                </div>
            </div>
        </div>
        <input type="hidden" value="<?=$form->formID?>" name="formID" />
    </form>
</div>

<?php
if (isset($_GET['progetto']) and $projectId = intval($_GET['progetto'])):
    if ($euro = FattureProgetti::getTotalEurosForProject($projectId)):?>
        <div class="container">
            <p>Totale fatture per questo progetto: &euro; <?=$euro?></p>
        </div>
    <?php
    endif;
endif;
?>

<?php
echo $fatture->buildHtml();
$_pagina->creaFooter();
