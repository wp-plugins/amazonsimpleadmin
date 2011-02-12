=== AmazonSimpleAdmin ===
Tags: amazon, admin, bbcode, collections, simple, product, preview, sidebar
Contributors: worschtebrot
Requires at least: 2.0
Tested up to: 3.0.5
Stable Tag: 0.9.9

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
* New with version 0.9.5: **Caching** Speeds up your blog when adding many products to your posts!
* New with version 0.9.6: Parsing [asa] tags in user comments 
* Since version 0.9.6 compatible with Amazons Product Advertising API changes by August 15, 2009 which require all requests to be authenticated using request signatures
* Version 0.9.7 supports customer reviews again!

For the latest information visit my website:

http://www.ichdigital.de/amazonsimpleadmin/

Here you can find a detailed documentation:

http://www.ichdigital.de/amazonsimpleadmin-documentation/

== Installation ==

Just unpack the `amazonsimpleadmin` folder into your plugins directory and activate it on your wordpress plugins page.
Then you will have the option `AmazonSimpleAdmin` on your wordpress options page.

AmazonSimpleAdmin requires `PHP 5.1.4` at least!

== Configuration ==

Go to the new option page `AmazonSimpleAdmin`. On the `Setup` panel you can set your Amazon Access Key ID and Secret Access Key.

Here you can find a detailed documentation:

http://www.ichdigital.de/amazonsimpleadmin-documentation/

== Change Log ==
0.9.9
* Added: Support for the excerpt
* Added: 2 new parameters for [asa_collection]: "items" and "type". 
         "items=2" for example lets you limit the collection items to be displayed.
         type supports "random", so "type=random" shows randomly selected collection items. 
         By default the latest added item will be displayed first.
         You may combine the two parameters, separated by comma: [asa_collection items=2, type=random]Products01[/asa_collection]
         If you want to use a template: [asa_collection book, items=2, type=random]Products01[/asa_collection]
* Added: Support for placeholders: Languages, Subtitles (for DVDs), Model, 
         ListPrice->FormattedPrice, ListPrice->CurrencyCode, AmountSaved->FormattedPrice, AmountSaved->CurrencyCode
         
0.9.8
* Bugfixes
                 
0.9.7
* Fixed: Customer reviews. Due to amazon api changes in November 2010 the customer reviews did not work any more.
         The api now provides a url to an iframe where the reviews are generated for 24 hours. I implemented a way
         to grab the 5-stars image and the total reviews so the old placeholders are still supported. I hope this 
         way works. If you have any problems please let me know.
* Added: News placeholders: LowestOfferFormattedPrice, RatingStarsSrc (the url to the image file) 

0.9.6
* Added: Compatible with request authentification
* Added: Parsing [asa] tags in user comments (optional) (feature request from Sebastian, thanks)
* Bugfix: Browsing a collection list which contained an item without an image caused an error (bug report by Lingus, thanks)
* Bugfix: Product with multiple artists caused an error (bug report by Aaron, thanks)

0.9.5
* Added: Caching
* Added new placeholders: ProductDescription, AmazonDescription, Artist
* Bugfix: Template parsing bug fixed

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

If you find any bugs please send me a mail (info@NOSPAM_ichdigital.de, remove the NOSPAM_) or use the comments on the [plugin's homepage](http://www.ichdigital.de/amazonsimpleadmin/). Please also contact me for feature requests and ideas how to improve this plugin. Any other reactions are welcome too of course.

== Frequently Asked Questions ==

No FAQ yet.

== Screenshots ==

1. Setup screen
2. Collections manager panel
3. Page integration examples

