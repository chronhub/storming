# Event Store

There are several strategies that can be used to store stream events in an event store. Here are some common ones:

1. **Append-only Log**: The simplest approach is to store events in an append-only log. Each event is appended to the end of the log, and the events are retrieved in the order they were stored. This approach is simple to implement and provides good performance for writes, but reading events can be slower as you need to scan the entire log to find a specific event.

2. **Partitioned Log**: To improve the performance of reading events, the event log can be partitioned based on some criteria, such as aggregate ID or event type. This allows you to quickly locate the relevant partition and read the events within that partition.

3. **Event Sourcing**: In an event sourcing architecture, events are stored as the canonical representation of the application state. Each aggregate has its own event stream, and the current state of the aggregate is rebuilt by replaying all the events in the stream. This approach provides a strong audit trail and enables easy reconstruction of the application state at any point in time.

4. **Relational Database**: Events can be stored in a relational database, with each event being a row in a table. This approach can provide the benefits of a mature, reliable database system, such as transactions, querying, and indexing. However, it may require more complex schema design and can have performance limitations for high-volume event stores.

5. **NoSQL Database**: NoSQL databases, such as document-oriented or key-value stores, can be a good fit for storing events. These databases are often more scalable and flexible than relational databases, making them suitable for high-volume event stores. However, they may lack some of the features and maturity of relational databases.

6. **Distributed Log**: Distributed log systems, such as Apache Kafka or Amazon Kinesis, can be used as the underlying storage for an event store. These systems provide high availability, scalability, and fault tolerance, making them a good choice for large-scale event-driven applications.

The choice of event store strategy depends on factors such as the volume of events, the read and write patterns, the need for transactions, the requirement for audit trails, and the overall architectural approach of the application. Many event-driven systems use a combination of these strategies, such as using a distributed log for high-volume event ingestion and a relational database for long-term storage and querying.