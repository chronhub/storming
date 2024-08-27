# Projection emitter

The purpose of projections is to process and analyze events from one or more event streams, and potentially create new events or link existing events to new streams. This functionality is powerful for creating different views of your data, performing complex event processing, and building read models:

1. **Emitting events to a new stream:**.

    Purpose:
    
    Create a new event stream based on processed data from other streams.
    Aggregate or transform data from multiple streams into a new, meaningful stream.
    Generate derived events that represent higher-level business concepts.
    
    How to use:
    
    After creating the projection, you can read from the new stream just like any other event stream.
    Use the client API to read events from the projected stream.
    Use these events to build read models or trigger further processes in your application.
    
    Example use case:
    A projection that monitors customer orders and emits "LoyalCustomer" events to a new stream when a customer makes their 10th order.

2. **Linking events to a new stream:**

    Purpose:
    
    Create a new stream that references events from other streams without duplicating data.
    Organize events from multiple streams into a logical grouping without moving the original events.
    Create different views or categorizations of existing events.
    
    How to use:
    
    After creating the projection, you can read from the new stream to get references to the original events.
    When reading the linked stream, you'll get link events that point to the original events.
    You can resolve these links to access the full event data from the original streams.