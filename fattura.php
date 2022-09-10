<?php
require_once 'funzioni/funzioni.php';
require_once 'funzioni/controlli.php';
require_once 'funzioni/classi/fatture.php';
require_once 'funzioni/classi/clienti.php';
require_once 'funzioni/classi/progetti.php';
require_once 'funzioni/classi/notifiche.php';

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina=new Pagina('Fattura ' . $_fattura->titolo);
$_pagina->jsOnLoad .= "$('.chosen').chosen();";

$azione='crea';
if (isset($_GET) and isset($_GET['azione'])) {
    if ($_GET['azione']=='modifica') {
        $azione='modifica';
    }
}

$_fattura=new FatturaProgetto();

if (isset($_REQUEST['id']) and $_REQUEST['id']) {
    $_fattura->ottieniDaId((int)$_REQUEST['id']);
}

if ($_fattura->getId() and $azione!='modifica') {
    $azione='visualizza';
}

$_clienti=new Clienti();
$_clienti->ottieniListaOpzioni('id, nome');

$_tipologieFatture = [1 => 'Fattura di vendita', 2 => 'Fattura di acquisto'];

$progetti = new Progetti();
$progetti->ordine = 'abil DESC, nome ASC';
$progetti->ottieniRecordTutti();
$progetti->ottieniListaOpzioni();

foreach ($progetti->listaOpzioni as $id => $progetto) {
    if (!$progetti->lista[$id]->isActive()) {
        $progetti->listaOpzioni[$id] = '(ARCHIVIATO) ' . $progetto;
    }
}

if ($azione=='modifica' or $azione=='crea') {
    $form=new Campi(FORM_MODIFICA_CLIENTE);
    $form->campi['codice']=new Campo('codice', 'Codice Fattura', TIPO_STRINGA, array('obbligatorio'=>false));
    $form->campi['tipologia']=new Campo('tipologia', 'Tipologia', TIPO_INTERO, array('obbligatorio'=>true, 'valori' => $_tipologieFatture, 'default' => 1, 'class' => 'chosen'));
    $form->campi['titolo']=new Campo('titolo', 'Titolo', TIPO_STRINGA, array('obbligatorio'=>true));
    $form->campi['descrizione']=new Campo('descrizione', 'Descrizione', TIPO_STRINGA, array());
    $form->campi['importo']=new Campo('importo', 'Importo', TIPO_REALE, array('obbligatorio'=>true));
    $form->campi['progetto']=new Campo('progetto', 'Progetto', TIPO_INTERO, array('obbligatorio'=>true, 'valori' => $progetti->listaOpzioni, 'class' => 'chosen'));
    $form->campi['cliente']=new Campo('cliente', 'Cliente', TIPO_INTERO, array('obbligatorio'=>true, 'valori' => $_clienti->listaOpzioni, 'class' => 'chosen'));
    $form->campi['data_emissione']=new Campo('data_emissione', 'Data di emissione', TIPO_DATA, array('obbligatorio'=>true));
    $form->campi['data_scadenza']=new Campo('data_scadenza', 'Data di scadenza', TIPO_DATA, array('obbligatorio'=>false));
    $form->campi['data_pagamento']=new Campo('data_pagamento', 'Data del pagamento', TIPO_DATA, array('obbligatorio'=>false));
    $form->campi['metodo_pagamento']=new Campo('metodo_pagamento', 'Metodo di pagamento', TIPO_STRINGA, array('obbligatorio'=>false));
    $form->campi['pagata']=new Campo('pagata', 'Pagata', TIPO_BOOLEANO);

    if (isset($_GET['progetto']) and $progetto = $_GET['progetto']) {
        if (isset($progetti->listaOpzioni[$progetto])) {
            $form->campi['progetto']->default = $progetto;
        }
    }
}

if (isset($_POST['crea'])) {
    if (!$form->controllaValori()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione della fattura.');
    } else {
        if (!$id = $_fattura->crea($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione della fattura.');
        } else {
            $_pagina->messaggi[]=new MessaggioConferma('Fattura creata');
            $_fattura=new FatturaProgetto($id);

            $notificationText = 'È stata inserita <a href="/fattura.php?id=' . $_fattura->getId() . '">una nuova fattura</a> per il progetto' . htmlspecialchars($_fattura->progetto->getName()) . '</a>';
            Notifiche::addNotificationForUser($notificationText, 15961);
        }
    }
} elseif (isset($_POST['salva'])) {
    if (!$form->controllaValori()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento della fattura.');
    } else {
        if (!$_fattura->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento della fattura.');
        } else {
            $_fattura=new FatturaProgetto($_fattura->getId());
            $_pagina->title='Fattura ' . $_fattura->titolo;
            $_pagina->messaggi[]=new MessaggioConferma('Fattura aggiornata');
        }
    }
} elseif (isset($_POST['elimina'])) {
    if (!$_fattura->elimina()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'eliminazione della fattura.');
    } else {
        $_pagina->messaggi[]=new MessaggioConferma('Fattura eliminata.');
        $_fattura=new FatturaProgetto();
    }
}

