<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');
require_once('funzioni/classi/provider-email.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$azione='crea';
if (isset($_GET) and isset($_GET['azione'])) {
    if ($_GET['azione']=='modifica') {
        $azione='modifica';
    }
}

if (isset($_REQUEST['id']) and $_REQUEST['id']) {
    $_provider=new ProviderEmail((int)$_REQUEST['id']);
} else {
    $_provider=new ProviderEmail();
}

if ($_provider->getId() and $azione!='modifica') {
    $azione='visualizza';
}

if ($azione=='modifica' or $azione=='crea') {
    $form=new Campi(FORM_MODIFICA_REGISTRAR);
    $form->campi['nome']=new Campo('nome', 'Nome provider', TIPO_STRINGA, array('obbligatorio'=>true));
    $form->campi['aggiornamento']=new Campo('aggiornamento', 'Ultimo aggiornamento', TIPO_STRINGA, array());
}

$_pagina=new Pagina('Provider '.$_provider->nome);

if (isset($_POST['crea'])) {
    if (!$id=$_database->generaNuovoIDTabella($_provider->getDbTable())) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del provider. ID non settato.');
    } else {
        if(!$form->controllaValori()) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del provider.');
        } else {
            $form->valoriDB['aggiornamento']="'now'";
            if (!$_provider->crea($form->valoriDB)) {
                $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del provider.');
            } else {
                $_pagina->messaggi[]=new MessaggioConferma('Provider creato');
                $_provider=new ProviderEmail($id);
            }
        }
    }
} elseif (isset($_POST['salva'])) {
    if(!$form->controllaValori()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del provider.');
    } else {
        $form->valoriDB['aggiornamento']="'now'";
        if (!$_provider->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del provider.');
        } else {
            $_provider=new ProviderEmail($_provider->getId());
            $_pagina->title='Provider '.$_provider->nome;
            $_pagina->messaggi[]=new MessaggioConferma('Provider aggiornato');
        }
    }
} elseif (isset($_POST['elimina'])) {
    if (!$_provider->elimina()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'eliminazione del provider.');
    } else {
        $_pagina->messaggi[]=new MessaggioConferma('Provider eliminato.');
        $_provider=new ProviderEmail();
    }
}

$_pagina->creaTesta();
?>
<section class="container pagina-provider">
 <?php if ($azione=='visualizza'):?>
    <h2>Provider: <?=$_provider->nome?></h2>
    <a class="btn btn-modifica" href="<?=$_SERVER['PHP_SELF']?>?id=<?=$_provider->getId()?>&amp;azione=modifica">Modifica provider</a>
    <div id="visualizzazione-dati" class="clearfix">
    <?=$_provider?>
   </div>
   <?php elseif ($azione=='modifica' or $azione=='crea'): ?>
   <h2><?=($azione=='modifica'?'Modifica del provider '.$_provider->nome:'Creazione nuovo provider')?></h2>
   <?php
   $parametriAction=[];
   $parametriAction['id']=$_provider->getId();
   $parametriAction['azione']=$azione;
   $action=creaHttpQueryDaParametri($parametriAction);
   ?>
   <form method="post" action="<?=$action?>">
     <?php
     $form->creaCampoDIV('nome', CAMPO_INPUTTEXT, $_provider->getRecord());
     ?>
     <input type="hidden" name="formID" value="<?=$form->formID?>" />
     <div class="campi-bottoni">
       <a href="<?=$_SERVER['PHP_SELF'].($_provider->getId()?'?id='.$_provider->getId():'')?>" class="btn btn-annulla">Annulla modifiche</a>
       <input type="submit" name="<?=$_provider->getId()?'salva':'crea'?>" value="Salva" class="btn btn-save" />
    <?php if ($_provider->getId()): ?>
       <input type="submit" name="elimina" value="Elimina" class="btn btn-delete" onClick="javascript:return confirm('Sei sicuro di voler eliminare questo provider?');" />
       <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-nuovo">Nuovo provider</a>
    <?php endif; ?>
   </div>
   </form>
   <?php endif; ?>
</section>
<?php
$_pagina->creaFooter();
