<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookSDKException;
use Facebook\FacebookRequestException;
use Facebook\FacebookAuthorizationException;
use Facebook\GraphObject;
use Facebook\GraphSessionInfo;

$appid = ''; // your AppID
$secret = ''; // your secret
$redirect_url = 'http://xfactor.a1.ro/jurat/';
FacebookSession::setDefaultApplication($appid, $secret);

$helper = new FacebookRedirectLoginHelper($redirect_url);
try {
    $session = $helper->getSessionFromRedirect();
} catch(FacebookRequestException $ex) {
  // When Facebook returns an error
  echo $ex->getMessage();
} catch(\Exception $ex) {
  // When validation fails or other local issues
  echo $ex->getMessage();
}

if( isset($_SESSION['token'])){
    // We have a token, is it valid?
    $session = new FacebookSession($_SESSION['token']);

    try
    {
        $session->Validate($appid ,$secret);
    }
    catch( FacebookAuthorizationException $ex)
    {
        // Session is not valid any more, get a new one.
        $session = $helper->getSessionFromRedirect();
    }
}?>

<?php require_once 'views/header.php';?>

<?php if ( isset( $session ) && $session ):?>
<?php

// set the PHP Session 'token' to the current session token
$_SESSION['token'] = $session->getToken();
// SessionInfo
$info = $session->getSessionInfo();
// getAppId
echo "Appid: " . $info->getAppId() . "<br />";
$req = new FacebookRequest($session, 'GET', '/me');
$user_profile = $req->execute()->getGraphObject();
$user = $user_profile->getProperty('id');


if (isset($_GET['save'])):
    $response = (new FacebookRequest(
      $session, 'POST', '/me/photos', array(
        'source' => new CURLFile(__DIR__ .'/pics/avatar-' . $user . '.jpg', 'image/jpeg2wbmp'),
        'message' => 'Test upload'
      )
    ))->execute()->getGraphObject();
    echo "Posted with id: " . $response->getProperty('id') . "<br />";
    echo '<a class="final" href="http://www.facebook.com/photo.php?fbid=' . $response->getProperty('id') . '&makeprofile=1" target="_blank">SALVEAZA POZA</a>';?>
<?php else:?>
    <div class="upper logged">
       <div class="clearfix">
           <div class="fleft">
                <p>Poza ta pe facebook va arăta astfel:</p>
           </div>
           <div class="fleft pic">
                <?php Image('http://graph.facebook.com/' . $user . '/picture?type=large', '1:1', '300x', $user);?>
            </div>
            <div class="fleft save"><a href="?save=1">Accepta poza</a><p>Poza va fi acum salvată in albumul tău de facebook în mod privat, vizibilă doar de către tine.</p></div>
        </div>
    </div>
?>

<?php endif?>

<?php else: ?>
<div class="upper"><a href = "<?= $helper->getLoginUrl(['publish_actions']) ?>" id="fbLogin">Login</a></div>
<?php endif;

require_once 'views/footer.php';

function Image($image, $crop = null, $size = null, $user) {
    $image = ImageCreateFromString(file_get_contents($image));

    if (is_resource($image) === true) {
        $x = 0;
        $y = 0;
        $width = imagesx($image);
        $height = imagesy($image);



        if (is_null($crop) === true) {
            $crop = array($width, $height);
        } else {
            $crop = array_filter(explode(':', $crop));

            if (empty($crop) === true) {
                    $crop = array($width, $height);
            } else {
                if ((empty($crop[0]) === true) || (is_numeric($crop[0]) === false)) {
                        $crop[0] = $crop[1];
                } else if ((empty($crop[1]) === true) || (is_numeric($crop[1]) === false)) {
                        $crop[1] = $crop[0];
                }
            }

            $ratio = array(0 => $width / $height, 1 => $crop[0] / $crop[1]);

            if ($ratio[0] > $ratio[1]) {
                $width = $height * $ratio[1];
                $x = (imagesx($image) - $width) / 2;
            }

            else if ($ratio[0] < $ratio[1]) {
                $height = $width / $ratio[1];
                $y = (imagesy($image) - $height) / 2;
            }

        }


        if (is_null($size) === true) {
            $size = array($width, $height);
        }

        else {
            $size = array_filter(explode('x', $size));

            if (empty($size) === true) {
                    $size = array(imagesx($image), imagesy($image));
            } else {
                if ((empty($size[0]) === true) || (is_numeric($size[0]) === false)) {
                        $size[0] = round($size[1] * $width / $height);
                } else if ((empty($size[1]) === true) || (is_numeric($size[1]) === false)) {
                        $size[1] = round($size[0] * $height / $width);
                }
            }
        }

       $result = ImageCreateTrueColor($size[0], $size[1]);

        if (is_resource($result) === true) {
            ImageSaveAlpha($result, true);
            ImageAlphaBlending($result, true);
            ImageFill($result, 0, 0, ImageColorAllocate($result, 255, 255, 255));
            ImageCopyResampled($result, $image, 0, 0, $x, $y, $size[0], $size[1], $width, $height);

            ImageInterlace($result, true);

			$file1 = $result;
			$file2 = 'photo_overlayer.png';

			// Second image (the overlay)
			$overlay = imagecreatefrompng($file2);

			// We need to know the width and height of the overlay
			list($widthx, $heightx, $type, $attr) = getimagesize($file2);

			// Apply the overlay
			imagecopy($file1, $overlay, 0, 0, 0, 0, $widthx, $heightx);
			imagedestroy($overlay);

			// Output the results

			imagejpeg($file1, 'pics/avatar-'.$user.'.jpg', 90);
			echo '<img src="pics/avatar-'.$user.'.jpg" alt="">';

			imagedestroy($file1);

            //ImageJPEG($result, null, 90);
        }
    }

    return false;
}
