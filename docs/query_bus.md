Query bus is a component in a CQRS (Command Query Responsibility Segregation) architecture that is responsible for handling and routing queries to the appropriate query handlers.

1. **CQRS Context:** In CQRS, we separate the read and write operations of a system. The query bus is specifically related to the read (query) side of this separation. It's responsible for handling and routing query requests to the appropriate handlers.
2. **Relationship to Event Sourcing:** While event sourcing primarily deals with the write side (storing events as the source of truth), the query bus helps in efficiently retrieving data for read operations.
While event sourcing primarily deals with the write side (storing events as the source of truth), the query bus helps in efficiently retrieving data for read operations. In an event-sourced system, you often need to build and maintain read models or projections for efficient querying. The query bus helps in accessing these read models.
3. **DDD Perspective:** In DDD, the query bus aligns with the concept of separating the domain model from the read model.

Key points about a query bus:

1. **Separation of Concerns:** It keeps query handling logic separate from command handling and domain logic.
2. **Decoupling:** It decouples the client code (UI, API) from the actual query handlers, allowing for more flexibility and easier testing.
3. **Single Entry Point:** Provides a unified way to handle all queries in the application.
4. **Scalability:** Makes it easier to scale the read side independently of the write side.
5. **Performance Optimization:** Allows for optimized read models tailored for specific queries, which is particularly useful in event-sourced systems.
6. **Consistency with Command Bus:** In a full CQRS implementation, you often have both a command bus and a query bus, providing a consistent approach to handling both writes and reads.

The query bus serves as a mediator between the application layer (where queries are initiated) and the infrastructure layer (where data is actually retrieved, often from read models or projections).
By using a query bus, you maintain a clear separation between your domain model (which handles business logic and state changes) and your read model (which is optimized for querying), adhering to CQRS principles while fitting well within a DDD and event-sourced architecture.