<?php

it('shows the Filament login page', function () {
    $this->get('/admin/login')->assertOk();
});

it('redirects guests away from the admin panel', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});
