---
layout: default
parent: Client
title: Message bus
nav_order: 4
---
{% include support.md %}

# Client. Message bus

Here's a description of message bus from [Enterprise Integration Patterns](http://www.enterpriseintegrationpatterns.com/patterns/messaging/MessageBus.html)

> A Message Bus is a combination of a common data model, a common command set, and a messaging infrastructure to allow different systems to communicate through a shared set of interfaces.

If all your applications built on top of Enqueue Client you have to only make sure they send message to a shared topic.
The rest is done under the hood.

If you'd like to connect another application (written on Python for example ) you have to follow these rules:

* An application defines its own queue that is connected to the topic as fanout.
* A message sent to message bus topic must have a header `enqueue.topic_name`.
* Once a message is received it could be routed internally. `enqueue.topic_name` header could be used for that.

[back to index](../index.md)
