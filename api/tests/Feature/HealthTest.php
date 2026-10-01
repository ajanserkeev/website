<?php

it('reports healthy when the database is reachable', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJson(['status' => 'ok', 'database' => 'ok']);
});
