<?php

	class smsgatewaycenter extends AktuelSms {

		function __construct($message, $gsmnumber) {
			$this->message = $this->utilmessage($message);
			$this->gsmnumber = $this->utilgsmnumber($gsmnumber);
		}

		function send() {
			if ($this->gsmnumber == "numbererror") {
				$log[] = ("Number format error." . $this->gsmnumber);
				$error[] = ("Number format error." . $this->gsmnumber);
				return null;
			}
			$params = $this->getParams();

			//Base URL
			//Composed of initial common portion of URL of SMS Gateway Provider
			$baseurl = "https://www.smsgateway.center/SMSApi/rest/send";

			//Sender ID, While using route 4 sender id should be 6 characters long.
			$senderId = trim($params->senderid);
			$senderId = substr($senderId, 0, 6);

			//Define Message Type
			// Send Unicode Message
			// Yes = 1 / No = 0 (if No, Default is English)
			if (ctype_digit($params->unicode)) {
				$unicodeSupport = $params->unicode;
			} else {
				$unicodeSupport = 0;   //Unicode support is disbaled if not defined in settings
			}

			$text = urlencode($this->message);
			$to = $this->gsmnumber;

			$result = @file_get_contents($baseurl . '?'
					. 'userId=' . $params->user . ''
					. '&password=' . $params->pass . ''
					. '&sendMethod=simpleMsg'
					. '&msgType=dynamic'
					. '&format=json'
					. '&mobile=' . urlencode($this->gsmnumber) . ''
					. '&senderId=' . urlencode($params->senderid) . ''
					. '&msg=' . urlencode($this->message));
			$result = json_decode($result);

			if ($result->status == 'success') {
				$log[] = ("Message sent.");
			} else {
				$log[] = ("Error.");
				$error[] = ("Check status, looks like problem with a connection or credentials. Reason: " . $result->reason);
			}

			return array(
				'log' => $log,
				'error' => $error,
				'msgid' => $send[0],
			);
		}

		function balance() {
			$params = $this->getParams();
			if ($params->user && $params->pass) {
				$result = @file_get_contents('https://www.smsgateway.center/SMSApi/rest/balanceValidityCheck?'
						. 'userId=' . $params->user . ''
						. '&password=' . $params->pass . ''
						. '&format=json');
				$result = json_decode($result);
				if ($result->status == 'success') {
					return $result->smsBalance;
				} else {
					return null;
				}
			} else {
				return null;
			}
		}

		function report($msgid) {
			$params = $this->getParams();
			if ($params->user && $params->pass && $msgid) {
				$baseurl = 'https://www.smsgateway.center/library/api/self/SMSDlr/';
				$url = "$baseurl?userId=$params->user&password=' . $params->pass&transId=$msgid&format=json";
				$result = @file_get_contents($url);
				$result = json_decode($result);
				if ($result->status == 'success') {
					if ($result->DLRReport->Status == 'DELIVERED' || $result->DLRReport->Cause == 'Delivered') {
						return "success";
					} else {
						return "error";
					}
				} else {
					null; // Problem with a connection, not with SMS.
				}
			} else {
				return null;
			}
		}

		//You can spesifically convert your gsm number. See netgsm for example
		function utilgsmnumber($number) {
			if (strlen($number) == 10) {
				$number = '91' . $number;
			}

			if (substr($number, 0, 2) != "91") {
				return "numbererror";
			}

			return $number;
		}

		//You can spesifically convert your message
		function utilmessage($message) {
			return $message;
		}

	}

	return array(
		'value' => 'smsgatewaycenter',
		'label' => 'SMS Gatewat Center (India)',
		'fields' => array(
			'user', 'pass'
		)
	);
	