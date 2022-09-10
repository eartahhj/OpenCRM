<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina=new Pagina('Modifica profilo', PAGINA_RISERVATA_UTENTE);

$form=new Campi(FORM_UTENTE_MODIFICAPROFILO);
$form->campi['password']=new Campo('password', 'Password', TIPO_STRINGA, array('obbligatorio'=>false));
$form->campi['email']=new Campo('email', 'Email', TIPO_EMAIL, array('obbligatorio'=>true));
$form->campi['nome']=new Campo('nome', 'Nome', TIPO_STRINGA, array('obbligatorio'=>true));
$form->campi['cognome']=new Campo('cognome', 'Cognome', TIPO_STRINGA, array('obbligatorio'=>true));

if (isset($_POST['salva']) and $_POST['formID']==$form->formID) {
    if (!$form->controllaValori()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del profilo.');
    } else {
        $messaggioErrore='';
        if ($nuovaPassword=$form->valoreAttuale('password')) {
            if (!$messaggioErrore=controllaRequisitiPassword($nuovaPassword)) {
                $nuovaPasswordCriptata=cryptString($nuovaPassword, '', true);
                $form->valoriDB['password_hash']=pg_escape_literal($nuovaPasswordCriptata['hash']);
                $form->valoriDB['password_salt']=pg_escape_literal($nuovaPasswordCriptata['salt']);
            } else {
                $_pagina->messaggi[]=new MessaggioErrore($messaggioErrore);
            }
        }
        $form->valoriDB['data_modifica']="'now'";
        if (isset($form->valoriDB['password'])) {
            unset($form->valoriDB['password']);
        }
        if ($messaggioErrore or !$_utente->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del profilo.');
        } else {
            $_pagina->messaggi[]=new MessaggioConferma('Profilo aggiornato');
            $_utente = new Utente($_utente->getId());
        }
    }
}

$_pagina->creaTesta();
?>
<div class="container">
<h2>Modifica del profilo di <?=$_utente->getUsername()?></h2>
<form method="post" action="<?=$action?>" class="form-standard">
    <?php
    $form->creaCampoDIV('password', CAMPO_INPUTPASSWORD);
    $form->creaCampoDIV('email', CAMPO_INPUTTEXT, $_utente->getRecord());
    $form->creaCampoDIV('nome', CAMPO_INPUTTEXT, $_utente->getRecord());
    $form->creaCampoDIV('cognome', CAMPO_INPUTTEXT, $_utente->getRecord());
    ?>
    <input type="hidden" name="formID" value="<?=$form->formID?>" />
    <div class="campi-bottoni">
      <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-annulla">Annulla modifiche</a>
      <input type="submit" name="salva" value="Salva" class="btn btn-invia" />
    </div>
</form>
</div>
<?php
$_pagina->creaFooter();
