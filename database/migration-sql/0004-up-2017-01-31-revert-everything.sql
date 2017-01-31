UPDATE processing_statuses SET label = 'Cancelled', description = 'Cancelled' WHERE id = 6;

INSERT INTO processing_statuses (id, slug, label, description) VALUES
  (7, 'hold', 'On Hold', 'On Hold');
