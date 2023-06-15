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
then press F12 and look for XHR type files to find other locations.

Examples:
1) miasto Knurów => gaid: 502 => type: commune
2) powiat gliwicki => gaid: 6 => type: district

Of course there are much more variables but this plugin don't cover all of them.

## Configuration

* Import default settings `configexport-tauron-wartoscglobalna.ini`
* Go to `<path-to-lms>/?m=configlist` adjust the settings for yourself

## Donation

* Bitcoin (BTC): bc1qvwahntcrwjtdp0ntfd0l6kdvdr9d9h6atp6qrr
* Ethereum (ETH): 0xEFCd4b066195652a885d916183ffFfeEEd931f40
