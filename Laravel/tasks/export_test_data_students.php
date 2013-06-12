<?php
/**
 * Exports students' info on a DB that has been restored
 * via the restore_test_data_students.php task.
 * @author David Varney
 *
 * CLI EXAMPLE:
 * php artisan export_test_data_students --sis_env=development --server=vcs_archive_sis.local
 * CLI EXAMPLE for Debugging:
 * php artisan export_test_data_students --sis_env=development --server=vcs_archive_sis.local --max=5 --debug=true
 */
class Export_Test_Data_Students_Task extends Base_Task
{

    /**
     *
     * @author David Varney
     * @return  void
     */
    public function run($arguments)
    {
        $test_data_p_pupil_status = DB::table('p_pupil_status')
            ->where('p_status_desc', '=', 'TEST DATA')
            ->first();

        $students = new student_recordset(array('pupil_status' => $test_data_p_pupil_status->p_status_key));
        /**
         * USED FOR TESTING
         * INSTRUCTIONS FOR DEBUGGING
         * CHANGE $debug to true
         * Set $max to however many iterations you want
         */
        $debug = Input::get('debug', null);
        $count = 0;
        $max = Input::get('max', 5);
        foreach($students as $student){
            if(!$this->check_student($student)){
                if($debug){
                    $count++;
                    if($count <= (int)$max){
                        $this->export_student($student);
                    }
                }else{
                    // Here is where normal behavior falls to
                    $count++;
                    $this->export_student($student);
                }
            }else{
                continue;
            }
        }
    }

    /**
     * Checks to see if the given student exists in the SQLite DB
     * @author David Varney
     * @param object $student
     * @return bool
     */
    private function check_student($student)
    {
        $db = $this->get_sqlite_db();
        // We have to make sure the student table exists first
        // Let's see if the table already exists if it does then we don't need to proceed - Time Saver!
        $table_check = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='student';");
        // I don't like this but I guess this is how you pull results from a SELECT query with SQLite
        $tc_result = $table_check->fetch();
        // If the table does exist then proceed else we can return false
        if((int)$tc_result[0] > 0){
            $sql = "SELECT COUNT(*)
                      FROM student
                     WHERE student.id = $student->id";
            $query = $db->query($sql);
            $result = $query->fetch();
            /**
             * If the result is greater than zero then we know that
             * this student is already in the SQLite DB and no
             * further action is required.
             */
            if((int)$result[0] == 0){
                return false;
            }else{
                return true;
            }
        }else{
            return false;
        }
    }

    /**
     * Gathers data on a given student, constructs a formatted
     * array of the data and then inserts it into a SQLite DB.
     * @author David Varney
     * @param object $student
     * @return bool
     */
    private function export_student($student = null)
    {
        if($student){
            $data = $student->export();

            $formatted_data = array();
            // Lets add the basic student info separately from all of the related data
            foreach($data as $column => $value){
                if($column != 'related'){
                    $formatted_data['student']['columns'][] = $column;
                    $formatted_data['student']['rows'][$student->id][] = $value;
                }
            }
            // Now lets gather everything else via the handy-dandy iterate_through_data() function
            $this->iterate_through_data($data['related'], $formatted_data);

            // Lets get the relationship data and add it to our $formatted_data array
            $formatted_data = $this->add_relationship_data($student, $formatted_data);

            // Now that we've properly setup our data lets populate the SQLite DB
            $this->populate_sqlite_db($formatted_data);
            return true;
        }
        debug('Something is wrong with this Student. Below is output of the student variable');
        debug($student);
        return false;
    }

    /**
     * Simply returns a object that is our connection to our SQLite DB
     * @author David Varney
     * @return object $database PDO SQLite object
     */
    private function get_sqlite_db()
    {
        $path_to_sqlite = $GLOBALS['DATA_DIR'] . '/sqlite_test_data_students/test_data_students.db';

        try{
          //create or open the database
          $database = new PDO("sqlite:" . $path_to_sqlite);
        }
        catch(Exception $e){
          die($e->getMessage());
        }

        return $database;
    }

    /**
     * Helps to properly construct our data so that we
     * can easily pass it off to our SQLite populate method.
     * @author David Varney
     * @param array $data The array we're pulling data from
     * @param array $formatted_data The array we're constructing
     * @return void
     */
    private function iterate_through_data($data, &$formatted_data){
        foreach($data as $table => $table_records){
            $formatted_data[$table] = array('columns' => array(), 'rows' => array());
            foreach($table_records as $record_id => $record_attributes){
                foreach($record_attributes as $column => $value){
                    if(!in_array($column, $formatted_data[$table]['columns']) && $column != 'related'){
                        $formatted_data[$table]['columns'][] = $column;
                    }
                    if($column != 'related'){
                        $formatted_data[$table]['rows'][$record_id][] = $value;
                    }
                    if($column == 'related' && !empty($value)){
                        $this->iterate_through_data($value, $formatted_data);
                    }
                }
            }
        }
    }

