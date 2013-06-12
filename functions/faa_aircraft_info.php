<?php
/**
 * Given an aircraft's N Number (Tail Number) we utilize phpQuery to
 * access the FAA's website to gather detailed information about the
 * aircraft. We could access FlightAware.com's API to achieve this
 * but unfortunately FlightAware.com charges every time we access
 * their API. By using phpQuery we can scrape the aircraft's publicly
 * available registration info directly off of the FAA's
 * website/webpage that has this info listed.
 *
 * @author David Varney
 */

// Include the phpQuery vendor model
require_once('PATH_TO_VENDOR_DIR_HERE/vendor/phpQuery/phpQuery.php');

/**
 * Retrieves an aircraft's info for a given N Number
 * via the FAA website and phpQuery
 *
 * @author David Varney
 * @param str $n_number The N Number/Tail Number of an aircraft
 * @return array $aircraft_insert_array The aircraft's collected data
 */
function get_aircraft_info($n_number = null){
    if($n_number){
        // Make sure phpQuery allows access to the domain that we need to access
        phpQuery::$ajaxAllowedHosts[] = array('registry.faa.gov');
        // The FAA's URL for viewing an aircraft given it's N Number
        $url = 'http://registry.faa.gov/aircraftinquiry/NNum_Results.aspx?NNumbertxt=' . $n_number;
        // Feeding the webpage to phpQuery
        $document = phpQuery::newDocumentFile($url);
        /**
         * The following array's keys are the aircraft's attributes and
         * the value's is the element id of the corresponding values
         * found on the FAA aircraft webpage.
         */
        $filter_array = array(
            'serial_number'                 => '#content_lbSerialNo',
            'manufacturer'                  => '#content_lbMfrName',
            'aircraft_type'                 => '#content_Label11',
            'registration_type'             => '#content_lbTypeReg',
            'certificate_issue_date'        => '#content_lbCertDate',
            'certificate_expiration_date'   => '#content_Label9',
            'engine_type'                   => '#content_lbTypeEng',
            'mode_s_code'                   => '#content_lbModeSCode',
            'engine_manufacturer'           => '#content_lbEngMfr',
            'engine_model'                  => '#content_lbEngModel',
            'classification'                => '#content_lbClassification',
            'category'                      => '#content_lbCategory1',
            'airworthiness_date'            => '#content_lbAWDate',
            );
        // Our array that will be collecting all of our aircraft data
        $aircraft_insert_array = array();
        /**
         * Loops through our filter array and accesses the phpQuery
         * $document variable and collects our data
         */
        foreach($filter_array as $column => $filter){
            $aircraft_insert_array[$column] = $document[$filter]->html();
        }

        return $aircraft_insert_array;
    }
    return false;
}