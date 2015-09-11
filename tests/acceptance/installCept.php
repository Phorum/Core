<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('ensure that installation screen works');
$I->amOnPage('/admin.php');
$I->see('Checking your system');