    private function add_relationship_data($student, $formatted_data)
    {
        $student_relationship_type = DB::table('p_relationship')
            ->where('type', '=', 'Student')
            ->first();
        $r_constraints = array('object_a' => $student->id, 'object_a_type' => $student_relationship_type->id);
        $relationships = new db_recordset('relationship', $r_constraints);
        // If we have any content then we'll proceed and if not then we'll just return the $formatted_data variable
        if($relationships->count() > 0){
            $formatted_data['relationship'] = array('columns' => array(), 'rows' => array());
            $formatted_data['relationship_type'] = array('columns' => array(), 'rows' => array());
            // Now lets add the relationship columns and rows
            foreach($relationships as $relationship){
                $relationship_atts = $relationship->get_attributes();
                foreach($relationship_atts as $column => $value){
                    if(!in_array($column, $formatted_data['relationship']['columns'])){
                        $formatted_data['relationship']['columns'][] = $column;
                    }
                    $formatted_data['relationship']['rows'][$relationship->id][] = $value;
                }
            }

            // Now lets get the relationship_type records for each of the relationship records
            $relationship_ids = $relationships->get_recordset();
            $rt_constraints = array('relationship_id' => $relationship_ids);
            $relationship_types = new db_recordset('relationship_type', $rt_constraints);
            // Now lets add the relationship_type columns and rows
            foreach($relationship_types as $relationship_type){
                $relationship_type_atts = $relationship_type->get_attributes();
                foreach($relationship_type_atts as $column => $value){
                    if(!in_array($column, $formatted_data['relationship_type']['columns'])){
                        $formatted_data['relationship_type']['columns'][] = $column;
                    }
                    $formatted_data['relationship_type']['rows'][$relationship_type->id][] = $value;
                }
            }
        }
        return $formatted_data;
    }

    /**
     * Populates the SQLite DB with data passed via the $data variable
     * @author David Varney
     * @param array $data
     * @return bool
     */
    private function populate_sqlite_db($data)
    {
        // Initialize our SQLite DB
        $db = $this->get_sqlite_db();
        foreach($data as $table => $table_data){
            if(!empty($table_data['columns'])){
                // Let's see if the table already exists if it does then we don't need to proceed - Time Saver!
                $table_check = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$table';");
                // I don't like this but I guess this is how you pull results from a SELECT query with SQLite
                $tc_result = $table_check->fetch();
                // Table structure info
                $table_structure = query('DESCRIBE ' . $table);
                // Construct our table if it doesn't exist yet
                if($tc_result[0] == 0){
                    $columns_count = count($table_data['columns']);
                    // Lets first create our table and columns
                    $table_creation_string = 'CREATE TABLE ' . $table . ' (';
                    foreach($table_data['columns'] as $key => $column){
                        foreach($table_structure as $column_atts){
                            $column_separator = ', ';
                            if($columns_count - 1 == $key){
                                $column_separator = '';
                            }
                            if($column_atts['Field'] == $column){
                                // This will always be: INTEGER PRIMARY KEY
                                if($column == 'id' && $key == 0){
                                    $table_creation_string .= $column . ' INTEGER PRIMARY KEY' . $column_separator;
                                }elseif(strpos($column_atts['Type'], 'int(') !== FALSE){
                                    // We've come across an INTEGER column
                                    $table_creation_string .= $column . ' INTEGER' . $column_separator;
                                }else{
                                    // Everything else gets treated like a peasant
                                    $table_creation_string .= $column . ' TEXT' . $column_separator;
                                }
                            }
                        }
                    }
                    // Make sure we properly end our table creation string
                    $table_creation_string .= ');';
                    // Create the table!
                    if(!$db->query($table_creation_string)){
                        debug("Could not create a table for $table");
                    }
                }

                // Now we're going to construct and insert the rows for the current table

                // First we'll get a comma delimited string of the columns
                $table_columns_string = implode(', ', $table_data['columns']);
                foreach($table_data['rows'] as $record_id => $record_data){
                    // Begin constructing the beginning of the insert string
                    $row_data_string = "INSERT INTO $table (" . $table_columns_string . ") VALUES (";
                    $values_count = count($record_data);
                    foreach($record_data as $rd_key => $rd_value){
                        $value_separator = ', ';
                        if($values_count - 1 == $rd_key){
                            $value_separator = '';
                        }
                        // See if the value is suppose to be wrapped in quotes or
                        // not by comparing the value to the table structure. Integers
                        // don't get quotes and everything else does.
                        if(strpos($table_structure[$rd_key]['Type'], 'int(') !== FALSE){
                            if(!isset($rd_value) || empty($rd_value)){
                                $rd_value = 0;
                            }
                            $row_data_string .= $rd_value . $value_separator;
                        }else{
                            $row_data_string .= '"' . $rd_value . '"' . $value_separator;
                        }
                    }
                    $row_data_string .= ');';
                    // Now we need to insert the row into the DB
                    if(!$db->query($row_data_string)){
                        debug("Could not insert record (ID: $record_id) into the $table table for student (ID: $student->id). SQL INSERT STRING: $row_data_string");
                    }
                }
            }
        }
        return true;
    }
}