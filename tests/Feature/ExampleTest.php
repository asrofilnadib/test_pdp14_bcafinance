<?php

test('the application redirects guests to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

test('login page is accessible', function () {
    $response = $this->get('/login');

    $response->assertOk();
});
