A distributed transaction is a transaction that involves multiple distinct parts of a distributed system, typically spanning across different services, databases, or even physical machines. It's designed to maintain ACID (Atomicity, Consistency, Isolation, Durability) properties across these distributed components.

Key characteristics of distributed transactions:

1. Multiple Resources: They involve two or more resources (e.g., databases, message queues) that may be on different systems.

2. Atomicity Across Systems: All operations within the transaction must succeed or fail as a unit, even though they're on different systems.

3. Coordination: Requires a coordinator (often called a transaction manager) to oversee the entire process.

4. Two-Phase Commit: Often uses a two-phase commit protocol:
    - Prepare phase: All participants are asked if they can commit.
    - Commit phase: If all agree, they're all told to commit.

5. Complexity: More complex to implement and manage than local transactions.

6. Performance Impact: Can be slower due to the need for coordination and network communication.

7. Potential for Blocking: Long-running distributed transactions can lead to resource locking across systems.

Example scenario:
Imagine an e-commerce system where placing an order involves:
1. Updating the order database
2. Reducing inventory in the inventory database
3. Charging the customer's credit card via a payment service

A distributed transaction would ensure that either all these operations succeed together, or none of them do.

Challenges with distributed transactions:

1. Performance: The coordination required can slow down operations.
2. Scalability: They can limit the system's ability to scale, especially in microservices architectures.
3. Availability: If one part of the system is down, it can block the entire transaction.
4. Complexity: They're harder to implement and debug.

Due to these challenges, many modern distributed systems prefer eventual consistency and compensating transactions over distributed transactions where possible. However, distributed transactions can still be necessary in some scenarios where strict consistency is required across multiple systems.