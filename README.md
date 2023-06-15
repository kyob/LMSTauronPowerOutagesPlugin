# UWAGA! TAURON przbudował swoją stronę i nie udostępnia już w sposób jawny informacji o wyłączeniach. W konsekwencji plugin aktualnie jest bezużyteczny.


# TauronPowerOutage plugin dla LMS

Shows power outages in areas served by Tauron.

![](tauron-power-outages.png?raw=true)

## Requirements

Installed [LMS](https://lms.org.pl/) or [LMS-PLUS](https://lms-plus.org) (recommended).

## Installation

* Copy files to `<path-to-lms>/plugins/`
* Run `composer update` or `composer update --no-dev`
* Go to LMS website and activate it `Configuration => Plugins`


## How to add your area?

We need to do some reverse engineering to get result in JSON format.

First open web browser and go to https://www.tauron-dystrybucja.pl/wylaczenia/wylaczenia-oddzialy
then press F12 and go to the Network tab. Next on the page choose your province (województwo), district (powiat) and (or not) commune (gmina).
Next click Sprawdź button. Look into Network Monitor tab, there is GET request with your values, something like this:
https://www.tauron-dystrybucja.pl/waapi/outages/area?provinceGAID=24&districtGAID=6&fromDate=2023-06-15T16%3A17%3A12.044Z&toDate=2023-06-20T16%3A17%3A12.044Z&communeGAID=502&_=1686844208220
Write down provinceGAID, districtGAID and communeGAID - these are searched values.

Examples:
1) miasto Knurów => communeGAID: 502 => type: commune
2) powiat gliwicki => districtGAID: 6 => type: district
3) województwo śląskie => provinceGAID: 24 => type: province

Of course there are much more variables but this plugin don't cover all of them.

## Configuration

* Import default settings `configexport-tauron-wartoscglobalna.ini`
* Go to `<path-to-lms>/?m=configlist` adjust the settings for yourself

## Donation

* Bitcoin (BTC): bc1qvwahntcrwjtdp0ntfd0l6kdvdr9d9h6atp6qrr
* Ethereum (ETH): 0xEFCd4b066195652a885d916183ffFfeEEd931f40
