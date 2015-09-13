<?php 
$I = new AcceptanceTester($scenario);
// check the forum homepage
$I->amOnPage('/');
$I->see('Forums');
$I->see('Test Forum');
$I->click('Test Forum');
$I->see('Test Message');
$I->click('Test Message');
$I->see('These tools will be visible in this screen if you log in as the administrator');

$I->click('New Topic');
$I->see('Sorry, only registered users may post in this forum');

$I->click('Log In');
$I->see('Username');

$I->submitForm('#login-form input[type=submit]', [
    'username' => 'admin',
    'password' => 'admin_pass',
]);

$I->see('Start a New Topic');
$I->see('Author:');
$I->see('Subject:');
$I->see('Message:');

$I->submitForm('#post_form input[type=submit]', [
        'subject' => 'Acceptance Tests are cool',
        'body' => 'Testmessage from Acceptance Tests',
        'finish' => 1
    ],
    'finish'
);

$I->see('Test Message');
$I->see('Acceptance Tests are cool');
$I->click('Acceptance Tests are cool');
$I->see('Testmessage from Acceptance Tests');
