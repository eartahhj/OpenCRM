<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');
require_once('funzioni/classi/clienti.php');
require_once('funzioni/classi/registrar.php');
require_once('funzioni/classi/domini.php');
require_once('funzioni/classi/provider-email.php');
require_once('funzioni/classi/caselle-email.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$azione='crea';
if (isset($_GET) and isset($_GET['azione'])) {
    if ($_GET['azione']=='modifica') {
        $azione='modifica';
    }
}

$cliente_selezionato=0;
$emailDefault = '';
$providerDefault = 0;

if (isset($_GET) and $_GET['cliente']) {
    $cliente_selezionato=(int)$_GET['cliente'];
}

if (isset($_GET['email']) and $_GET['email']) {
    $emailDefault = htmlspecialchars($_GET['email']);
}

if (isset($_GET['provider']) and $_GET['provider']) {
    $providerDefault = (int)$_GET['provider'];
}

if (isset($_REQUEST['id']) and $_REQUEST['id']) {
    $_casellaEmail=new CasellaEmail((int)$_REQUEST['id']);
} else {
    $_casellaEmail=new CasellaEmail();
}

if ($_casellaEmail->getId() and $azione!='modifica') {
    $azione='visualizza';
}

if ($azione=='modifica' or $azione=='crea') {
    $_clienti=new Clienti();
    $_provider=new ProvidersEmail();
    $_clienti->ottieniListaOpzioni();
    $_provider->ottieniListaOpzioni();
    $form=new Campi(FORM_MODIFICA_CASELLAEMAIL);
    $form->campi['email']=new Campo('email', 'Email', TIPO_STRINGA, array('obbligatorio'=>true, 'default' => ($emailDefault ? $emailDefault : '')));
    $form->campi['provider']=new Campo('provider', 'Provider', TIPO_INTERO, array('valori'=>$_provider->listaOpzioni, 'default' => ($providerDefault ? $providerDefault : '')));
    $form->campi['cliente']=new Campo('cliente', 'Cliente associato alla casella email', TIPO_INTERO, array('valori'=>$_clienti->listaOpzioni,'default'=>$cliente_selezionato));
    $form->campi['alias_of']=new Campo('alias_of', 'Alias', TIPO_STRINGA, array());
    $form->campi['attivo']=new Campo('attivo', 'Attivo', TIPO_BOOLEANO);
    $form->campi['aggiornamento']=new Campo('aggiornamento', 'Ultimo aggiornamento', TIPO_STRINGA, array());
    $form->campi['note']=new Campo('note', 'Note', TIPO_STRINGA);
}

$_pagina=new Pagina('Casella Email '.$_casellaEmail->nome);

if (isset($_POST['crea'])) {
    if (!$id=$_database->generaNuovoIDTabella($_casellaEmail->getDbTable())) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione della casella email. ID non settato.');
    } else {
        if (!$form->controllaValori()) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione della casella email.');
        } else {
            $form->valoriDB['aggiornamento']="'now'";
            if (!$_casellaEmail->crea($form->valoriDB)) {
                $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione della casella email.');
            } else {
                $_pagina->messaggi[]=new MessaggioConferma('Casella Email creata');
                $_casellaEmail=new CasellaEmail($id);
            }
        }
    }
} elseif (isset($_POST['salva'])) {
    if (!$form->controllaValori()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento della casella email.');
    } else {
        $form->valoriDB['aggiornamento']="'now'";
        if (!$_casellaEmail->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento della casella email.');
        } else {
            $_casellaEmail=new CasellaEmail($_casellaEmail->getId());
            $_pagina->title='CasellaEmail '.$_casellaEmail->nome;
            $_pagina->messaggi[]=new MessaggioConferma('CasellaEmail aggiornato');
        }
    }
} elseif (isset($_POST['elimina'])) {
    if (!$_casellaEmail->elimina()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'eliminazione della casella email.');
    } else {
        $_pagina->messaggi[]=new MessaggioConferma('Casella email eliminata.');
        $_casellaEmail=new CasellaEmail();
    }
}

