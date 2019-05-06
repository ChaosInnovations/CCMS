var f$ = {
    FireSock: function() {
        this.isWebsocket = false;
        this.isConnected = false;
        this.subscriptions = [];
        this.socket = null;

        this.onOpen = function() {
            console.log("Welcome - status "+this.readyState);
        };

        this.onClose = function() {
            console.log("Disconnected - status "+this.readyState);
        };

        this.subscribe = function(subscription) {
            subscription.connection = this;
            this.subscriptions.push(subscription);
        };

        this.unsubscribe = function(subscription) {
            this.subscriptions = this.subscriptions.filter(function(a) {return a != subscription;});
        };

        this.send = function(hook, message) {
            this.socket.send(hook + " " + message);
        };

        this.close = function() {
            if (this.socket != null) {
                console.log("Goodbye!");
                this.socket.close();
                this.socket=null;
            }
        };

        this.open = function(host, port) {
            try {
                this.socket = new WebSocket("ws://" + host + ":" + port);
                console.log('WebSocket - status '+this.socket.readyState);
                this.isWebsocket = true;
                this.isConnected = true;
                this.socket.onopen = this.onOpen;
                var subs = this.subscriptions;
                this.socket.onmessage = function(msg) { 
                    console.log("Received: "+msg.data);
                    for (var sub of subs) {
                        if (msg.data.startsWith(sub.hook)) {
                            sub.onReceived(msg.data);
                        }
                    }
                };
                this.socket.onclose = this.onClose;
            }
            catch(ex){
                console.log(ex); 
            }
        }
    },

    Subscription: function(hook) {
        this.hook = hook;
        this.onReceived = function(message) {
            console.log(message);
        };
    },
};

f$.connection = new f$.FireSock();

f$.connection.onOpen = function() {
    console.log("Welcome - status "+this.readyState);

    var authSubscription = new f$.Subscription("user");
    authSubscription.onReceived = function(msg) {
    }

    f$.connection.subscribe(authSubscription);
    f$.connection.send("user", "subscribe");
    f$.connection.send("user", JSON.stringify({function: "authenticate", token: Token()}));
}

f$.connection.open(location.hostname, "9000");