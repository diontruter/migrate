DELETE FROM processing_statuses WHERE id = 7;

UPDATE processing_statuses SET label = 'Aborted', description = 'Aborted' WHERE id = 6;
