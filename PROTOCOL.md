# SlowTransfer Protocol

This document describes the first version of the SlowTransfer protocol (SLOWTRANSFER-1).

## Protocol identifier
All requests and responses start with a protocol identifier. In a successful request, the response identifier must match the request identifier. This means that no protocol identifier will be compatible with another one. 

## Intent
The protocol identifier is followed by the request or response intent. 

### `ERROR`
This intent is followed by an error code.

* 10 - Intent is not supported by server.
* 11 - Protocol version not recognizable.
* 12 - Requested protocol is not fully implemented on server.
* 13 - Bad PUBLISH packet.

### `PING`
This intent requests a `PONG` intention from the other machine.

### `PONG`
This intent is a response to a `PING` intention.

### `PUBLISH`
This intent allow a client to publish a player transfer and associated data to the server. This intent is followed by a player identifier (name+IP) and then by a list of namespaces and data.

```
SLOWTRANSFER-1
PUBLISH
playerIdHere
namespace1
13
Hello World!
namespace2
5
Fun!
STOP
```

## `STOP`
All requests and responses end with a `STOP` code.


