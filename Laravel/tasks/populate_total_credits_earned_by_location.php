<?php

/**
 * Populates the total_credits_earned_by_location DB table
 *
 * @author David Varney
 * LOCAL CLI EXAMPLE:
 * php artisan populate_total_credits_earned_by_location --sis_env=development --server=focus_sis.local
 * CLI EXAMPLE:
 * php artisan populate_total_credits_earned_by_location --client=focus
 *
 */

class Populate_Total_Credits_Earned_By_Location_Task extends Base_Task
{
    /**
     *
     * @author David Varney
     * @return  void
     */
    public function run($arguments)
    {
        // Make sure our task only runs for Focus client
        if(Client::is('focus')){
            $this->populate_db();
        }else{
            debug("Missing Parameter: district_irn");
        }

    }

    private function populate_db()
    {
        $inserted_records = array();
        // Get all of the locations from the DB
        $locations = DB::table('p_pupil_type')->get();
        // Get all of the school years from the DB
        $school_years = DB::table('school_year')->get();

        foreach($locations as $location){
            // Get all of the students and then filter the recordset by location
            $students = new student_recordset;
            $students->limit_by_district_irn($location->district_irn);
            foreach($school_years as $school_year){
                // Lets see if there is an existing record for this location and school_year
                $record = DB::table('total_credits_earned_by_location')
                    ->where('p_pupil_type_id', '=', $location->p_type_key)
                    ->where('school_year_id', '=', $school_year->school_year_id)
                    ->first();
                // If there is then we need to set the total credits to zero
                if($record){
                    // Lets go ahead and reset the count for the record
                    DB::table('total_credits_earned_by_location')
                        ->where('id', '=', $record->id)
                        ->update(array('total_credits' => 0.000));
                    // Set the $id variable so we have some sort of reference to fall back on
                    $id = $record->id;
                }else{
                    // Since there isn't a record that exists lets go ahead and create one
                    $insert_array = array('p_pupil_type_id' => $location->p_type_key, 'school_year_id' => $school_year->school_year_id, 'total_credits' => 0.000);
                    // Lets get the id for reference once we create the record
                    $id = DB::table('total_credits_earned_by_location')
                        ->insert_get_id($insert_array);
                }
                foreach($students as $student){
                    // Make sure the student was here during this school_year
                    if($student->get_enrolled_date_count_during_date_range($school_year->begin_date, $school_year->end_date) > 0){
                        // Since they were here lets go ahead and add their credits to the total_credits for this particular record
                        $record = DB::table('total_credits_earned_by_location')
                            ->where('id', '=', $id)
                            ->first();
                        DB::table('total_credits_earned_by_location')
                            ->where('id', '=', $record->id)
                            ->update(array('total_credits' => $record->total_credits + (float)$student->get_total_credits(false, $school_year->school_year_id)));
                    }
                }
            }
        }
    }
}