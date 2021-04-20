<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BaseController extends AbstractController
{
    private $dbalconn;

    public function setConn($dbalconn)
    {
        $this->dbalconn = $dbalconn;
    }

    public function getConn()
    {
        return $this->dbalconn;
    }

    public function getBaseUrl()
    {
        if (stristr($_SERVER['SERVER_NAME'], 'localhost')) {
            $baseUrl = 'http://localhost:3000';
        } elseif (stristr($_SERVER['SERVER_NAME'], 'dev')) {
            $baseUrl = 'https://dev.ratemyshopper.com';
        } else {
            $baseUrl = 'https://www.ratemyshopper.com';
        }

        return $baseUrl;
    }

    public function getShareCode()
    {
        if (stristr($_SERVER['SERVER_NAME'], 'localhost')) {
            $shareCode = '5903355453ad0c00117a420b';
        } elseif (stristr($_SERVER['SERVER_NAME'], 'dev')) {
            $shareCode = '5903355453ad0c00117a420b';
        } else {
            $shareCode = '59025a11e056200014ee5981';
        }

        return $shareCode;
    }

    public function checkUserSession()
    {
        $dbalconn = $this->dbalconn;
        if (!isset($_SESSION['user'])) {
            return false;
        } else {
            $sql = '
                SELECT *
                FROM `user`
                WHERE token = :token
            ';
            $stmt = $dbalconn->prepare($sql);
            $stmt->bindValue('token', $_SESSION['user']['token']);
            $stmt->execute();
            $users = $stmt->fetchAll();
            if (sizeof($users) == 0) {
                return false;
            } else {
                return true;
            }
        }
    }

    public function compressCrypt($string)
    {
        return base64_encode(gzcompress($string, 9));
    }

    public function decompressCrypt($string)
    {
        return gzuncompress(base64_decode($string));
    }

    public function isImage($url)
    {
        $params = ['http' => [
                    'method' => 'HEAD',
                 ]];
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            return false;
        }  // Problem with url

        $meta = stream_get_meta_data($fp);
        if ($meta === false) {
            fclose($fp);

            return false;  // Problem reading data from url
        }
    }

    public function checkUser($returnJson = false, $returnUrl = '', $message = '')
    {
        $baseUrl = $this->getBaseUrl();
        if (!checkUserSession()) {
            if ($returnJson) {
                echo json_encode(['error' => 'Session Expired']);
                exit;
            } else {
                $returnstring = '';
                if ($returnUrl != '') {
                    $returnstring = '?return='.urlencode($returnUrl);
                }
                session_start();
                $_SESSION['return'] = $returnUrl;
                $_SESSION['message'] = $message;

                return $this->redirectToRoute('index_login', ['return' => $returnUrl]);
            }
        } else {
            if ($returnUrl == '') {
                $currentdate = date('m/d/Y h:i:s a', time());
                if ($_SESSION['user']['activated'] == 1 && strtotime($_SESSION['user']['expiration_date']) < strtotime($currentdate)) {
                    $dbalconn = $this->dbalconn;
                    $dbalconn->update('user', ['activated' => 0], ['id' => $_SESSION['user']['id']]);
                    $_SESSION['user']['activated'] = 0;

                    return $this->redirectToRoute('user_profile');
                } elseif ($_SESSION['user']['activated'] == 0 && strtotime($_SESSION['user']['expiration_date']) < strtotime($currentdate)) {
                    return $this->redirectToRoute('user_profile');
                }
            }
        }
    }

    public function getLocalDateTime($cur_lat, $cur_long, $date)
    {
        if ($cur_lat == '' || $cur_long == '') {
            return $date;
        }

        $timezone_ids = DateTimeZone::listIdentifiers();

        if ($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {
            $time_zone = '';
            $tz_distance = 0;

            //only one identifier?
            if (count($timezone_ids) == 1) {
                $time_zone = $timezone_ids[0];
            } else {
                foreach ($timezone_ids as $timezone_id) {
                    $timezone = new DateTimeZone($timezone_id);
                    $location = $timezone->getLocation();
                    $tz_lat = $location['latitude'];
                    $tz_long = $location['longitude'];

                    $theta = $cur_long - $tz_long;
                    $distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat)))
                        + (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
                    $distance = acos($distance);
                    $distance = abs(rad2deg($distance));
                    // echo '<br />'.$timezone_id.' '.$distance;

                    if (!$time_zone || $tz_distance > $distance) {
                        $time_zone = $timezone_id;
                        $tz_distance = $distance;
                    }
                }
            }

            //echo $date. "\n";
            $datetime = new DateTime($date);
            //echo $datetime->format('Y-m-d H:i:s') . "\n";
            $localTimezone = new DateTimeZone($time_zone);
            $datetime->setTimezone($localTimezone);
            //echo $datetime->format('Y-m-d H:i:s') . "\n";
            //exit;
            return $datetime->format('Y-m-d H:i:s');
        } else {
            return $date;
        }
    }
}
