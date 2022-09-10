<?php
require_once 'funzioni/funzioni.php';
require_once 'funzioni/controlli.php';

require_once 'funzioni/classi/clienti.php';
require_once 'funzioni/classi/registrar.php';
require_once 'funzioni/classi/domini.php';

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$azione='crea';

if (isset($_GET) and isset($_GET['azione'])) {
    if ($_GET['azione']=='modifica') {
        $azione='modifica';
    }
}

$_dominio=new Dominio();

if (isset($_REQUEST['id']) and $_REQUEST['id']) {
    $_dominio=new Dominio((int)$_REQUEST['id']);
    $_dominio->associaCaselleEmail();
}

if ($_dominio->getId() and $azione!='modifica') {
    $azione='visualizza';
}

$_clienti=new Clienti();
$_clienti->ottieniListaOpzioni();

$cliente_selezionato=0;
if (isset($_GET['cliente']) and $_GET['cliente']) {
    $cliente_selezionato=(int)$_GET['cliente'];
}

$nameDefault = '';
if (isset($_GET['name']) and $_GET['name']) {
    $nameDefault = htmlspecialchars($_GET['name']);
}

if ($azione=='modifica' or $azione=='crea') {
    $_registrar=new Registrars;
    $_registrar->ottieniListaOpzioni();
    $form=new Campi(FORM_MODIFICA_DOMINIO);
    $form->campi['name']=new Campo('name', 'Nome dominio', TIPO_STRINGA, array('obbligatorio'=>true, 'default' => $nameDefault));
    $form->campi['registrar']=new Campo('registrar', 'Registrar', TIPO_INTERO, array('valori'=>$_registrar->listaOpzioni, 'obbligatorio'=>true));
    $form->campi['datareg']=new Campo('datareg', 'Data di registrazione', TIPO_DATA, array('obbligatorio'=>true));
    $form->campi['cliente']=new Campo('cliente', 'Cliente associato al dominio', TIPO_INTERO, array('valori'=>$_clienti->listaOpzioni,'default'=>$cliente_selezionato, 'obbligatorio'=>true));
    $form->campi['notecliente']=new Campo('notecliente', 'Note cliente', TIPO_STRINGA, array());
    $form->campi['aggiornamento']=new Campo('aggiornamento', 'Ultimo aggiornamento', TIPO_STRINGA, array());
    $form->campi['attivo']=new Campo('attivo', 'Attivo', TIPO_BOOLEANO, array('valori' => ['t' => 'Si', 'f' => 'No'], 'default' => $_dominio->isActive() ? 't' : 'f'));
    $form->campi['data_chiusura']=new Campo('data_chiusura', 'Data di chiusura', TIPO_DATA, array('aiuto' => 'Se specificata, il campo "Attivo" viene forzato automaticamente a "No". Rimuovere la data e poi riattivare il dominio.'));
}
$_pagina=new Pagina('Dominio '.$_dominio->nome);

if (isset($_POST['crea'])) {
    $trovatiNomiDominioUguali=$_dominio->controllaSeNomeDominioEsiste(
        $_database->escapeLiteral($form->campi['name']->valore)
    );
    if (!$trovatiNomiDominioUguali) {
        if (!$id=$_database->generaNuovoIDTabella($_dominio->getDbTable())) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del dominio. ID non settato.');
        } else {
            if (!$form->controllaValori()) {
                $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del dominio.');
            } else {
                $form->valoriDB['aggiornamento']="'now'";
                if (!$_dominio->crea($form->valoriDB)) {
                    $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del dominio.');
                } else {
                    $_pagina->messaggi[]=new MessaggioConferma('Dominio creato');
                    $_dominio=new Dominio($id);
                }
            }
        }
    }
} elseif (isset($_POST['salva'])) {
    if ($form->valoreAttuale('data_chiusura') != '') {
        $form->campi['attivo']->valore = 'f';
    }
    if (!$form->controllaValori()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del dominio.');
    } else {
        $form->valoriDB['aggiornamento']="'now'";
        if (!$_dominio->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del dominio.');
        } else {
            $_dominio=new Dominio($_dominio->getId());
            if ($_dominio->cliente and $_dominio->cliente->getId()) {
                $_dominio->cliente=new AnagraficaCliente($_dominio->cliente->getId());
            }
            $_pagina->title='Dominio '.htmlspecialchars($_dominio->nome);
            $_pagina->messaggi[]=new MessaggioConferma('Dominio aggiornato');
        }
    }
} elseif (isset($_POST['elimina'])) {
    if (!$_dominio->elimina()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'eliminazione del dominio.');
    } else {
        $_pagina->messaggi[]=new MessaggioConferma('Dominio eliminato.');
        $_dominio=new Dominio();
    }
}