$_pagina->creaTesta();
?>
<section class="container pagina-casellaEmail">
 <?php if ($azione=='visualizza'):?>
    <h2>Casella Email: <?=$_casellaEmail->email?></h2>
    <a class="btn btn-modifica" href="<?=$_SERVER['PHP_SELF']?>?id=<?=$_casellaEmail->getId()?>&amp;azione=modifica">Modifica Casella Email</a>
    <div id="visualizzazione-dati" class="clearfix">
        <?php if($_casellaEmail->cliente):?>
        <div class="col-l-4">
         <h4>Cliente associato</h4>
         <p><a href="cliente.php?id=<?=$_casellaEmail->cliente->getId()?>"><?=$_casellaEmail->cliente->nomeAzienda?></a></p>
        </div>
        <?php endif?>
        <?php if($_casellaEmail->dominio->nome):?>
        <div class="col-l-4">
         <h4>Dominio associato</h4>
         <p><a href="dominio.php?id=<?=$_casellaEmail->dominio->getId()?>"><?=$_casellaEmail->dominio->nome?></a></p>
        </div>
        <?php endif; ?>
        <div class="col-l-4">
         <h4>Provider</h4>
         <p><?=$_casellaEmail->provider->nome?></p>
        </div>
        <div class="col-l-4">
         <h4>Ultimo aggiornamento</h4>
         <p><?=$_casellaEmail->aggiornamento?></p>
        </div>
        <?php if ($_casellaEmail->note):?>
        <div class="col-l-4">
            <h4>Note</h4>
            <p><?=$_casellaEmail->note?></p>
        </div>
        <?php endif?>
   </div>
   <?php elseif ($azione=='modifica' or $azione=='crea'): ?>
   <h2><?=($azione=='modifica'?'Modifica della casella email '.$_casellaEmail->nome:'Creazione nuova Casella Email')?></h2>
   <?php
   $parametriAction=[];
   $parametriAction['id']=$_casellaEmail->getId();
   $parametriAction['azione']=$azione;
   $action=creaHttpQueryDaParametri($parametriAction);
   ?>
   <form method="post" action="<?=$action?>">
     <?php
     $form->creaCampoDIV('email', CAMPO_INPUTTEXT, $_casellaEmail->getRecord());
     $form->creaCampoDIV('cliente', CAMPO_SELECT, $_casellaEmail->getRecord(), 'Selezionare');
     $form->creaCampoDIV('alias_of', CAMPO_INPUTTEXT, $_casellaEmail->getRecord());
     $form->creaCampoDIV('provider', CAMPO_SELECT, $_casellaEmail->getRecord(), 'Selezionare');
     $form->creaCampoDIV('attivo', CAMPO_RADIOSINO, $_casellaEmail->getRecord(), 't', 'f');
     $form->creaCampoDIV('note', CAMPO_TEXTAREA, $_casellaEmail->getRecord());
     ?>
     <input type="hidden" name="formID" value="<?=$form->formID?>" />
     <div class="campi-bottoni">
       <a href="<?=$_SERVER['PHP_SELF'].($_casellaEmail->getId()?'?id='.$_casellaEmail->getId():'')?>" class="btn btn-annulla">Annulla modifiche</a>
       <input type="submit" name="<?=$_casellaEmail->getId()?'salva':'crea'?>" value="Salva" class="btn btn-save" />
    <?php if ($_casellaEmail->getId()): ?>
       <input type="submit" name="elimina" value="Elimina" class="btn btn-delete" onClick="javascript:return confirm('Sei sicuro di voler eliminare questa Casella Email?');" />
       <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-nuovo">Nuova Casella Email</a>
    <?php endif; ?>
   </div>
   </form>
   <?php endif; ?>
</section>
<?php
$_pagina->creaFooter();
