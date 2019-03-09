<?php

namespace Mod\FireSock;

use \Mod\FireSock\ISubscription;

class WebSocketUser
{
    public $socket;
    public $id;
    public $headers = array();
    public $handshake = false;
  
    public $handlingPartialPacket = false;
    public $partialBuffer = "";
  
    public $sendingContinuous = false;
    public $partialMessage = "";
    
    public $hasSentClose = false;

    public $subscriptions = [];

    public $address;
  
    function __construct($id, $socket) {
        $this->id = $id;
        $this->socket = $socket;
        socket_getpeername($this->socket, $this->address);
    }

    function processMessage($server, $message) {
        if (strpos($message, " ") === false) {
            return;
        }

        $msgParts = explode(" ", $message, 2);
        $hook = $msgParts[0];

        if (strlen($hook) == 0) {
            return;
        }

        $isSubscribed = isset($this->subscriptions[$hook]);

        $body = $msgParts[1];
        if ($body == "subscribe") {
            if ($isSubscribed) {
                // already subscribed!
                return;
            }

            $newSubscription = $this->getNewSubscription($server, $hook);
            if ($newSubscription === false) {
                return;
            }

            $this->subscriptions[$hook] = $newSubscription;
            return;
        }

        if (!$isSubscribed) {
            // not subscribed yet
            return;
        }

        if ($body == "unsubscribe") {
            unset($this->subscriptions[$hook]);
            return;
        }

        $this->subscriptions[$hook]->processMessage($body);
    }

    function getNewSubscription($server, $requestedHook) {
        // Enumerate hooks
        foreach ($server->subscription_hooks as $hook) {
            $hookString = $hook[0];
            $hookClassName = $hook[1];
            if ($hookString !== $requestedHook) {
                continue;
            }

            $sub = new $hookClassName($server, $this, $requestedHook);

            if (!$sub instanceof ISubscription) {
                return false;
            }

            return $sub;
        }

        return false;
    }

    function tick($server) {
        foreach ($this->subscriptions as $hook => $subscription) {
            $subscription->tick();
        }
    }
}