<?php

namespace Sloway;

class facebook_api
{
  public static function generateEventId($event_name, $content_type, $id)
  {
    return $event_name . "_" . $content_type . "_" . $id;
  }

  public static function trueIfSettingEnabled($name)
  {
    $meta_pixel_id = MetaPixelAndConversionApi::get("meta_pixel_id");
    if ($name == "meta_pixel") {
      $enable_meta_pixel = MetaPixelAndConversionApi::get("enable_meta_pixel");
      return $meta_pixel_id && $enable_meta_pixel;
    } else if ($name == "conversion_api") {
      $conversion_api_access_token = MetaPixelAndConversionApi::get("conversion_api_access_token");
      $enable_conversion_api = MetaPixelAndConversionApi::get("enable_conversion_api");
      return $meta_pixel_id && $conversion_api_access_token && $enable_conversion_api;
    } else {
      return false;
    }
  }

  public static function generateClientEventScript($event_name, $content_type, $id, $additionalParams = array(), $value = '0', $wrap = true)
  {
	if (Settings::get("facebook_pixel")) {
//    if (self::trueIfSettingEnabled("meta_pixel")) {
      $script = "";
      $script .= $wrap ? "<script>" : "";
      $script .= "fbq('track', '" . $event_name . "', {";
      foreach ($additionalParams as $name => $param) {
        if (!is_array($param)) {
          $script .= "" . $name . ": '" . $param . "',";
        } else {
          $scriptArr = $name . ": ";
          $scriptArr .= "[";
          foreach ($param as $val) {
            $scriptArr .= $val . ",";
          }
          $scriptArr .= "],";
          $script .= $scriptArr;
        }
      }
      $script .= "value: '" . $value . "',";
      $script .= "},";
      $script .= "{";
      $script .= "eventID: '" . self::generateEventId($event_name, $content_type, $id) . "'";
      $script .= "}";
      $script .= ");";
      $script .= $wrap ? "</script>" : "";
      return $script;
    }
  }

  public static function sendConversion($event_name, $content_type, $content_id, $content_name = "", $value = 0, $currency = "EUR", $opt_out = false, $country = "si")
  {
	$access_token = Settings::get("facebook_conv");
	$meta_pixel_id = Settings::get("facebook_pixel");

    //if (self::trueIfSettingEnabled("conversion_api")) {
	//
    //  $conversion_api_access_token = MetaPixelAndConversionApi::get("conversion_api_access_token");
    //  $meta_pixel_id = MetaPixelAndConversionApi::get("meta_pixel_id");
	if ($meta_pixel_id && $access_token) {
      $api_url = 'https://graph.facebook.com/v13.0/' . $meta_pixel_id .  '/events';
      //$access_token = $conversion_api_access_token;

      $event = [];
      $event["data"][0] = array(
        "event_name" => $event_name,
        "event_time" => time(),
        "event_id" => self::generateClientEventScript($event_name, $content_type, $content_id),
        "user_data"  => array(
          "country" => hash('sha256', $country),
          "client_user_agent" => $_SERVER["HTTP_USER_AGENT"],
          "client_ip_address" => $_SERVER['REMOTE_ADDR'] ?: ($_SERVER['HTTP_X_FORWARDED_FOR'] ?: $_SERVER['REMOTE_ADDR']),
        ),
        "custom_data" => array(
          "currency" => $currency,
          "value" => $value,
          "content_ids" => array($content_id),
          "content_type" => $content_type,
          "content_name" => $content_name,
        ),
        "opt_out" => $opt_out,
        "action_source" => "website",
        "event_source_url" => url::site(),
      );

      $ch = curl_init($api_url);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token,
      ]);

      $response = curl_exec($ch);

      /*
      $log = mlClass::create("conversion_api_logs");
      $log->request = json_encode($event);
      $log->response = json_encode($response);
      $log->time = time();
      $log->save();
      */

      if ($response === false) {
        echo 'Curl error: ' . curl_error($ch);
      } else {
        $responseData = json_decode($response);
      }
      curl_close($ch);
    }
  }
}
