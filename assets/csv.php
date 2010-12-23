<?php
require_once (dirname( __FILE__ )."/../internal/app.php");
switch($_GET['function'])
{
	case 'scores':
		if ($_GET['instID'] > 0 && strlen($_GET['filename']) > 0)
		{
			$lor = \obo\API::getInstance();
			$scores = $lor->getScoresForInstance($_GET['instID']);
			$UM = namespace obo;::getInstance();
			if (is_array($scores))
			{
				session_write_close();
				header("Pragma: public");
				header("Expires: 0"); // set expiration time
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Disposition: attachment; filename=\"{$_GET['filename']}.csv\"");
				echo "User ID,Last Name,First Name,MI,Score\r\n";
				
				usort($scores, "compareFunction");
				
				foreach ($scores as $user)
				{
					$score = getCountedScore($user['attempts'], $_GET['method']);
					if($score != -1) echo $UM->getUserName($user['userID']).','.$user['user']['last'].','.$user['user']['first'].','.$user['user']['mi'].','.$score."\r\n";
				}
				
				exit ();
			}
		}
	break;
	default:
		break;
}

function compareFunction($a, $b)
{
	$n1 = $a['user']['last'].$a['user']['first'].$a['user']['mi'];
	$n2 = $b['user']['last'].$b['user']['first'].$b['user']['mi'];
	
	return strcmp($n1, $n2);
}

function getCountedScore($scores, $method)
{
	//Filter out unsubmitted scores:
	$attempts = array();
	foreach($scores as $scoreData)
	{
		if($scoreData['submitted']) $attempts[] = $scoreData;
	}
	if(count($attempts) == 0) return -1;
	
    switch($method)
    {
        case 'h': //Highest:
            $highest = 0;
            foreach ($attempts as $scoreData)
            {
                $curScore = $scoreData['score'];
                if ($curScore > $highest)
                    $highest = $curScore;
            }
            return $highest;
    	case 'm': //Mean:
     	   $total = 0;
    		foreach ($attempts as $scoreData)
   			{
        		$total += $scoreData['score'];
			}
			return $total/count($scores);
		case 'r': //Recent:
    		return $attempts[count($attempts)-1]['score'];
		}
	exit();
}

header('HTTP/1.0 404 Not Found');

?>
