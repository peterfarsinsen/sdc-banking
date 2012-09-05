<?php
require_once('sdc-banking.php');

$bank = new SdcBanking('ssn', 'pin', 'activation conde', 'bank no.');
$agreements = $bank->login();
$bank->selectAgreement($agreements->agreements[0]->agreementNo);
$accounts = $bank->getAccounts();
$transactions = $bank->searchAccount($accounts[0]->id, '2012-07-01', '2012-08-01');
?>