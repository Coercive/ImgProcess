Coercive ImageEngine
====================

- The ImageEngine allows you to easily resize your images in PHP with util options like "cover" which allows to cover a dimensioned area provided just as would do the css property of the same name; Or the "crop" that allows you to crop a specific area of ​​your image.


Get
---
```
composer require coercive/imageengine
```

Class
-----
```php
use Coercive\Utility\Csv\ImageEngine\ImageEngine;

# INIT
$oImg = new ImageEngine;

# QUALITY (optional : default jpg 60 /  png 0)
$oImg
	->setJpgQuality(50)
	->setPngCompression(5);

# EXAMPLE SET
$oImg
	->setOverwriting(true)
	->setInputPath('source/path/image_name.jpg')
	->setOutputPath('output/path/new_image.jpg')
	->setSourceCoordinate('RIGHT', 'BOTTOM')
	->setOutputSize(1000, 1000);

# PROCESS
$bVerif = $oImg->sameSize();
// or
$bVerif = $oImg->myOwnSize(500);
// or
$bVerif = $oImg->crop();
// or
$bVerif = $oImg->cover();

# HANDLE ERRORS
if( !$bVerif ) {
	if( $aError = $oImg->getError() ) {
		foreach ($aError as $sMessage) { echo "<p>$sMessage</p>"; }
		die('Shutdow After Process');
	} else {
		die('Shutdow After Process : Unknow Error.');
	}
}

# DETECT IMAGE QUALITY (base on linux 'identify')
$iQuality = $oImg->getImageQuality('/path/image_name.jpg');
echo $iQuality;

```
