<?php
require_once("../internal/app.php");

// Fallback code to handle empty POST:
if(empty($_POST))
{
	# process headers if apache isn't around
	if ( ! function_exists('apache_request_headers'))
	{
		$headers = [];
		foreach ($_SERVER as $name => $value)
		{
			if (substr($name, 0, 5) == 'HTTP_')
			{
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
	}
	else
	{
		# just use apache's request headers
		$headers = apache_request_headers();
	}

	// grab the oauth information:
	$authHeaders = $headers['Authorization'];
	$authHeaders = str_replace('OAuth ', '', $authHeaders);
	$authHeaders = explode(',', $authHeaders);
	$oauthData   = array();
	foreach($authHeaders as $authHeader)
	{
		$authHeader = explode('=', $authHeader);
		$oauthData[$authHeader[0]] = str_replace('"', '', urldecode($authHeader[1]));
	}
	$body = file_get_contents("php://input");
	// shove the oauth header info into the post, since the OAuth classes rely on that
	foreach($oauthData as $oauthKey => $oauthValue)
	{
		$_POST[$oauthKey] = $oauthValue;
	}
}

$valid = false;
$description = "Invalid Oauth Signature";
$ltiData = new \lti\Data($_POST);
$outMsgId = 0;
$score = null;
$sourceId = null;
$inMsgId = null;
$body = @file_get_contents('php://input');

try
{
	$valid = \lti\OAuth::validateLtiPassback(\AppCfg::MATERIA_LTI_KEY, \AppCfg::MATERIA_LTI_SECRET, \AppCfg::MATERIA_LTI_TIMELIMIT);
}
catch (\lti\Exception $e)
{
	profile('lti',"'invalid-lti-passback-received', '".time()."'");
	trace($e->getMessage(), true);
	trace($body, true);
}

if($valid)
{
	// process the incoming data
	$xml      = simplexml_load_string($body);
	$inMsgId  = (string) $xml->imsx_POXHeader->imsx_POXRequestHeaderInfo->imsx_messageIdentifier;
	$sourceId = (string) $xml->imsx_POXBody->replaceResultRequest->resultRecord->sourcedGUID->sourcedId;
	$score    = (string) $xml->imsx_POXBody->replaceResultRequest->resultRecord->result->resultScore->textString;
	$outMsgId = uniqid();

	$sm = \obo\ScoreManager::getInstance();
	list($valid, $description) = $sm->submitLTIQuestion($sourceId, 'materia', $score);
}

// build response
$success = $valid ? "success" : "failure";
$smarty = \rocketD\util\Template::getInstance();
$smarty->assign('messsageIdOut', $outMsgId);
$smarty->assign('messsageIdIn', $inMsgId);
$smarty->assign('description', $description);
$smarty->assign('success', $success);
$response = $smarty->fetch(\AppCfg::DIR_BASE . \AppCfg::DIR_TEMPLATES . 'lti-replaceResult-response.tpl');

header('Content-Type: application/xml');
echo($response);
profile('lti-score', "'".time()."',materia','$sourceId','$score','$description','$success'");