$_pagina->creaTesta();
?>
<section class="container pagina-dominio">
 <?php
 if ($azione=='visualizza'):
  ?>
  <h2>Dominio: <?=$_dominio->nome?></h2>
  <div id="visualizzazione-dati" class="clearfix">
   <div class="col-l-4">
    <h4>Registrar</h4>
    <p><?=$_dominio->registrar->nome?></p>
   </div>
   <div class="col-l-4">
    <h4>Data di registrazione</h4>
    <p><?=$_dominio->datareg?></p>
   </div>
   <?php if ($_dominio->cliente):?>
   <div class="col-l-4">
           <h4>Cliente associato al dominio</h4>
           <p><a href="cliente.php?id=<?=($_dominio->cliente->getId())?>"><?=$_dominio->cliente->nomeAzienda?></a></p>
   </div>
    <?php endif?>

    <div class="col-l-4">
        <h4>Stato</h4>
        <p><?=($_dominio->isActive() ? 'Attivo' : 'Chiuso')?></p>
    </div>

    <?php if ($_dominio->dataChiusura):?>
    <div class="col-l-4">
        <h4>Data di chiusura</h4>
        <p><?=htmlspecialchars($_dominio->dataChiusura)?></p>
    </div>
    <?php endif?>

    <?php if ($_dominio->note):?>
    <div class="col-l-4">
        <h4>Note aggiuntive</h4>
        <p><?=htmlspecialchars($_dominio->note)?></p>
    </div>
    <?php endif?>

    <div class="col-l-4">
        <h4>Data inserimento</h4>
         <p><?=$_dominio->getDateCreated()?></p>
    </div>

    <?php if ($_dominio->getDateModified()):?>
        <div class="col-l-4">
            <h4>Data modifica</h4>
            <p><?=$_dominio->getDateModified()?></p>
        </div>
    <?php endif?>
  </div>
  <a class="btn btn-modifica" href="<?=$_SERVER['PHP_SELF']?>?id=<?=$_dominio->getId()?>&amp;azione=modifica">Modifica dominio</a>
   <?php
 endif;
 if ($_dominio->cliente):
  ?>
  <div class="sezione sezione-cliente">
   <h2>Cliente associato: <a href="cliente.php?id=<?=$_dominio->cliente->getId()?>" class="azione-modifica"><?=$_dominio->cliente->nomeAzienda?> <abbr title="Modifica cliente"></abbr></a></h3>
  </div>
  <?php
 endif;

 if ($azione=='modifica' or $azione=='crea'): ?>
  <h2><?=($azione=='modifica'?'Modifica del dominio '.$_dominio->nome:'Creazione nuovo dominio')?></h2>
  <?php
  $parametriAction=[];
  $parametriAction['id']=$_dominio->getId();
  $parametriAction['azione']=$azione;
  $action=creaHttpQueryDaParametri($parametriAction);
  ?>
  <form method="post" action="<?=$action?>" class="form-standard">
  <div class="grid">
      <?php
      $form->creaCampoDivCustom('name', CAMPO_INPUTTEXT, 'grid-col', $_dominio->getRecord());
      $form->creaCampoDivCustom('registrar', CAMPO_SELECT, 'grid-col', $_dominio->getRecord(), 'Selezionare');
      $form->creaCampoDivCustom('datareg', CAMPO_INPUTTEXT, 'grid-col', $_dominio->getRecord());
      $form->creaCampoDivCustom('cliente', CAMPO_SELECT, 'grid-col', $_dominio->getRecord(), 'Selezionare');
      $form->creaCampoDivCustom('attivo', CAMPO_RADIOSINO, 'grid-col', NULL, 't', 'f');
      $form->creaCampoDivCustom('data_chiusura', CAMPO_INPUTTEXT, 'grid-col', $_dominio->getRecord());
      ?>
  </div>

  <?php
  $form->creaCampoDivCustom('notecliente', CAMPO_TEXTAREA, 'grid-col', $_dominio->getRecord());
  ?>

  <input type="hidden" name="formID" value="<?=$form->formID?>" />
  <div class="campi-bottoni">
  <a href="<?=$_SERVER['PHP_SELF'].($_dominio->getId()?'?id='.$_dominio->getId():'')?>" class="btn btn-annulla">Annulla modifiche</a>
  <input type="submit" name="<?=$_dominio->getId()?'salva':'crea'?>" value="Salva" class="btn btn-save" />
  <?php if ($_dominio->getId()): ?>
  <input type="submit" name="elimina" value="Elimina" class="btn btn-delete" onClick="javascript:return confirm('Sei sicuro di voler eliminare questo dominio?');" />
  <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-nuovo">Nuovo dominio</a>
 </div>
 <?php endif; ?>
 </form>
<?php endif;?>
</section>
<?php
$_pagina->creaFooter();
