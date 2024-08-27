# Boundaries of transactions across different components of the system.

```
[Client] --> [API/Application Layer]
|
| (1) Command
v
[Command Handler]
|
| (2) Begin Transaction
v
[Domain Model (Aggregate)]
|
| (3) Apply business logic
|     Generate domain events
v
[Event Store]
|
| (4) Store events
|
| (5) Commit Transaction
v
[Event Publisher]
|
| (6) Publish events (outside transaction)
|
------------------------------------
|                |                 |
v                v                 v
[Event Handler 1]  [Event Handler 2]  [Event Handler N]
(e.g., Projections) (e.g., Integration)  (e.g., Notifications)
|                |                 |
v                v                 v
[Read Model]    [External System]   [Email Service]
```

## Schematic representation of the transaction boundary

1. Client sends a command to the Application Layer.
2. Command Handler receives the command and begins a transaction.
3. Domain Model (Aggregate) applies business logic and generates domain events.
4. Events are stored in the Event Store within the same transaction.
5. Transaction is committed, ensuring that both state changes and events are stored atomically.
6. After the transaction is committed, events are published to various event handlers.

```
|--------------------- Transaction Boundary ---------------------|
|                                                                |
|  [Command Handler] -> [Domain Model] -> [Event Store]          |
|                                                                |
|----------------- Commit ------------------|

[Event Publisher] -> [Event Handlers] (Outside Transaction)
```

## Key Points

1. The transaction boundary encompasses the Command Handler, Domain Model, and Event Store. This ensures that all state changes and event storage are atomic.
2. Event publishing and handling occur outside the transaction boundary. This prevents long-running or potentially failing operations (like sending emails or updating read models) from affecting the core domain transaction.
3. Read models are updated asynchronously based on the published events, maintaining the CQRS separation.
4. Integration with external systems and side effects (like sending notifications) are handled outside the main transaction, allowing for retry mechanisms and preventing cascading failures.

Additional Considerations:

1. Outbox Pattern: You might implement an outbox within the transaction boundary to ensure events are reliably published even if the event publisher fails.
2. Sagas/Process Managers: For long-running business processes that span multiple aggregates, you'd implement these outside the main transaction boundary, reacting to events and issuing new commands as needed.
3. Read Model Transactions: While updating read models is typically done outside the main transaction, you might use separate transactions for consistency in the read model itself.