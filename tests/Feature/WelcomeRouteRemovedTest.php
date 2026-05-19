<?php

it('returns 404 for the removed welcome route', function () {
    $this->get('/welcome')->assertNotFound();
});
