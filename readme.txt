=== AmazonSimpleAdmin ===
Tags: amazon, admin, bbcode, collections, simple, product, preview, sidebar
Contributors: worschtebrot
Requires at least: 1.5
Tested up to: 2.5
Stable Tag: 0.9.4.1

Lets you easily embed Amazon products into your posts by use of [asa]ASIN[/asa] tags. Supports the use of templates. So you can choose from various presentation styles and of course create your own template in a few seconds. Requires at least PHP 5.1.4!

== Description ==

AmazonSimpleAdmin lets you easily integrate Amazon products into your wordpress pages.
By using the template feature, you can present the products in different styles on different pages. All by typing simple BBCode tags.
AmazonSimpleAdmin requires `PHP5`.

Features:

* Ease of use with **BBCode** tags
* Lets you design your own product **templates**
* Supports all Amazon webservice **country codes**: CA, DE, FR, JP, UK, US
* Use your Amazon **tracking ID** for making money
* Features **collections**. You can define and mangage sets of products as a collection an show them on a page with only one BBCode tag or just the latest added product in your sidebar
* Supports product **preview layers** (for US, UK and DE so far)


For the latest information visit my website:

http://www.ichdigital.de/amazonsimpleadmin/

Here you can find a detailed documentation:

http://www.ichdigital.de/amazonsimpleadmin-documentation/

== Installation ==

Just unpack the `amazonsimpleadmin` folder into your plugins directory and activate it on your wordpress plugins page.
Then you will have the option `AmazonSimpleAdmin` on your wordpress options page.

AmazonSimpleAdmin requires `PHP 5.1.4` at least!

== Configuration ==

Go to the new option page `AmazonSimpleAdmin`. On the `Setup` panel you can set your own Amazon tracking ID and Access Key ID.

Here you can find a detailed documentation:

http://www.ichdigital.de/amazonsimpleadmin-documentation/

== Change Log ==

0.9.4.1
* Fixed: Installation of collections (form for installation was not displayed)
* Added function asa_item for use in PHP code everywhere eg sidebar: <?php asa_item('B000TKHBDK'); ?>
* (User request) Added placeholder "CustomRating" which can be used like this [asa custom_rating=3.5]0316015849[/asa] 

0.9.4
* Adapated to wordpress version 2.5
* Added some new DVD related placehoders (Director, Actors, RunningTime, Format, Studio)
* Added an exemplary dvd template

0.9.3.1
* Bugfix: Fixed error in deleting collection items

0.9.3
* Bugfixes
* Added new placeholders: AverageRating, TotalReviews, RatingStars

0.9.2
* Added new placeholders: ASIN, ISBN, EAN, NumberOfPages, ReleaseDate, Binding, Author, Creator, Edition
* Added template book.htm
* Fixed: Delete collection items with qutations in title

0.9.1
* Added a PHP Version checker. If plugin gets activated under PHP 4 it will be reversed.

== Info ==

This is the first public version. I have used this plugin for several weeks now and it works fine so far. If you find any bugs please send me a mail (info@NOSPAM_ichdigital.de, remove the NOSPAM_) or use the comments on the [plugin's homepage](http://www.ichdigital.de/amazonsimpleadmin/). Please also contact me for feature requests and ideas how to improve this plugin. Any other reactions are welcome too of course.

== Frequently Asked Questions ==

No FAQ yet.

== Screenshots ==

1. Setup screen
2. Collections manager panel
3. Page integration examples

