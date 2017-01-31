-- Just a sanity check
DROP TABLE IF EXISTS processing_statuses;

/*
 * The processing statuses table is used to track and report on background tasks.
 */

CREATE TABLE processing_statuses (
  id INT NOT NULL PRIMARY KEY,
  slug VARCHAR(50),
  label VARCHAR(255) NOT NULL,
  description VARCHAR(255),
  sort_order INT NOT NULL DEFAULT 0,
  enabled BOOLEAN NOT NULL DEFAULT TRUE
);

/*
 * Insert the default processing statuses. Specify IDs so we can reference statuses by primary key via constants.
 */

INSERT INTO processing_statuses (id, slug, label, description) VALUES
  (1, 'pending', 'Pending', 'Pending'),
  (2, 'queued', 'Queued', 'Queued'),
  (3, 'processing', 'Processing', 'Processing'),
  (4, 'success', 'Successful', 'Successful'),
  (5, 'failed', 'Failed', 'Failed'),
  (6, 'cancelled', 'Cancelled', 'Cancelled'),
  (7, 'hold', 'On Hold', 'On Hold');
