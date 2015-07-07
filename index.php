<?php
require 'facebook-php-sdk/src/facebook.php';

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

function Image($image, $crop = null, $size = null, $user) {
    $image = ImageCreateFromString(file_get_contents($image));

    if (is_resource($image) === true) {
        $x = 0;
        $y = 0;
        $width = imagesx($image);
        $height = imagesy($image);

        /*
        CROP (Aspect Ratio) Section
        */

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

        /*
        Resize Section
        */

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
			$file2 = 'sim33.png';

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

$facebook = new Facebook(array(
  'appId'  => '180592005382838',
  'secret' => '6adcf05420ee2523fadcb9c15ba2f0d0',
));

$loginUrl = $facebook->getLoginUrl(array ( 
        'display' => 'page',
        'redirect_uri' => 'http://liked.ro/apps/jurat-xfactor/'
        ));

$user = $facebook->getUser();
//echo $user. '</br>';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Fii cel de-al patrulea jurat X Factor!">
    <meta property="og:url" content="http://liked.ro/apps/jurat-xfactor/"/>
    <meta property="og:title" content="Devino Jurat X Factor!"/>
    <meta property="og:description" content="X Factor! Show la puterea X!"/>
    <!-- <meta property="og:image" content="http://liked.ro/apps/hai-simona/img/appicon.jpg"/> -->
    <title>Devino Jurat X Factor!</title>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="css/main-style.css">
</head>
<body>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.0";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<div id="content">
<div class="topfix"></div>
<?
if ($user) {
	?>
   <div class="upper logged">
   <div class="clearfix">
   <div class="fleft">
   <p>Poza ta pe facebook va arăta astfel:</p>
   </div>
   <div class="fleft pic">
   <?php
	Image('http://graph.facebook.com/' . $user . '/picture?type=large', '1:1', '300x', $user);
	?>
	</div>
	<div class="fleft save">
	<?php
	if(isset($_GET["save"])) {
		$facebook->setFileUploadSupport(true);

		//Create an album
		$album_details = array(
		        'message'=> '',
		        'name'=> 'Hai Simona!'
		);
		$create_album = $facebook->api('/me/albums', 'post', $album_details);

			//Get album ID of the album you've just created
		  $album_uid = $create_album['id'];
			//Upload a photo to album of ID...
		  $photo_details = array('message'=> 'Hai Simona! O susținem pe Simona Halep in finala Roland Garros! http://liked.ro/apps/hai-simona');
		  $file='pics/avatar-'.$user.'.jpg'; //Example image file
		  $photo_details['image'] = '@' . realpath($file);

      
		  $upload_photo = $facebook->api('/'.$album_uid.'/photos', 'post', $photo_details);

		  echo '<p class="final">ATENȚIE! <span>Poza va fi acum salvată ca poză de profil. Vei fi redirecționat către facebook.</span></p><a class="final" href="http://www.facebook.com/photo.php?fbid=' . $upload_photo['id'] . '&makeprofile=1" target="_blank">SALVEAZĂ POZA</a>';
	      //echo $user;
      	} else {
      	?>
      	<a href="?save=1">ACCEPTĂ POZA</a>
      	<p>Poza va fi acum salvată in albumul tău de facebook în mod privat, vizibilă doar de către tine.</p>
      	<?php } ?>
      	</div>
      </div>
      </div>
      </div>
      <?php


} else {
    ?>
    <div class="upper">
    <?php
    $loginUrl = $facebook->getLoginUrl(array ( 
        'display' => 'page',
        'redirect_uri' => 'http://liked.ro/apps/jurat-xfactor/'
        ));
    echo '<a href = "'.$loginUrl.'" id="fbLogin">Sustine-o pe Simona Halep! Hai Simona!</a> ';
    ?>
    </div>
    <?php
 }

?>
<div class="banda"></div>
<!-- <div class="statement">
	<p><span>O sustinem pe Simona Halep in finala turneului Roland Garros, 07 iunie 2014! Hai Simona, Hai România!<br /><strong></span> Acesta este un proiect individual, neafiliat unui brand sau unei campanii.</p></strong>
</div>-->
<div style="margin:50px auto 0 auto; width:120px; display:block;" class="fb-share-button" data-href="http://liked.ro/apps/hai-simona" data-type="button_count"></div>
</div>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-32495049-1', 'liked.ro');
  ga('send', 'pageview');

</script>
</body>
</html>