# Database safety - Shan's Guitar (XAMPP)

Companion to `CHANGELOG.md`. Read this before touching XAMPP or MySQL.

## 1. What broke on 17 Sep 2026 (so it never happens again)

Login died with:

    ERROR 1932 (42S02): Table 'shansguitar.admins' doesn't exist in engine

Cause chain:

1. InnoDB keeps its list of tables (the *data dictionary*) inside one file:
   `C:\xampp\mysql\data\ibdata1`. At 18:10 on 17 Sep that file was missing, so MySQL
   logged `The first innodb_system data file 'ibdata1' did not exist. A new tablespace
   will be created!` and built a **blank** one. From then on the engine no longer knew
   that `admins`, `users`, `guitars`, `orders` exist - even though their `.frm`/`.ibd`
   files were still on disk. Hence error 1932 (not 1146: the files exist, the entry doesn't).
2. Two habits produced that state: database folders were **swapped/copied by hand**
   (`data_corrupted`, `data_backup_2026-09-14`, `data_old` inside `C:\xampp\mysql`), and
   MySQL was repeatedly **force-killed** instead of stopped (`mysql_stop.bat` /
   `apache_stop.bat` in `C:\xampp` run `killprocess.bat`, which is `taskkill /F`;
   `mysql_error.log` contains no clean-shutdown record at all, and Apache logged
   `Unclean shutdown of previous Apache run?`).

`ibdata1 + ib_logfile0 + ib_logfile1 + every \*.frm / \*.ibd` are ONE unit. Copying a
single database folder, or restoring only part of a data folder, destroys that link.

## 2. Shutdown routine (30 seconds, do it every time)

1. XAMPP Control Panel -> **Stop MySQL** (wait until the row is grey).
2. Control Panel -> **Stop Apache**.
3. Verify nothing survived:

       tasklist | findstr /i "mysqld httpd"      rem must print NOTHING

4. Only then: Start -> Power -> Shut down (same before Restart, Sleep, Hibernate, closing the lid).
5. If the panel ever says **"MySQL shutdown unexpectedly"**, that start already ran crash
   recovery: run `C:\xampp\start_work_check.bat` and, if it complains, restore the newest
   dump before doing anything else.

## 3. Never do these

1. Never run `C:\xampp\mysql_stop.bat` / `apache_stop.bat` (they `taskkill /F` mysqld).
2. Never move, rename, delete or "fix" anything inside `C:\xampp\mysql\data` -
   especially `ibdata1`, `ib_logfile0`, `ib_logfile1`.
3. Never copy a database folder into another `data` folder (InnoDB needs its dictionary;
   you get 1932 again).
4. Never rename an old data folder back to `data`, and never restore a partial data folder.
5. Never hard power-off / force-restart while the panel is green.

## 4. Backups (the real protection)

    C:\xampp\db_backups\backup_databases.bat
        -> one file per database + ALL_databases_<date>.sql, timestamped, safe while MySQL runs

    C:\xampp\db_backups\restore_database.bat <database> <file.sql>
        -> drops + recreates that database and imports the file

Run a backup before every demo/submission, and after any significant data change.
Keep the files - they live OUTSIDE `mysql\data` on purpose.

## 5. Health check

    C:\xampp\start_work_check.bat

Prints `Database OK - shansguitar is readable.` or a loud warning with the exact recovery step.

## 6. Where the current data came from (25 Sep 2026 repair)

* shansguitar - salvaged from the 17 Sep snapshot (`innodb_force_recovery=3`) ->
  `db_backups\SALVAGE_shansguitar_2026-09-25.sql` (1 admin, 15 products, 2 users, 8 orders)
* final_exam - same salvage -> `db_backups\SALVAGE_final_exam_2026-09-25.sql`
* shans_music_store - from the 05 Sep phpMyAdmin export -> `db_backups\shans_music_store_from_2026-09-05.sql`
* helpdesk_core - structure only (was always empty) -> `db_backups\SALVAGE_helpdesk_core_2026-09-25.sql`
* hr, employees - recreated empty (they never had tables)
* sales - NOT recoverable: no dictionary entry in any snapshot and no dump with tables.
  Recreate it from the original class scripts if they turn up, then run backup_databases.bat.

Old folders were moved out of `C:\xampp\mysql\` into `C:\xampp\db_snapshots\` so the
"wrong folder" trap is gone. The truncated dumps that begin with
`DROP DATABASE IF EXISTS ...` and contain no tables are quarantined in
`C:\xampp\db_snapshots\older_dumps\BAD_truncated\` - never import those.

## 7. Windows service (recommended, one-time)

XAMPP Control Panel (as Administrator) -> tick the **Service** box next to Apache and MySQL.
Windows then asks them to stop at shutdown instead of killing them, which removes the
crash-recovery risk entirely. Untick to undo.