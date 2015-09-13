<?php 
$I = new AcceptanceTester($scenario);
// check the forum homepage
$I->amOnPage('/');
$I->see('Forums');

