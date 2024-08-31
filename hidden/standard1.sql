-- Sequence for Stream Event position
CREATE SEQUENCE stream_event_no_seq START 1;

CREATE OR REPLACE FUNCTION NextPosition() RETURNS bigint AS $$
BEGIN
    RETURN nextval('stream_event_no_seq');
END;
$$ LANGUAGE plpgsql;

-- Create the stream event table
CREATE TABLE IF NOT EXISTS stream_event
(
    position bigint NOT NULL,
    internal_position bigint DEFAULT NULL,
    stream_name varchar NOT NULL,
    type varchar NOT NULL,
    id uuid NOT NULL,
    version bigint NOT NULL,
    header jsonb NOT NULL,
    content jsonb NOT NULL,
    created_at timestamptz NOT NULL DEFAULT now(),

    CONSTRAINT pk_stream_event PRIMARY KEY (position, stream_name),
    CONSTRAINT uk_stream_event UNIQUE (type, id, version)
);

CREATE INDEX idx_stream_event_stream_name ON stream_event(stream_name);
CREATE INDEX idx_stream_event_created_at ON stream_event(created_at);
CREATE INDEX idx_stream_event_header ON stream_event USING GIN (header);
CREATE INDEX idx_stream_event_content ON stream_event USING GIN (content);
CREATE INDEX idx_stream_event_stream_name_position ON stream_event(stream_name, position);

-- Create an index on internal_position for partitioning
-- CREATE INDEX IF NOT EXISTS idx_internal_position ON stream_event (internal_position);

-- Disable UPDATE and DELETE on stream_event
CREATE RULE rule_no_update_stream_event AS
    ON UPDATE TO stream_event DO INSTEAD NOTHING;
CREATE RULE rule_no_delete_stream_event AS
    ON DELETE TO stream_event DO INSTEAD NOTHING;

-- Sequence for Event Stream NO
CREATE SEQUENCE IF NOT EXISTS event_stream_no_seq;

-- Table for storing event streams
CREATE TABLE IF NOT EXISTS event_stream
(
    no BIGINT NOT NULL DEFAULT nextval('event_stream_no_seq'),
    stream_name varchar NOT NULL,
    real_stream_name varchar NOT NULL,
    partition varchar DEFAULT NULL,
    created_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (no, stream_name),
    CONSTRAINT uk_event_stream UNIQUE (stream_name)
);

-- Function to handle inserting records into stream_event table and creating the necessary tables
CREATE OR REPLACE FUNCTION stream_event_insert_trigger() RETURNS TRIGGER AS $$
DECLARE
    stream_parts text[];
    internal_position bigint;
position bigint;

BEGIN
    -- Extract stream name parts using regex
    stream_parts := regexp_match(NEW.stream_name, '^([^-]+)(?:-(.+))?$');

    -- Ensure the appropriate table exists
    PERFORM ensure_stream_table(NEW.stream_name, stream_parts[1], stream_parts[2]);

    -- Only proceed if all required fields are provided
    IF NEW.type IS NOT NULL AND NEW.id IS NOT NULL AND NEW.version IS NOT NULL
        AND NEW.header IS NOT NULL AND NEW.content IS NOT NULL THEN

    BEGIN
        internal_position := (NEW.header ->> '__internal_position')::bigint;
    EXCEPTION
        WHEN invalid_text_representation THEN
                internal_position := NULL;
        WHEN null_value_not_allowed THEN
                internal_position := NULL;
    END;

    position := NextPosition();

    -- Insert record into the appropriate table
    EXECUTE format(
        'INSERT INTO %I (position,internal_position, stream_name, type, id, version, header, content)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)',
        NEW.stream_name
    ) USING position, internal_position, NEW.stream_name, NEW.type, NEW.id, NEW.version, NEW.header, NEW.content;

    END IF;

RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Separate function for ensuring the stream table exists
CREATE OR REPLACE FUNCTION ensure_stream_table(
    stream_name text,
    partition_name text,
    derived_table_name text
) RETURNS void AS $$
BEGIN

    -- Acquire an advisory lock
    PERFORM pg_advisory_xact_lock(hashtext(stream_name));

    -- todo change schema from public
    -- Check if the table for the stream_name exists in the public schema
    IF NOT EXISTS (SELECT 1 FROM pg_tables WHERE tablename = stream_name AND schemaname = 'public') THEN
        -- Create the table and add constraints
        EXECUTE format('CREATE TABLE %I (CHECK (stream_name = %L)) INHERITS (stream_event)', stream_name, stream_name);
        EXECUTE format('ALTER TABLE %I ADD CONSTRAINT %I UNIQUE (type, id, version)', stream_name, 'uk_' || stream_name);

        -- Insert into event_stream
        IF derived_table_name IS NOT NULL THEN
                    -- This is a partitioned stream
                    IF partition_name IS NULL OR partition_name = '' THEN
                        RAISE EXCEPTION 'Partition name cannot be empty for a derived table';
        END IF;

        INSERT INTO event_stream (stream_name, real_stream_name, partition)
        VALUES (stream_name, stream_name, partition_name);

    ELSE
        -- This is a regular stream
        INSERT INTO event_stream (stream_name, real_stream_name, partition)
        VALUES (stream_name, stream_name, null);

    END IF;

END IF;

END;
$$ LANGUAGE plpgsql;


-- Create a trigger to be executed BEFORE INSERT on STREAM EVENT table
CREATE TRIGGER stream_event_insert_trigger
    BEFORE INSERT ON stream_event
    FOR EACH ROW EXECUTE FUNCTION stream_event_insert_trigger();
