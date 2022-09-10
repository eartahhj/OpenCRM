<?php
require_once('../funzioni/funzioni.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina=new Pagina('Amministrazione Area Interna',PAGINA_RISERVATA_ADMIN);
$_pagina->creaTesta();
?>

<?php
$_pagina->creaFooter();
