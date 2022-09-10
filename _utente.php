<?php
require_once('funzioni/funzioni.php');
$_pagina=new Pagina();
$username='username';
$password=generaStringaCriptataConHash('password');
$password_hash=$password['hash'];
$password_salt=$password['salt'];
$email='email@dominio.it';
$nome='Nome';
$cognome='Cognome';
$q="INSERT INTO utenti(username,password_hash,password_salt,email,nome,cognome) VALUES('{$username}','{$password_hash}','{$password_salt}','{$email}','{$nome}','{$cognome}');";

// if(!$r=pg_query($_database->connection,$q)) {
//  echo 'Errore inserimento';
// } else {
//  echo 'Inserito utente';
// }
?>
