<?php

it('returns 404 for the removed changelog route', function () {
    $this->get('/changelog')->assertNotFound();
});
