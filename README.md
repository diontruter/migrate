# migrate
Simple SQL migration tool

SimpleMigration is the main class containing migration functions. Can be called in command ine mode via:
- SimpleMigration::processCommandLine($argv)

or otherwise individual functions can be used:
- SimpleMigration::migrate($up = true)
- SimpleMigration::status()

Upward migrations will process all migration files not yet processed, and downward migrations will only run the
downward migration script for the latest migration.

Migration scripts must be in this format: '\<id\>-\<u|d\>[description].sql' Valid examples:
- 123-up-2017-01-01-new-year-fix.sql
- 123-down-2017-01-01-reverse-new-year-fix.sql
- 123-U.sql
- 123-d.sql
- 00123-upwards-migration.sql
- 00123-downwards-migration.sql

Migrations are tracked via the ID part of the file name, and grouped into upward and downward migration pairs.
Downward migration files are optional; when there is no downward migration for the latest upward migration an
error will occur when a downward migration is attempted.

All files in the migration-scripts directory are read, and files that do not have a .sql extension are ignored.
Migrations are sorted by their ID prefix, and run consecutively based on their IDs.

Migration scripts can contain arbitrary SQL statements separated by semicolons. The SqlScriptParser class
separates SQL statements in order to send them via PDO. It can handle the precedence between semicolons, single
line comments, multi line comments and quoted strings.  All SQL statements within a given script are run in a
single database transaction in order to guarantee integrity.

This application uses very basic SQL that has been tested on MySQL and PostgreSQL. It should work with any SQL
based database, but this has not been verified yet.

Once a migration script has been run it can be safely renamed, as long as the parsed ID part equals the original
integer ID value when it was first run. If an upward migration script is deleted or the ID part is changed, it will
have no effect as only the down migration will be needed in future. After downgrading past the deleted upward
migration, future upward migrations will exclude the deleted file. Always add new migration scripts with a higher
ID than the ID of the last script. The migration tool will only process new migrations if their ID is larger
than the last migration ID that was run.

This class takes a configuration path when constructed. The configuration path must be a plain PHP file that
returns a configuration array. The array must have this structure:

<?php

return [

    'basePath' => 'path-within-which-to-find-migration-scripts-directory',

    'connectionString' => 'pdo-database-connection-string',

    'userName' => 'optional-pdo-username',

    'password' => 'optional-pdo-password'

];
