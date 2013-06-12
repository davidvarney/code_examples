<?php

/**
 * Restores DB backups for a given date range
 * @author David Varney
 * Required Params
 * @param date start_date EX: --start_date=2012-01-01
 * @param date end_date EX: --end_date=2012-01-31
 * @param str client EX: --client=vcs || --client=focus .....etc
 * CLI EXAMPLE:
 * php artisan restore_test_data_students --start_date=2012-01-01 --end_date=2012-01-31 --client=vcs
 */
class Restore_Test_Data_Students_Task extends Base_Task
{
    /**
     *
     * @author David Varney
     * @return  void
     */
    public function run($arguments)
    {
        $start_date = Input::get('start_date', null);
        $end_date = Input::get('end_date', null);
        $client = Input::get('client', null);
        $dates_array = get_days_between($end_date, $start_date, false, false, true, true, false, 0);
        /**
         * Lets reverse the ordering to the $dates_array so that we
         * start with the newest date first and work backwards
         * towards the oldest last.
         */
        rsort($dates_array);
        // Lets see if all of these dates are valid
        $dates_array = $this->check_dates($dates_array, $client);
        foreach($dates_array as $date){
            $this->restore_db($date, $client);
        }
    }

    /**
     * Restores a clients SIS db as a new instance with the
     * following name 'CLIENT-NAME-HERE_archive_sis'
     * @author David Varney
     * @param date $date EX: 2012-01-01
     * @param str $client_name EX: 'vcs' || 'focus' .....etc
     */
    private function restore_db($date, $client_name)
    {
        $db_name = $client_name . '_archive_sis';
        $ssh_connect_string = "ssh -p 1222 -i ~/.ssh/dev_rsa vagrant@dev.eschoolconsultants.com";
        $create_empty_database_command = "mysql --user=root --password=root -e \"DROP DATABASE IF EXISTS $db_name; CREATE DATABASE $db_name COLLATE=utf8_general_ci;\"";
        $remote_filename = "/mnt/external/backups/db/$date/" . $client_name . "_sis_backup.sql.bz2";
        $decompress = "| pbzip2 --decompress";
        $restore_remote_database_command = "$ssh_connect_string cat $remote_filename | pv --wait $decompress | mysql --user=root --password=root $db_name";

        // Let's first run the $create_empty_database_command
        exec($create_empty_database_command);
        // Now we're doin' some restorin'!
        exec($restore_remote_database_command, $output, $return_var);

        $this->run_migrations($db_name);

        $this->run_export_task($db_name);
    }

    /**
     * Checks to see if there is indeed a valid backup for the
     * dates within the passed $dates variable. If it isn't a
     * valid date then the date will be stripped from the
     * $dates variable.
     * @author David Varney
     * @param array $dates
     * @return array $dates
     */
    private function check_dates($dates, $client)
    {
        $remote_filename = $client . "_sis_backup.sql.bz2";
        $ssh_connect_string = "ssh -p 1222 -i ~/.ssh/dev_rsa vagrant@dev.eschoolconsultants.com";
        $dates_to_return = array();
        foreach($dates as $date){
            $remote_ls_command = "$ssh_connect_string ls /mnt/external/backups/db/$date";
            exec($remote_ls_command, $files);
            if(in_array($remote_filename, $files)){
                $dates_to_return[] = $date;
            }
            unset($files);
        }
        return $dates_to_return;
    }

    /**
     * We Have to run the migrations for the DB that we just
     * restored. If we don't then we'll potentially get errors
     * @author David Varney
     * @param str $db_name The name of the archive SIS instance
     */
    private function run_migrations($db_name)
    {
        $run_migrations_command = "/usr/bin/php /var/www/eschool/sis/artisan db:migrate --sis_env=development --server=$db_name.local";
        exec($run_migrations_command);
    }

    /**
     * Now we need to run the second task on the archive_sis site.
     * @author David Varney
     * @param str $db_name The name of the archive SIS instance
     */
    private function run_export_task($db_name)
    {
        $run_task_command = "/usr/bin/php /var/www/eschool/sis/artisan export_test_data_students --sis_env=development --server=$db_name.local";
        exec($run_task_command);
    }

}