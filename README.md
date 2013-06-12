Code Examples
=============
Below is a rough outline of the code examples directories and their contents.
You can view these files by making your way to the "Files" area above.
### 1. functions
    * faa_aircraft_info.php
        - Custom function that utilizes phpQuery to access the
          FAA's website to gather detailed information about
          aircraft given their N Number (Tail Number).
### 2. Laravel
####    * libraries
            * flash.php
                - Custom library that allows management of system messages
                  for things like errors, form feedback, and general overall
                  information.
####    * tasks
            * export_test_data_students.php
                - Exports students' info on a DB that has been restored
                  via the restore_test_data_students.php task.
            * restore_test_data_students.php
                - Restores DB backups for a given date range.
