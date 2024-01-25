Coercive ImgProcess
===================

- The ImgProcess allows you to easily resize your images in PHP with util options like "cover" which allows to cover a dimensioned area provided just as would do the css property of the same name; Or the "crop" that allows you to crop a specific area of your image.

Get
---
```
composer require coercive/imgprocess
```

# Resize image

```php
use Coercive\Utility\ImgProcess\ImgProcess;

# INIT
$img = new ImgProcess;

# QUALITY (optional : default jpg 60 /  png 0)
$img
	->setJpgQuality(50)
	->setPngCompression(5);

# EXAMPLE SET
$img
	->setOverwriting(true)
	->setInputPath('source/path/image_name.jpg')
	->setOutputPath('output/path/new_image.jpg')
	->setSourceCoordinate('RIGHT', 'BOTTOM')
	->setOutputSize(1000, 1000);

# PROCESS
$bVerif = $img->sameSize();
// or
$bVerif = $img->myOwnSize(500);
// or
$bVerif = $img->crop();
// or
$bVerif = $img->cover();
// ...

# HANDLE ERRORS
if( !$bVerif ) {
	if( $aError = $img->getError() ) {
		foreach ($aError as $sMessage) { echo "<p>$sMessage</p>"; }
		die('Shutdow After Process');
	}
	else {
		die('Shutdow After Process : Unknow Error.');
	}
}
```

## Detect image quality

```php
# DETECT IMAGE QUALITY (base on linux 'identify')
$iQuality = ImgProcess::getImageQuality('/path/image_name.jpg');
```

## Get image size

```php
$sizes = ImgProcess::getImageSize('/path/image_name.jpg');
echo $sizes['width'] . ' x ' . $sizes['height']
```

---
---
# Html responsive image

Example of html content to reformat.

```html
<section>
    <p>Hello World</p>
    <img src="my-image.jpg" alt="Hello World" />
</section>
```

## Instantiate basic options

```php
use Coercive\Utility\ImgProcess\ImgResponsive;

$rii = (new ImgResponsive)
    ->overwrite(true)
    ->data($data)
    ->path('/rootpath/server/img', '/realpath/img')
```

### Mode SRCSET with multiplier option

```php
$rii
    ->modeSrcset(true)
    ->size(500, '1x', true)
    ->size(1000, '2x')
    ->size(1500, '3x')
```

### Mode SRCSET with query option

```php
$rii
    ->modeSrcset()
    ->size(645, '(max-width: 750px) 645px')
    ->size(1095, '(min-width: 751px) and (max-width: 938px) 1095px')
    ->size(862, '(min-width: 939px) and (max-width: 1169px) 832px')
    ->size(1095, '(min-width: 1170px) 1095px', true)
```

### Mode PICTURE with query option

```php
$rii
    ->modeSrcset()
    ->size(645, '(max-width: 750px) 645px')
    ->size(1095, '(min-width: 751px) and (max-width: 938px) 1095px')
    ->size(862, '(min-width: 939px) and (max-width: 1169px) 832px')
    ->size(1095, '(min-width: 1170px) 1095px', true)
```

### Resolve image src path

Here is an example of retrieving the good image path if you have some complex directory tree.

```php
$rii->resolve(function ($path) {
    $path = urldecode($path);

    if(!preg_match('`/(?P<env>testmode|production)/filedirectory/(?P<relpath>/.+)$`', $path, $matches)) {
        return '';
    }

    $env = $matches['env'];
    $relpath = $matches['relpath'];
    return  preg_replace('`testmode|production`', $env, '/root/production/specific/path') . $relpath;
});
```

### Start process and get HTML

```php
try {
    $rii->process();
}
catch (Exception $e) {
    // do something
    echo $e->getMessage();
    exit;
}

$formattedHtml = $rii->getHtml();
if(!$formattedHtml) {
    die('KO');
}

echo $formattedHtml;
```