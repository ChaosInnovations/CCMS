var f$ = {
    FireSock: function() {
        this.isWebsocket = false;
        this.isLongPoll = false;
        this.isConnected = false;
        this.subscriptions = [];
        this.socket = null;
        this.sendQueue = [];
        this.isClosing = false;
        this.host = null;
        this.port = null;
        this.longPollToken = null;

        this.onOpen = function() {
        };

        this.onClose = function() {
            this.isWebsocket = false;
            this.isConnected = false;
            console.log("Disconnected - status "+this.socket.readyState);
            if (this.isClosing) {
                return;
            }
            this.startLongPoll();
        };

        this.subscribe = function(subscription) {
            subscription.connection = this;
            this.subscriptions.push(subscription);
        };

        this.unsubscribe = function(subscription) {
            this.subscriptions = this.subscriptions.filter(function(a) {return a != subscription;});
        };

        this.send = function(hook, message) {
            this.sendQueue.push(hook + " " + message);
            this.sendMessagesInQueue();
        };

        this.sendMessagesInQueue = function() {
            if (!this.isConnected) {
                console.log("wait for interface");
                return;
            }
            while (this.sendQueue.length > 0) {
                if (this.isWebsocket) {
                    this.socket.send(this.sendQueue.shift());
                    continue;
                }
                if (this.isLongPoll) {
	                $.post(BASE_URL + "/api/firesock/longpoll-in", {token: this.longPollToken, msg: this.sendQueue.shift()});
                    continue;
                }
                console.log("Can't send without interface: " + this.sendQueue.shift());
            }
        }

        this.close = function() {
            this.isClosing = true;
            if (this.socket != null) {
                console.log("Goodbye!");
                this.socket.close();
                this.socket=null;
            }
        };

        this.open = function(host, port) {
            this.host = host;
            this.port = port;
            this.isClosing = false;
            try {
                this.socket = new WebSocket("ws://" + host + ":" + port);
                console.log('WebSocket - status '+this.socket.readyState);
                var self = this;
                this.socket.onopen = function(){
                    self.isWebsocket = true;
                    self.isConnected = true;
                    console.log("Welcome - status "+self.socket.readyState);
                    self.onOpen();
                };
                var subs = this.subscriptions;
                this.socket.onmessage = function(msg) { 
                    console.log("Received: "+msg.data);
                    for (var sub of subs) {
                        if (msg.data.startsWith(sub.hook)) {
                            sub.onReceived(msg.data);
                        }
                    }
                };
                this.socket.onclose = function(){self.onClose();};
            }
            catch(ex){
                console.log(ex); 
            }
        };

        this.startLongPoll = function() {
            console.log("Requesting long-poll token");
            var self = this;
            $.get(BASE_URL + "/api/firesock/newlongpoll", function(token) {
                console.log("Token received: " + token);
                self.longPollToken = token;
                self.isLongPoll = true;
                self.isConnected = true;
                self.onOpen();
                self.longPoll();
            });
        }

        this.longPoll = function() {
            console.log("Start long-poll");
            this.sendMessagesInQueue();
            this.host;
            var self = this;
            $.ajax({
                method: "POST",
                url: "/api/firesock/longpoll",

                data: {token: self.longPollToken},

                cache: false,
                timeout: 60000, // 1 minute

                success: function(data) {
                    // Should respond with JSON array of messages: [msg1, msg2, ...]
                    try {
                        var msgs = JSON.parse(data);
                    }
                    catch (ex) {
                        return;
                    }
                    var subs = self.subscriptions;
                    msgs.forEach(msg => {
                        console.log("Received: "+msg);
                        for (var sub of subs) {
                            if (msg.startsWith(sub.hook)) {
                                sub.onReceived(msg);
                            }
                        }
                    });
                    // Start another poll
                    self.longPoll();
                },

                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    console.log("Long-poll error: " + textStatus + " (" + errorThrown + ")");
                    // Try another poll after 10 seconds
                    setTimeout(self.longPoll, 10000);
                }
            })
        };
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