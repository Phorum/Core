<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('ensure that installation works');
// go through the installation steps
$I->amOnPage('/admin.php');
$I->see('Checking your system');
$I->click('Continue ->');
$I->see('Tables created.');
$I->click('Continue ->');
$I->see('Please enter the following information.');
$I->submitForm('//form', [
    'admin_user' => 'admin',
    'admin_email' => 'test@phorum.org',
    'admin_pass' => 'admin_pass',
    'admin_pass2' => 'admin_pass'
]);
$I->see('Optional modules');
$I->click('Continue ->');
$I->see('The setup is complete.');

// check the forum homepage
$I->amOnPage('/');
$I->see('Forums');

