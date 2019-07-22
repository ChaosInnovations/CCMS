<?php

namespace Package\FireSock;

interface ISubscription {
    public function __construct($server, $user, $hook);
    public function processMessage($message);
    public function tick();
}