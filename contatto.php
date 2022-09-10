<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');

require_once('funzioni/classi/clienti.php');
require_once('funzioni/classi/registrar.php');
require_once('funzioni/classi/domini.php');
require_once('funzioni/classi/contatti.php');

$_clienti=new Clienti();
$_clienti->ottieniListaOpzioni();

if (isset($_REQUEST) and $_REQUEST['id']) {
    $_contatto=new Contatto((int)$_REQUEST['id']);
} else {
    $_contatto=new Contatto();
}

$cliente_selezionato=0;
if (isset($_GET) and $_GET['cliente']) {
    $cliente_selezionato=(int)$_GET['cliente'];
}

$form=new Campi(FORM_MODIFICA_CONTATTO);
$form->campi['azienda']=new Campo('azienda', 'Azienda', TIPO_STRINGA, array());
$form->campi['nomecognome']=new Campo('nomecognome', 'Nome e cognome', TIPO_STRINGA, array('obbligatorio'=>true));
$form->campi['email']=new Campo('email', 'Email', TIPO_STRINGA, array());
$form->campi['telefono']=new Campo('telefono', 'Telefono', TIPO_STRINGA, array());
$form->campi['fax']=new Campo('fax', 'Fax', TIPO_STRINGA, array());
$form->campi['indirizzo']=new Campo('indirizzo', 'Indirizzo', TIPO_STRINGA, array());
$form->campi['localita']=new Campo('localita', 'Località', TIPO_STRINGA, array());
$form->campi['provincia']=new Campo('provincia', 'Provincia', TIPO_STRINGA, array());
$form->campi['cap']=new Campo('cap', 'CAP', TIPO_STRINGA, array());
$form->campi['note']=new Campo('note', 'Note', TIPO_STRINGA, array());
$form->campi['attivo']=new Campo('attivo', 'Attivo', TIPO_BOOLEANO, array());
$form->campi['cliente']=new Campo('cliente', 'Cliente associato', TIPO_INTERO, array('valori'=>$_clienti->listaOpzioni,'default'=>$cliente_selezionato));
$_pagina=new Pagina('Contatto '.$_contatto->nomeCognome);

if (isset($_POST['crea'])) {
    if (!$id=$_database->generaNuovoIDTabella($_contatto->getDbTable())) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del contatto. ID non settato.');
    } else {
        if (!$form->controllaValori()) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del contatto.');
        } else {
            if (!$_contatto->crea($form->valoriDB)) {
                $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del contatto.');
            } else {
                $_pagina->messaggi[]=new MessaggioConferma('Contatto creato');
                $_contatto=new Contatto($id);
            }
        }
    }
} elseif (isset($_POST['salva'])) { # Aggiornamento del Contatto
    if (!$form->controllaValori()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del dominio.');
    } else {
        if (!$_contatto->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del contatto.');
        } else {
            $_contatto=new Contatto($_contatto->getId());
            $_pagina->title='Contatto '.$_contatto->nomecognome;
            $_pagina->messaggi[]=new MessaggioConferma('Contatto aggiornato');
        }
    }
} elseif (isset($_POST['elimina'])) {
    if (!$_contatto->elimina()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'eliminazione del contatto.');
    } else {
        $_pagina->messaggi[]=new MessaggioConferma('Contatto eliminato.');
        $_contatto=new Contatto();
    }
}

$_pagina->creaTesta();
?>
<section class="container pagina-contatto">
  <div class="sezione sezione-contatto">
   <?php
   if ($_contatto->getId()):
   ?>
   <h2>Modifica del contatto <?=$_contatto->nomeCognome?></h2>
  <?php else: ?>
   <h2>Creazione nuovo contatto</h2>
  <?php endif; ?>
   <form method="post" action="<?=$_SERVER['PHP_SELF'].($_contatto->getId()?'?id='.$_contatto->getId():'')?>">
   <?php
   $form->creaCampoDIV('azienda', CAMPO_INPUTTEXT, $_contatto->getRecord());
   $form->creaCampoDIV('nomecognome', CAMPO_INPUTTEXT, $_contatto->getRecord());
   $form->creaCampoDIV('email', CAMPO_INPUTTEXT, $_contatto->getRecord());
   $form->creaCampoDIV('telefono', CAMPO_INPUTTEXT, $_contatto->getRecord());
   $form->creaCampoDIV('fax', CAMPO_INPUTTEXT, $_contatto->getRecord());
   $form->creaCampoDIV('indirizzo', CAMPO_INPUTTEXT, $_contatto->getRecord());
   $form->creaCampoDIV('localita', CAMPO_INPUTTEXT, $_contatto->getRecord());
   $form->creaCampoDIV('provincia', CAMPO_INPUTTEXT, $_contatto->getRecord());
   $form->creaCampoDIV('cap', CAMPO_INPUTTEXT, $_contatto->getRecord());
   $form->creaCampoDIV('note', CAMPO_INPUTTEXT, $_contatto->getRecord());
   $form->creaCampoDIV('attivo', CAMPO_RADIOSINO, $_contatto->getRecord(), 't');
   $form->creaCampoDIV('cliente', CAMPO_SELECT, $_contatto->getRecord(), 'Selezionare');
   ?>
   <input type="hidden" name="formID" value="<?=$form->formID?>" />
   <a href="<?=$_SERVER['PHP_SELF'].($_contatto->getId()?'?id='.$_contatto->getId():'')?>" class="btn btn-annulla">Annulla modifiche</a>
   <input type="submit" name="<?=$_contatto->getId()?'salva':'crea'?>" value="Salva" class="btn btn-invia" />
   <?php if ($_contatto->getId()): ?>
   <input type="submit" name="elimina" value="Elimina" class="btn btn-elimina" onClick="javascript:return confirm('Sei sicuro di voler eliminare questo contatto?');" />
   <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-nuovo">Nuovo contatto</a>
  <?php endif; ?>
  </form>
 </div>
</section>
<?php
$_pagina->creaFooter();
