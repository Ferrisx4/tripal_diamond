<?php

/*
 * Main theme for displaying the results of a submitted Diamond job
 */
?>
<!--<script>
Drupal.behaviors.diamondSetTimeout = {
  attach: function (context, settings) {
    setTimeout(function(){
       window.location.reload(1);
    }, 5000);
  }
};

</script>
-->
<?php
drupal_set_title('Status of Job #'.$job_id);
$outputPath = '/var/www/html/Drupal/sites/default/files/tripal/jobs/';



?>
<hr />

Current status of your job in the Tripal Job System: <?php echo $status; ?>
<br />
<!-- Current status of your job on the Remote Server: <?php //echo $remote_status; ?> -->


<!-- Show results if job was successful -->
<?php
$outputPath = DRUPAL_ROOT.'/sites/default/files/tripal/jobs/';
if ($status == 'debug')
{
    echo "This was a debugging run. The job was not submitted. There are no results and there will never be any.";
}
else if ($status == 'Completed')
{   
    //Get the details from this job (from database: tseq_job_information)
    $job_details = tseq_get_job_information($job_id);
    
    //echo "OUT: ".filesize($outputPath.$job_id.'/STDOUT.txt');
    //echo "ERR: ".filesize($outputPath.$job_id.'/STDERR.txt');
    $empty = 0;
    
    
    // Summary Information
    // Count sequences in query
    $query_file_no_path = explode('/',$job_details['sequence_file']);
    $query_file = $outputPath.$job_id.'/'.$query_file_no_path[count($query_file_no_path)-1];
    drupal_set_message("Sequence count: ".tseq_get_query_sequence_count($query_file));
    
    // Matches found
    $output_file = $outputPath.$job_id.'/STDOUT.txt';
    $matches_found = tseq_get_matches_count($output_file);
    drupal_set_message("Matches found: ".$matches_found);
    
    // If both output files are present
    if (file_exists($outputPath.$job_id.'/STDOUT.txt') && file_exists($outputPath.$job_id.'/STDERR.txt'))
    {   
        //Display output if there is any data to show in STDOUT
        if (filesize($outputPath.$job_id.'/STDOUT.txt') > 0)
        {
            echo "Your job results: <br /><br />";
            $jobResults = file($outputPath.$job_id.'/STDOUT.txt');
            //$jobResults2 = explode("\t", $jobResults[0]);
            //
            // Check for no hits found
            $resultsFound = TRUE;
            foreach($jobResults as $resultLine)
            {
                if ($resultLine == "***** No hits found *****")
                {
                    $resultsFound = FALSE;
                }
            }
            if ($resultsFound)
            {
                $headers = array('Query Label',
                    'Target',
                    '% Identity',
                    'Alignment Length',
                    'Mismatches',
                    'Gap opens',
                    'Start position (query)',
                    'End position (query)',
                    'Start position (database)',
                    'End position (database)',
                    'E-value',
                    'Bit score');
                $rows = array();
                
                foreach($jobResults as $key => $resultLine)
                {
                    //Possible pumpkin
                    if ($key < 10)
                    {
                        $rows[$key] = explode("\t", $resultLine);
                    }
                }
                //$rows[0] = array('turdget','ident','align length','mismath','gap','start q','end q','start t','end t','ev','bit');
                $table_vars = array(
                    'header'      => $headers, 
                    'rows'        => $rows
                );
                
                echo theme('table', $table_vars);
                
                /*
                 *  Download section
                 *   Some of these are dynamic and will only be available if certain conditions are met
                 */
                echo "<ul>";
                // Do we need to link to the original sequence file?
                // 1. Did the user use one of the system sequence databases? (database_file_type == 'database')
                if ($job_details['database_file_type'] == 'database')
                {
                    // 2. Do we have a web_location in order to serve the file?
                    $db_id = tseq_get_db_id_by_location($job_details['database_file']);
                    $db_details = tseq_get_db_info($db_id);
                    
                    if ($db_details['web_location'] != '')
                    {
                        echo "<li>Click <a href=\"".$db_details['web_location']."\">here</a> to download the original sequence file</li>";
                    }
                }
                
                
                echo "<li>Click <a href=\"download/$job_id/results\">here</a> to download these results</li>";
                echo "<li>Click <a href=\"download/$job_id/query\">here</a> to download your original query</li>";
                // Only show a link for download if the user uploaded/pasted 
                // their target database (in which case it'll exist locally on the webserver)
                if ($job_details['database_file_type'] != 'database')
                {
                    echo "<li>Click <a href=\"download/$job_id/target\">here</a> to download your selected target database (index)</li>";
                }
                echo "<ul><hr />";
            }
            else {
                echo "No hits were found";
            }
        }
        //Output file exists but is empty
        else
        {
            $empty += 1;
        }
        //Display any errors if STDERR has data
        if (filesize($outputPath.$job_id.'/STDERR.txt') > 0)
        {
                   echo "The following errors were reported: ";
                   readfile($outputPath.$job_id.'/STDERR.txt'); 
        }
        //Error file exists but is empty
        else
        {
            $empty += 1;
        }
        
        //Files exist but are empty
        if ($empty == 2)
        {
            echo "The requested job has no results. Perhaps there was an error";
        }
    }
    //The files do not exist (possibly due to job error or incorrectly specified job_id
    else
    {
        echo "The results of that job can not be found. Be aware that results for Diamond jobs expire after 60 days";
    }
}
else
{
  // Job evidently not completed. Keep checking
  $meta = array(
    '#tag' => 'meta',
    '#attributes' => array(
      'http-equiv' => 'refresh',
      'content' =>  '10',
    )
  );
  drupal_add_html_head($meta, 'tripal_job_status_page');
}

//echo "On the remote server, your job is: ".TripalRemoteSSH::isJobRunning(tripal_get_job($job_id));
        //TripalRemoteSSH::isJobRunning(tripal_get_job($job_id));;