$_pagina->creaTesta();
?>
<section class="container pagina-fattura">
  <div class="sezione sezione-fattura">
  <?php if ($azione=='visualizza'):?>
   <h2>Fattura: <?=$_fattura->titolo?> <span class="small">(<?=$_fattura->codice?>)</span> <a class="icona icona-modifica" href="<?=$_SERVER['PHP_SELF']?>?id=<?=$_fattura->getId()?>&amp;azione=modifica"><img src='/img/svg/edit.svg' /></a></h2>
   <div id="visualizzazione-dati" class="row">
    <?=$_fattura->buildHtml()?>
   </div>
  <?php elseif ($azione=='modifica' or $azione=='crea'): ?>
   <h2><?=($azione=='modifica'?'Modifica della fattura '.$_fattura->titolo:'Creazione nuova fattura')?></h2>
   <?php
   $parametriAction=[];
   if ($azione == 'modifica') {
       $parametriAction['id'] = $_fattura->getId();
   }
   $parametriAction['azione']=$azione;
   $action=creaHttpQueryDaParametri($parametriAction);
   ?>
   <form method="post" action="<?=$action?>" class="form-standard">
       <fieldset>
           <legend>Informazioni economiche</legend>
           <?php
           $form->creaCampoDivCustom('importo', CAMPO_INPUTTEXT, 'col-m-4', $_fattura->getRecord());
           $form->creaCampoDivCustom('progetto', CAMPO_SELECT, 'col-m-4', $_fattura->getRecord(), 'Selezionare');
           $form->creaCampoDivCustom('tipologia', CAMPO_SELECT, 'col-m-4', $_fattura->getRecord(), 'Selezionare');
           ?>
       </fieldset>
       <fieldset>
           <legend>Informazioni di fatturazione</legend>
           <?php
           $form->creaCampoDivCustom('titolo', CAMPO_INPUTTEXT, 'col-m-4', $_fattura->getRecord());
           $form->creaCampoDivCustom('cliente', CAMPO_SELECT, 'col-m-4', $_fattura->getRecord(), 'Selezionare');
           $form->creaCampoDivCustom('data_emissione', CAMPO_INPUTTEXT, 'col-m-4', $_fattura->getRecord());
           $form->creaCampoDivCustom('codice', CAMPO_INPUTTEXT, 'col-m-4', $_fattura->getRecord());
           $form->creaCampoDivCustom('data_scadenza', CAMPO_INPUTTEXT, 'col-m-4', $_fattura->getRecord());
           $form->creaCampoDivCustom('descrizione', CAMPO_TEXTAREA, 'col-m-12', $_fattura->getRecord());
           ?>
       </fieldset>
       <fieldset>
           <legend>Informazioni sul pagamento</legend>
           <?php
           $form->creaCampoDivCustom('data_pagamento', CAMPO_INPUTTEXT, 'col-m-4', $_fattura->getRecord());
           $form->creaCampoDivCustom('metodo_pagamento', CAMPO_INPUTTEXT, 'col-m-4', $_fattura->getRecord());
           $form->creaCampoDivCustom('pagata', CAMPO_RADIOSINO, 'col-m-4', $_fattura->getRecord());
           ?>
       </fieldset>
   <input type="hidden" name="formID" value="<?=$form->formID?>" />
    <div class="campi-bottoni">
     <a href="<?=$_SERVER['PHP_SELF'].($_fattura->getId()?'?id='.$_fattura->getId():'')?>" class="btn btn-annulla">Annulla modifiche</a>
     <input type="submit" name="<?=$_fattura->getId()?'salva':'crea'?>" value="Salva" class="btn btn-invia" />
     <?php if ($_fattura->getId()): ?>
     <input type="submit" name="elimina" value="Elimina" class="btn btn-elimina" onClick="javascript:return confirm('Sei sicuro di voler eliminare questa fattura?');" />
     <a href="<?=$_SERVER['PHP_SELF']?>?azione=crea" class="btn btn-nuovo">Nuova fattura</a>
    <?php endif;?>
   </div>
  </form>
 <?php endif;?>
 </div>
</section>
<?php
$_pagina->creaFooter();
