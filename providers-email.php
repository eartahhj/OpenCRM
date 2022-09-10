<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');
require_once('funzioni/classi/provider-email.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina=new Pagina('Elenco Providers');

$_providers=new ProvidersEmail;
$_providers->ottieniRisultatiFiltrati();

if(count($_providers)==0) {
 $_pagina->creaTesta();
 echo 'Nessun provider trovato';
 $_pagina->creaFooter();
 exit();
}
 $form=new Campi(FORM_FILTRA_REGISTRAR);
 $form->campi['nome']=new Campo('nome','Nome Provider',TIPO_STRINGA);
 $form->campi['rpp']=new Campo('rpp','Risultati per pagina',TIPO_INTERO,array('valori'=>$_config['risultatiPerPaginaAmmessi']));
 if(isset($_REQUEST['nome'])) $form->campi['nome']->default=$_REQUEST['nome'];
 if(isset($_REQUEST['rpp'])) $form->campi['rpp']->default=$_REQUEST['rpp'];

 $_pagina->creaTesta();
 ?>
 <div class="container">
 <form action="<?=$_SERVER['PHP_SELF']?>" method="get" class="clearfix form-inline">
  <h2>Filtra provider per:</h2>
  <?php
  $form->creaCampoDiv('nome',CAMPO_INPUTTEXT);
  $form->creaCampoDiv('rpp',CAMPO_SELECT);
  ?>
  <input type="hidden" value="<?=$form->formID?>" name="formID" />
  <div class="campi-bottoni">
   <input type="submit" name="filtra" value="Cerca" class="btn btn-search" />
   <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-annulla">Azzera</a>
   <a href="provider-email.php" class="btn btn-nuovo">Nuovo provider</a>
  </div>
 </form>
</div>
 <?php
 echo $_providers->buildHtml();

$_pagina->creaFooter();
