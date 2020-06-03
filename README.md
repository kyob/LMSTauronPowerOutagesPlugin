# LMS plugin PowerOutages

Shows power outages in areas served by Tauron.

![](tauron-power-outages.png?raw=true)

## Requirements

Installed [LMS](https://lms.org.pl/) or [LMS-PLUS](https://lms-plus.org) (recommended).

## Installation

* Copy files to `<path-to-lms>/plugins/`
* Run `composer update` or `composer update --no-dev`
* Go to LMS website and activate it `Configuration => Plugins`
* Set data with best suits to you in file `modules/poweroutages.php`

## How to add your area?

We need to do some reverse engineering to get result in JSON format.

First open web browser and go to https://www.tauron-dystrybucja.pl/wylaczenia/wylaczenia-oddzialy
then press F12 and look for XHR type files to find other locations.

Examples:
1) miasto Knurów => gaid: 502 => type: commune
2) powiat gliwicki => gaid: 6 => type: district

Of course there are much more variables but this plugin don't cover all of them.

## Configuration

Go to `<path-to-lms>/?m=configlist` then add config parameters and values. Otherway default values will be used.

```
tauron.gaid = 502
tauron.type = commune
taruron.api_url = https://www.tauron-dystrybucja.pl/iapi/
```

## Donations :)
https://github.com/kyob
