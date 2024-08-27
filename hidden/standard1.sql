-- Stream event sequence number
CREATE TABLE IF NOT EXISTS PositionCounter
(
    Position bigint NOT NULL
);

INSERT INTO PositionCounter VALUES (0);

-- Disable INSERT and DELETE on PositionCounter
CREATE RULE rule_no_insert_positioncounter AS
    ON INSERT TO PositionCounter DO INSTEAD NOTHING;

CREATE RULE rule_no_delete_positioncounter AS
    ON DELETE TO PositionCounter DO INSTEAD NOTHING;

-- Get the next position number from PositionCounter
CREATE FUNCTION NextPosition() RETURNS bigint AS $$
DECLARE
    nextPos bigint;
BEGIN
    UPDATE PositionCounter SET Position = Position + 1;
    SELECT INTO nextPos Position FROM PositionCounter;
    RETURN nextPos;
END;
$$ LANGUAGE plpgsql;

-- Create the stream event table
CREATE TABLE stream_event
(
    position bigint NOT NULL,
    stream_name varchar NOT NULL,
    type varchar NOT NULL,
    id uuid NOT NULL,
    version bigint NOT NULL,
    header jsonb NOT NULL,
    content jsonb NOT NULL,
    metadata jsonb DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now()
);

-- Constraints for the stream_event table
ALTER TABLE stream_event ADD CONSTRAINT pk_stream_event PRIMARY KEY (position, stream_name);
ALTER TABLE stream_event ADD CONSTRAINT uk_stream_event UNIQUE (type, id, version);

-- Disable UPDATE and DELETE on stream_event
CREATE RULE rule_no_update_streamevent AS
    ON UPDATE TO stream_event DO INSTEAD NOTHING;

CREATE RULE rule_no_delete_streamevent AS
    ON DELETE TO stream_event DO INSTEAD NOTHING;

-- Sequence for Event Stream NO
CREATE SEQUENCE IF NOT EXISTS event_stream_no_seq;

-- Table for storing event streams
CREATE TABLE event_stream
(
    no BIGINT NOT NULL DEFAULT nextval('event_stream_no_seq'),
    stream_name varchar NOT NULL,
    real_stream_name varchar NOT NULL,
    partition varchar DEFAULT NULL,
    created_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (no, stream_name)
);

-- Unique constraint for the EVENT STREAM table
ALTER TABLE event_stream ADD CONSTRAINT uk_event_stream UNIQUE (stream_name);

-- Function to handle inserting records into stream_event table and creating the necessary tables
CREATE OR REPLACE FUNCTION stream_event_insert_trigger() RETURNS TRIGGER AS $$
DECLARE
    stream_name varchar(255);
    base_table_name varchar(255);
    derived_table_name varchar(255);
    uk_stream_name varchar(255);
    uk_base_table_name varchar(255);
    uk_derived_table_name varchar(255);
    next_position bigint;
    is_projection_event boolean;
    is_link_to_event boolean;
BEGIN
    -- Extract information from the NEW row
    stream_name := NEW.stream_name;
    base_table_name := nullif(split_part(stream_name, '-', 1), '');
    derived_table_name := nullif(split_part(stream_name, '-', 2), '');
    uk_stream_name := 'uk_' || stream_name;
    uk_base_table_name := 'uk_' || base_table_name;
    uk_derived_table_name := 'uk_' || stream_name;

    -- Check if this is a projection-emitted event or link-to event using metadata
    is_projection_event := (NEW.metadata->>'is_projection')::boolean;
    is_link_to_event := (NEW.metadata->>'is_link_to')::boolean;

    IF NOT is_projection_event AND NOT is_link_to_event THEN
        -- Check if the table for the stream_name exists in the public schema
        IF NOT EXISTS (SELECT 1 FROM pg_tables WHERE tablename = stream_name AND schemaname = 'public') THEN
            -- Check if stream_name contains a hyphen
            IF POSITION('-' IN stream_name) = 0 THEN
                -- Create a table for stream_name and add constraints
                EXECUTE format('CREATE TABLE %I (CHECK (stream_name LIKE %L)) INHERITS (stream_event)', stream_name, stream_name);
                EXECUTE format('ALTER TABLE %I ADD CONSTRAINT %I UNIQUE (type, id, version)', stream_name, uk_stream_name);
                INSERT INTO event_stream (stream_name, real_stream_name, partition) VALUES (stream_name, stream_name, NULL);
            ELSE
                -- Create a base table if it doesn't exist
                IF base_table_name IS NOT NULL AND NOT EXISTS (SELECT 1 FROM pg_tables WHERE tablename = base_table_name) THEN
                    EXECUTE format('CREATE TABLE %I (CHECK (stream_name LIKE %L)) INHERITS (stream_event)', base_table_name, base_table_name || '%');
                    EXECUTE format('ALTER TABLE %I ADD CONSTRAINT %I UNIQUE (type, id, version)', base_table_name, uk_base_table_name);
                    INSERT INTO event_stream (stream_name, real_stream_name, partition) VALUES (base_table_name, base_table_name, NULL);
                END IF;

                -- Create a derived table if it doesn't exist
                IF derived_table_name IS NOT NULL THEN
                    EXECUTE format('CREATE TABLE %I (CHECK (stream_name = %L)) INHERITS (%I)', stream_name, stream_name, base_table_name);
                    EXECUTE format('ALTER TABLE %I ADD CONSTRAINT %I UNIQUE (type, id, version)', stream_name, uk_derived_table_name);
                    INSERT INTO event_stream (stream_name, real_stream_name, partition) VALUES (stream_name, stream_name, base_table_name);
                END IF;
            END IF;
        END IF;
    END IF;

    -- Get the next position
    SELECT NextPosition() INTO next_position;

    -- Insert record into the stream_event table
    NEW.position := next_position;
    INSERT INTO stream_event VALUES (NEW.*);

    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Create a trigger to be executed BEFORE INSERT on STREAM EVENT table
CREATE TRIGGER stream_event_insert_trigger
    BEFORE INSERT ON stream_event
    FOR EACH ROW EXECUTE FUNCTION stream_event_insert_trigger();